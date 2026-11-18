# Patrones de código

> Referencia de `respawn-sistemas`: plantillas de cada capa y de cada
> interacción. Los nombres entre llaves son marcadores: `{Modelo}` es la
> clase, `{modelo}` el singular, `{modelos}` el plural y `{Modulo}` la
> carpeta del módulo. Las reglas están en `checklist.md`.

## Contenido

1. Migración, Enum y modelo
2. Form Request y Rule Object
3. Service
4. Policy
5. Controller
6. Rutas
7. Layout del panel
8. Listado con filtros
9. Carga rápida
10. Fila editable
11. Página propia
12. Tests

## 1. Migración, Enum y modelo

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{modelos}', function (Blueprint $tabla): void {
            $tabla->id();
            $tabla->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $tabla->string('nombre', 120);
            $tabla->string('slug', 140)->unique();
            $tabla->decimal('precio', 12, 2);
            $tabla->string('estado', 20);
            $tabla->timestamps();
            $tabla->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{modelos}');
    }
};
```

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum Estado{Modelo}: string
{
    case Borrador = 'borrador';
    case Publicado = 'publicado';

    /** Texto que ve el cliente en el panel. */
    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Sin publicar',
            self::Publicado => 'Publicado',
        };
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Estado{Modelo};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class {Modelo} extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const int POR_PAGINA = 20;

    protected $table = '{modelos}';

    /** @var list<string> */
    protected $fillable = ['categoria_id', 'nombre', 'slug', 'precio', 'estado'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'estado' => Estado{Modelo}::class,
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** Criterio de visibilidad pública, usado por el sitio y por el sitemap. */
    public function scopePublicados(Builder $consulta): Builder
    {
        return $consulta->where('estado', Estado{Modelo}::Publicado);
    }
}
```

## 2. Form Request y Rule Object

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\{Modulo};

use App\Enums\Estado{Modelo};
use App\Models\Categoria;
use App\Models\{Modelo};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Actualizar{Modelo}Request extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('editar {modelos}') ?? false;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'categoria_id' => ['required', Rule::exists(Categoria::class, 'id')],
            'nombre' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:140', Rule::unique({Modelo}::class)->ignore($this->route('{modelo}'))],
            'precio' => ['required', 'decimal:0,2', 'min:0'],
            'estado' => ['required', Rule::enum(Estado{Modelo}::class)],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
```

- `Guardar{Modelo}Request` es igual, con `Rule::unique({Modelo}::class)` sin
  `ignore`.
- Los nombres legibles de los campos van en `lang/es/validation.php`, en
  `attributes`.

```php
<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class {Regla} implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^{patron}$/', $value) !== 1) {
            $fail('validation.{clave_de_la_regla}')->translate();
        }
    }
}
```

## 3. Service

```php
<?php

declare(strict_types=1);

namespace App\Servicios\{Modulo};

use App\Models\{Modelo};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class {Modelo}Service
{
    /** @param array{buscar?: string|null, estado?: string|null} $filtros */
    public function listar(array $filtros, int $pagina): LengthAwarePaginator
    {
        $consulta = {Modelo}::query()
            ->with('categoria')
            ->when($filtros['buscar'] ?? null, fn ($c, string $texto) => $c->where('nombre', 'like', "%{$texto}%"))
            ->when($filtros['estado'] ?? null, fn ($c, string $estado) => $c->where('estado', $estado))
            ->orderBy('nombre');

        $resultado = $consulta->paginate({Modelo}::POR_PAGINA, ['*'], 'page', $pagina);

        if ($pagina > $resultado->lastPage()) {
            $resultado = $consulta->paginate({Modelo}::POR_PAGINA, ['*'], 'page', $resultado->lastPage());
        }

        return $resultado->withQueryString();
    }

    /** @param array{categoria_id: int, nombre: string, slug: string, precio: string, estado: string} $datos */
    public function crear(array $datos): {Modelo}
    {
        return {Modelo}::query()->create($datos);
    }

    /** @param array<string, mixed> $datos */
    public function actualizar({Modelo} ${modelo}, array $datos): {Modelo}
    {
        return DB::transaction(function () use (${modelo}, $datos): {Modelo} {
            ${modelo}->update($datos);

            return ${modelo}->refresh();
        });
    }

    public function eliminar({Modelo} ${modelo}): void
    {
        $motivo = $this->motivoQueImpideEliminar(${modelo});

        if ($motivo !== null) {
            throw ValidationException::withMessages(['{modelo}' => $motivo]);
        }

        ${modelo}->delete();
    }

    /** Devuelve por qué no se puede eliminar, en palabras del cliente, o null si se puede. */
    public function motivoQueImpideEliminar({Modelo} ${modelo}): ?string
    {
        if (${modelo}->pedidos()->exists()) {
            return 'Este {modelo} figura en pedidos. Podés dejar de publicarlo, pero no eliminarlo.';
        }

        return null;
    }
}
```

- `eliminar` llama siempre a `motivoQueImpideEliminar`. La vista puede usar el
  mismo método para no mostrar el botón, pero eso no reemplaza a la
  comprobación.
- El mensaje de una regla de negocio se escribe en el Service, con voseo.

## 4. Policy

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\{Modelo};
use App\Models\Usuario;

class {Modelo}Policy
{
    /** Pertenencia: el registro es del usuario o el usuario administra todos. */
    public function gestionar(Usuario $usuario, {Modelo} ${modelo}): bool
    {
        return $usuario->can('administrar todo') || ${modelo}->usuario_id === $usuario->id;
    }
}
```

## 5. Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\{Modulo};

use App\Http\Controllers\Controller;
use App\Http\Requests\{Modulo}\Actualizar{Modelo}Request;
use App\Http\Requests\{Modulo}\Guardar{Modelo}Request;
use App\Models\{Modelo};
use App\Servicios\{Modulo}\{Modelo}Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class {Modelo}Controller extends Controller
{
    public function __construct(private readonly {Modelo}Service $servicio) {}

    public function index(Request $request): View|string
    {
        Gate::authorize('ver {modelos}');

        $filtros = $request->only(['buscar', 'estado']);
        ${modelos} = $this->servicio->listar($filtros, max((int) $request->input('page', 1), 1));
        $vista = view('{modelos}.index', ['{modelos}' => ${modelos}, 'filtros' => $filtros]);

        return $request->hasHeader('HX-Request') ? $vista->fragment('listado-{modelos}') : $vista;
    }

    public function create(): View
    {
        Gate::authorize('crear {modelos}');

        return view('{modelos}.formulario', ['{modelo}' => new {Modelo}]);
    }

    public function store(Guardar{Modelo}Request $request): Response
    {
        $this->servicio->crear($request->validated());

        return $this->sinContenido([
            'listadoCambio' => true,
            'cerrarDialogo' => true,
            'aviso' => ['tipo' => 'exito', 'texto' => 'El {modelo} se guardó.'],
        ]);
    }

    public function update(Actualizar{Modelo}Request $request, {Modelo} ${modelo}): Response
    {
        Gate::authorize('gestionar', ${modelo});
        $this->servicio->actualizar(${modelo}, $request->validated());

        return $this->sinContenido([
            'listadoCambio' => true,
            'cerrarDialogo' => true,
            'aviso' => ['tipo' => 'exito', 'texto' => 'Los cambios se guardaron.'],
        ]);
    }

    public function destroy({Modelo} ${modelo}): Response
    {
        Gate::authorize('eliminar {modelos}');
        Gate::authorize('gestionar', ${modelo});
        $this->servicio->eliminar(${modelo});

        return $this->sinContenido([
            'listadoCambio' => true,
            'aviso' => ['tipo' => 'exito', 'texto' => 'El {modelo} se eliminó.'],
        ]);
    }
}
```

El Controller base tiene el único lugar donde se arma la cabecera:

```php
/** @param array<string, mixed> $eventos */
protected function sinContenido(array $eventos): Response
{
    return response()->noContent()->header('HX-Trigger', json_encode($eventos, JSON_THROW_ON_ERROR));
}
```

- `json_encode` sin opciones extra escapa los acentos, y así la cabecera
  viaja bien.
- Una acción de página propia responde
  `redirect()->route(…)->with('aviso', ['tipo' => 'exito', 'texto' => '…'])`.

## 6. Rutas

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\{Modulo}\{Modelo}Controller;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('panel')->group(function (): void {
    Route::resource('{modelos}', {Modelo}Controller::class)
        ->parameters(['{modelos}' => '{modelo}'])
        ->except(['show']);

    Route::patch('{modelos}/{{modelo}}/publicar', [{Modelo}Controller::class, 'publicar'])
        ->name('{modelos}.publicar');
});
```

- Un grupo anidado con dos modelos en la dirección declara
  `->scopeBindings()`.
- Las rutas públicas y el webhook viven en archivos aparte.

## 7. Layout del panel

```blade
<!doctype html>
<html lang="es-AR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $titulo }} · Panel</title>
    @vite(['resources/css/panel.css', 'resources/js/panel.js'])
</head>
<body>
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <x-panel.menu />

    <main id="contenido">
        <h1>{{ $titulo }}</h1>
        {{ $slot }}
    </main>

    <dialog id="dialogo" aria-labelledby="titulo-dialogo"></dialog>

    <div id="avisos" role="status" aria-live="polite">
        @if (session('aviso'))
            <div class="aviso aviso--{{ session('aviso.tipo') }}">
                <p>{{ session('aviso.texto') }}</p>
            </div>
        @endif
    </div>
</body>
</html>
```

- `resources/js/panel.js` importa HTMX y el puente, en ese orden.
- Las clases `aviso`, `aviso--exito`, `aviso--error`, `aviso--info`,
  `aviso__cerrar` y `error-campo` las escribe el puente; su estilo se define
  una vez, con los tokens del proyecto.

## 8. Listado con filtros

```blade
<x-layouts.panel titulo="{Modelos}">
    <form method="GET" action="{{ route('{modelos}.index') }}"
          hx-get="{{ route('{modelos}.index') }}"
          hx-target="#listado-{modelos}"
          hx-push-url="true">
        <label for="buscar">Buscar por nombre</label>
        <input type="search" id="buscar" name="buscar" value="{{ $filtros['buscar'] ?? '' }}">
        <button type="submit">Buscar</button>
    </form>

    <button type="button" hx-get="{{ route('{modelos}.create') }}" hx-target="#dialogo">
        Cargar {modelo}
    </button>

    <div id="listado-{modelos}" hx-get="" hx-trigger="listadoCambio from:body">
        @fragment('listado-{modelos}')
            @if (${modelos}->isEmpty())
                <p>{{ ($filtros['buscar'] ?? null) ? 'No hay {modelos} con esos filtros.' : 'Todavía no cargaste {modelos}. Creá el primero.' }}</p>
            @else
                <table>
                    <thead>
                        <tr><th scope="col">Nombre</th><th scope="col">Precio</th><th scope="col">Estado</th><th scope="col">Acciones</th></tr>
                    </thead>
                    <tbody>
                        @foreach (${modelos} as ${modelo})
                            @include('{modelos}._fila', ['{modelo}' => ${modelo}])
                        @endforeach
                    </tbody>
                </table>
                {{ ${modelos}->links() }}
            @endif
        @endfragment
    </div>
</x-layouts.panel>
```

- `hx-get=""` vuelve a pedir la dirección actual: el listado se refresca con
  los filtros y la página que la persona tenía.
- El formulario de filtros funciona también sin JavaScript, por su `method` y
  su `action`.
- La paginación usa los links comunes de Laravel.
- El contenedor queda afuera del `@fragment`; el fragment es solo su
  contenido.

## 9. Carga rápida

```blade
{{-- {modelos}/formulario.blade.php: se carga dentro de #dialogo --}}
<form hx-{{ ${modelo}->exists ? 'put' : 'post' }}="{{ ${modelo}->exists ? route('{modelos}.update', ${modelo}) : route('{modelos}.store') }}"
      hx-swap="none" novalidate>
    <h2 id="titulo-dialogo">{{ ${modelo}->exists ? 'Editar {modelo}' : 'Cargar {modelo}' }}</h2>

    <div data-campo>
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" value="{{ ${modelo}->nombre }}" required>
    </div>

    <div data-campo>
        <label for="precio">Precio</label>
        <input type="text" inputmode="decimal" id="precio" name="precio" value="{{ ${modelo}->precio }}" required>
    </div>

    <button type="submit">Guardar {modelo}</button>
    <button type="button" data-cerrar-dialogo>Cancelar</button>
</form>
```

- Sin `hx-target`: la respuesta es `204` y el puente hace el resto.
- `novalidate`: los errores los devuelve el servidor y el puente los pinta
  debajo de cada `data-campo`.
- El `name` de cada campo coincide con la clave de su regla en el Form
  Request. Si no coincide, el error aparece como aviso y no junto al campo.

## 10. Fila editable

```blade
{{-- {modelos}/_fila.blade.php --}}
<tr id="{modelo}-{{ ${modelo}->id }}">
    <td>{{ ${modelo}->nombre }}</td>
    <td>$ {{ number_format((float) ${modelo}->precio, 0, ',', '.') }}</td>
    <td>
        <button type="button"
                hx-patch="{{ route('{modelos}.publicar', ${modelo}) }}"
                hx-target="#{modelo}-{{ ${modelo}->id }}"
                hx-swap="outerHTML"
                aria-pressed="{{ ${modelo}->estado === \App\Enums\Estado{Modelo}::Publicado ? 'true' : 'false' }}">
            {{ ${modelo}->estado->etiqueta() }}
        </button>
    </td>
    <td>
        <button type="button" hx-get="{{ route('{modelos}.edit', ${modelo}) }}" hx-target="#dialogo">Editar</button>
        <button type="button"
                hx-delete="{{ route('{modelos}.destroy', ${modelo}) }}"
                hx-swap="none"
                hx-confirm="¿Eliminar «{{ ${modelo}->nombre }}»?">
            Eliminar
        </button>
    </td>
</tr>
```

```php
public function publicar({Modelo} ${modelo}): Response
{
    Gate::authorize('gestionar', ${modelo});
    ${modelo} = $this->servicio->alternarPublicacion(${modelo});

    return response()
        ->view('{modelos}._fila', ['{modelo}' => ${modelo}])
        ->header('HX-Trigger', json_encode(
            ['aviso' => ['tipo' => 'exito', 'texto' => 'Listo: '.mb_strtolower(${modelo}->estado->etiqueta()).'.']],
            JSON_THROW_ON_ERROR,
        ));
}
```

## 11. Página propia

```blade
<x-layouts.panel titulo="Datos del negocio">
    <form method="POST" action="{{ route('negocio.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div data-campo>
            <label for="horario">Horario de atención</label>
            <input type="text" id="horario" name="horario" value="{{ old('horario', $negocio->horario) }}"
                   @error('horario') aria-invalid="true" aria-describedby="error-horario" @enderror>
            @error('horario')
                <p class="error-campo" id="error-horario">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit">Guardar cambios</button>
        <a href="{{ route('panel.inicio') }}">Cancelar</a>
    </form>
</x-layouts.panel>
```

- Envío común, sin HTMX: valida, redirige y muestra el aviso de sesión.
- Sirve para todo formulario largo o de uso ocasional.

## 12. Tests

```php
<?php

declare(strict_types=1);

use App\Models\{Modelo};
use App\Models\Usuario;

describe('eliminar un {modelo}', function (): void {
    test('quien tiene permiso lo elimina y el listado se entera', function (): void {
        $usuario = Usuario::factory()->conPermisos(['eliminar {modelos}', 'administrar todo'])->create();
        ${modelo} = {Modelo}::factory()->create();

        $respuesta = $this->actingAs($usuario)
            ->withHeader('HX-Request', 'true')
            ->delete(route('{modelos}.destroy', ${modelo}));

        $respuesta->assertNoContent();
        expect(json_decode((string) $respuesta->headers->get('HX-Trigger'), true))
            ->toHaveKeys(['listadoCambio', 'aviso']);
        $this->assertSoftDeleted(${modelo});
    });

    test('quien no es dueño del registro recibe un 403', function (): void {
        $usuario = Usuario::factory()->conPermisos(['eliminar {modelos}'])->create();
        ${modelo} = {Modelo}::factory()->create();

        $this->actingAs($usuario)
            ->delete(route('{modelos}.destroy', ${modelo}))
            ->assertForbidden();
    });

    test('no se elimina un {modelo} que figura en pedidos', function (): void {
        ${modelo} = {Modelo}::factory()->conPedidos()->create();

        expect(fn () => app(\App\Servicios\{Modulo}\{Modelo}Service::class)->eliminar(${modelo}))
            ->toThrow(\Illuminate\Validation\ValidationException::class);
    });
});
```
