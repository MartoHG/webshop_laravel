<?php

namespace App\Servicios\Contenido;

use App\Models\Configuracion;
use App\Models\ImagenContenido;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Imagenes\ImagenContenidoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PortadaService
{
    /** @var array<string, string> */
    private const array CLAVES = [
        'titulo' => 'portada.titulo',
        'subtitulo' => 'portada.subtitulo',
        'boton_texto' => 'portada.boton_texto',
        'boton_destino' => 'portada.boton_destino',
        'imagen_id' => 'portada.imagen_id',
    ];

    public function __construct(
        private AuditoriaService $auditoria,
        private ImagenContenidoService $imagenes,
    ) {}

    /**
     * La portada solo se publica con título y con imagen ya procesada; si falta cualquiera, la home no la muestra.
     *
     * @return array{titulo: string, subtitulo: ?string, boton_texto: ?string, boton_destino: ?string, imagen_url: ?string, imagen_miniatura_url: ?string}|null
     */
    public function obtener(): ?array
    {
        $estado = $this->leerEstado();

        if ($estado['titulo'] === null || $estado['imagen_id'] === null) {
            return null;
        }

        $imagen = $this->imagenPortada($estado['imagen_id'])->where('estado', 'listo')->first();

        if ($imagen === null) {
            return null;
        }

        return [
            'titulo' => $estado['titulo'],
            'subtitulo' => $estado['subtitulo'],
            'boton_texto' => $estado['boton_texto'],
            'boton_destino' => $estado['boton_destino'],
            'imagen_url' => $imagen->url(),
            'imagen_miniatura_url' => $imagen->urlMiniatura(),
        ];
    }

    /**
     * Configuración completa para el panel, incluso si la imagen todavía se está procesando o falló.
     *
     * @return array{titulo: ?string, subtitulo: ?string, boton_texto: ?string, boton_destino: ?string, imagen: ?array{id: int, estado: string, error: ?string, url: ?string, url_miniatura: ?string}}
     */
    public function obtenerParaAdministracion(): array
    {
        $estado = $this->leerEstado();
        $imagen = $estado['imagen_id'] === null ? null : $this->imagenPortada($estado['imagen_id'])->first();

        return [
            'titulo' => $estado['titulo'],
            'subtitulo' => $estado['subtitulo'],
            'boton_texto' => $estado['boton_texto'],
            'boton_destino' => $estado['boton_destino'],
            'imagen' => $imagen === null ? null : [
                'id' => $imagen->id,
                'estado' => $imagen->estado,
                'error' => $imagen->error,
                'url' => $imagen->url(),
                'url_miniatura' => $imagen->urlMiniatura(),
            ],
        ];
    }

    /**
     * Los campos ausentes del arreglo se conservan; los presentes con valor vacío se borran.
     *
     * @param  array{titulo?: ?string, subtitulo?: ?string, boton_texto?: ?string, boton_destino?: ?string}  $datos
     *
     * @throws ValidationException
     */
    public function actualizarTextos(array $datos): void
    {
        DB::transaction(function () use ($datos): void {
            $entidad = $this->bloquearEntidad();
            $anteriores = $this->leerEstado(bloquear: true);
            $nuevos = $anteriores;

            foreach (['titulo', 'subtitulo', 'boton_texto', 'boton_destino'] as $campo) {
                if (array_key_exists($campo, $datos)) {
                    $nuevos[$campo] = $this->textoOpcional($datos[$campo]);
                }
            }

            if ($nuevos['titulo'] === null) {
                throw ValidationException::withMessages(['titulo' => 'El título de la portada es obligatorio.']);
            }

            $this->validarBoton($nuevos['boton_texto'], $nuevos['boton_destino']);
            $this->guardarCampos($nuevos);
            $this->auditoria->registrarCambios('contenido.portada_actualizada', $entidad, $anteriores, $nuevos);
        });
    }

    /**
     * Sube la nueva imagen, la asigna y borra la anterior. Si la transacción falla, la nueva se elimina.
     */
    public function cambiarImagen(UploadedFile $archivo): ImagenContenido
    {
        $nueva = $this->imagenes->subir($archivo, ImagenContenido::TIPO_PORTADA);

        try {
            DB::transaction(function () use ($nueva): void {
                $entidad = $this->bloquearEntidad();
                $anteriorId = $this->leerEstado(bloquear: true)['imagen_id'];

                $this->guardarCampos(['imagen_id' => $nueva->id]);
                $this->auditoria->registrarCambios(
                    'contenido.portada_actualizada',
                    $entidad,
                    ['imagen_id' => $anteriorId],
                    ['imagen_id' => $nueva->id],
                );

                if ($anteriorId !== null) {
                    $anterior = $this->imagenPortada($anteriorId)->lockForUpdate()->first();

                    if ($anterior !== null) {
                        $this->imagenes->eliminar($anterior);
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->imagenes->eliminar($nueva);

            throw $exception;
        }

        return $nueva;
    }

    /**
     * Quita todos los campos de la portada y borra su imagen.
     */
    public function quitar(): void
    {
        DB::transaction(function (): void {
            $entidad = $this->bloquearEntidad();
            $anteriores = $this->leerEstado(bloquear: true);
            $nuevos = array_fill_keys(array_keys($anteriores), null);

            $this->guardarCampos($nuevos);
            $this->auditoria->registrarCambios('contenido.portada_actualizada', $entidad, $anteriores, $nuevos);

            if ($anteriores['imagen_id'] !== null) {
                $imagen = $this->imagenPortada($anteriores['imagen_id'])->lockForUpdate()->first();

                if ($imagen !== null) {
                    $this->imagenes->eliminar($imagen);
                }
            }
        });
    }

    /**
     * @throws ValidationException
     */
    private function validarBoton(?string $texto, ?string $destino): void
    {
        if (($texto === null) === ($destino === null)) {
            return;
        }

        $mensaje = 'El botón necesita texto y destino, o ninguno de los dos.';

        throw ValidationException::withMessages(['boton_texto' => $mensaje, 'boton_destino' => $mensaje]);
    }

    /**
     * La fila de título funciona como entidad de auditoría de la portada.
     */
    private function bloquearEntidad(): Configuracion
    {
        return Configuracion::query()->lockForUpdate()->firstOrCreate(
            ['clave' => self::CLAVES['titulo']],
            ['valor' => null],
        );
    }

    /**
     * @return array{titulo: ?string, subtitulo: ?string, boton_texto: ?string, boton_destino: ?string, imagen_id: ?int}
     */
    private function leerEstado(bool $bloquear = false): array
    {
        $valores = Configuracion::query()
            ->whereIn('clave', array_values(self::CLAVES))
            ->when($bloquear, fn (Builder $consulta): Builder => $consulta->lockForUpdate())
            ->pluck('valor', 'clave');
        $imagenId = $this->textoOpcional($valores->get(self::CLAVES['imagen_id']));

        return [
            'titulo' => $this->textoOpcional($valores->get(self::CLAVES['titulo'])),
            'subtitulo' => $this->textoOpcional($valores->get(self::CLAVES['subtitulo'])),
            'boton_texto' => $this->textoOpcional($valores->get(self::CLAVES['boton_texto'])),
            'boton_destino' => $this->textoOpcional($valores->get(self::CLAVES['boton_destino'])),
            'imagen_id' => $imagenId === null ? null : (int) $imagenId,
        ];
    }

    /** @param array<string, string|int|null> $campos */
    private function guardarCampos(array $campos): void
    {
        foreach ($campos as $campo => $valor) {
            Configuracion::query()->updateOrCreate(
                ['clave' => self::CLAVES[$campo]],
                ['valor' => $valor === null ? null : (string) $valor],
            );
        }
    }

    private function imagenPortada(int|string $imagenId): Builder
    {
        return ImagenContenido::query()
            ->whereKey($imagenId)
            ->where('tipo', ImagenContenido::TIPO_PORTADA);
    }

    private function textoOpcional(mixed $valor): ?string
    {
        $texto = $valor === null ? '' : trim((string) $valor);

        return $texto === '' ? null : $texto;
    }
}
