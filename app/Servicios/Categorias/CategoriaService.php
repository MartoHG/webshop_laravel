<?php

namespace App\Servicios\Categorias;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoriaService
{
    public function listar(): Collection
    {
        return Categoria::query()->with('imagenContenido')->orderBy('orden')->orderBy('nombre')->get();
    }

    /** @param array{nombre: string, slug?: ?string, orden?: int} $datos */
    public function crear(array $datos): Categoria
    {
        $slug = $this->slugDisponible(($datos['slug'] ?? null) ?: Str::slug($datos['nombre']));

        return Categoria::query()->create([
            'nombre' => $datos['nombre'],
            'slug' => $slug,
            'activo' => true,
            'orden' => $datos['orden'] ?? 0,
        ]);
    }

    /** @param array{nombre?: string, slug?: ?string, orden?: int} $datos */
    public function actualizar(int $id, array $datos): Categoria
    {
        return DB::transaction(function () use ($id, $datos): Categoria {
            $categoria = Categoria::query()->lockForUpdate()->findOrFail($id);

            if ($categoria->clave_sistema !== null) {
                throw ValidationException::withMessages(['categoria' => 'La categoría Ofertas no admite cambios de nombre ni de slug.']);
            }

            if (array_key_exists('nombre', $datos)) {
                $categoria->nombre = $datos['nombre'];
            }
            if (array_key_exists('slug', $datos) && $datos['slug'] !== null) {
                $categoria->slug = $this->slugDisponible($datos['slug'], $categoria->id);
            }
            if (array_key_exists('orden', $datos)) {
                $categoria->orden = $datos['orden'];
            }
            $categoria->save();

            return $categoria->refresh();
        });
    }

    public function cambiarEstado(int $id, bool $activo): Categoria
    {
        return DB::transaction(function () use ($id, $activo): Categoria {
            $categoria = Categoria::query()->findOrFail($id);

            if (! $activo && $categoria->activo) {
                $productos = DB::table('productos')
                    ->join('categoria_producto', 'productos.id', '=', 'categoria_producto.producto_id')
                    ->where('categoria_producto.categoria_id', $categoria->id)
                    ->where('productos.activo', true)
                    ->orderBy('productos.id')
                    ->lockForUpdate()
                    ->get(['productos.id']);

                foreach ($productos as $producto) {
                    $tieneOtraCategoriaActiva = DB::table('categoria_producto')
                        ->join('categorias', 'categorias.id', '=', 'categoria_producto.categoria_id')
                        ->where('categoria_producto.producto_id', $producto->id)
                        ->where('categorias.activo', true)
                        ->where('categorias.id', '!=', $categoria->id)
                        ->exists();

                    if (! $tieneOtraCategoriaActiva) {
                        throw ValidationException::withMessages(['activo' => 'No se puede desactivar: dejaría un producto activo sin categorías activas.']);
                    }
                }
            }

            $categoria = Categoria::query()->lockForUpdate()->findOrFail($id);
            $categoria->activo = $activo;
            $categoria->save();

            return $categoria->refresh();
        });
    }

    /** @param list<int> $ids */
    public function ordenar(array $ids): void
    {
        DB::transaction(function () use ($ids): void {
            foreach ($ids as $orden => $id) {
                Categoria::query()->whereKey($id)->update(['orden' => $orden]);
            }
        });
    }

    /** @param list<int> $categoriaIds */
    public function sincronizarProducto(int $productoId, array $categoriaIds): void
    {
        DB::transaction(function () use ($productoId, $categoriaIds): void {
            $producto = DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
            abort_if($producto === null, 404);

            $categorias = Categoria::query()
                ->whereIn('id', $categoriaIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($categorias->count() !== count(array_unique($categoriaIds))) {
                throw ValidationException::withMessages(['categoria_ids' => 'Una o más categorías no existen.']);
            }

            if ($producto->activo && ! $categorias->contains(fn (Categoria $categoria): bool => $categoria->activo)) {
                throw ValidationException::withMessages(['categoria_ids' => 'Un producto activo debe conservar al menos una categoría activa.']);
            }

            DB::table('categoria_producto')->where('producto_id', $productoId)->delete();
            if ($categoriaIds !== []) {
                DB::table('categoria_producto')->insert(array_map(
                    fn (int $categoriaId): array => ['producto_id' => $productoId, 'categoria_id' => $categoriaId],
                    array_values(array_unique($categoriaIds)),
                ));
            }
        });
    }

    public function asignarProducto(int $productoId, int $categoriaId): void
    {
        DB::transaction(function () use ($productoId, $categoriaId): void {
            $producto = DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
            abort_if($producto === null, 404);

            $ids = DB::table('categoria_producto')->where('producto_id', $productoId)->pluck('categoria_id')->map(fn ($id): int => (int) $id)->all();
            $ids[] = $categoriaId;
            $this->sincronizarProducto($productoId, array_values(array_unique($ids)));
        });
    }

    public function desasignarProducto(int $productoId, int $categoriaId): void
    {
        DB::transaction(function () use ($productoId, $categoriaId): void {
            $producto = DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
            abort_if($producto === null, 404);

            $categoria = Categoria::query()->lockForUpdate()->findOrFail($categoriaId);
            $asignada = DB::table('categoria_producto')
                ->where('producto_id', $productoId)->where('categoria_id', $categoriaId)->exists();

            if (! $asignada) {
                throw ValidationException::withMessages(['categoria_id' => 'La categoría no está asignada a este producto.']);
            }

            $otraActiva = DB::table('categoria_producto')
                ->join('categorias', 'categorias.id', '=', 'categoria_producto.categoria_id')
                ->where('categoria_producto.producto_id', $productoId)
                ->where('categorias.activo', true)
                ->where('categorias.id', '!=', $categoriaId)
                ->exists();

            if ($producto->activo && $categoria->activo && ! $otraActiva) {
                throw ValidationException::withMessages(['categoria_id' => 'No se puede quitar la última categoría activa de un producto activo.']);
            }

            DB::table('categoria_producto')->where('producto_id', $productoId)->where('categoria_id', $categoriaId)->delete();
        });
    }

    private function slugDisponible(string $slug, ?int $ignorarId = null): string
    {
        $slugBase = Str::slug($slug);
        if ($slugBase === '') {
            $slugBase = 'categoria';
        }
        $slug = $slugBase;
        $sufijo = 2;

        while (Categoria::query()->where('slug', $slug)->when($ignorarId, fn ($consulta) => $consulta->whereKeyNot($ignorarId))->exists()) {
            $slug = $slugBase.'-'.$sufijo++;
        }

        return $slug;
    }
}
