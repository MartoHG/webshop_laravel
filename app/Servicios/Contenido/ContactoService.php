<?php

namespace App\Servicios\Contenido;

use App\Models\Configuracion;
use App\Servicios\Auditoria\AuditoriaService;
use App\Servicios\Pedidos\WhatsAppEnlaceService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactoService
{
    public const string ZONA_HORARIA = 'America/Argentina/Buenos_Aires';

    /** @var list<string> */
    public const array DIAS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

    private const string MENSAJE_CONSULTA = 'Hola El Refugio, quiero hacer una consulta.';

    /** @var array<string, string> */
    private const array CLAVES = [
        'whatsapp' => 'contacto.whatsapp',
        'email' => 'contacto.email',
        'direccion' => 'contacto.direccion',
        'instagram' => 'contacto.instagram',
        'facebook' => 'contacto.facebook',
        'horarios' => 'contacto.horarios',
    ];

    /** @var array<string, string> */
    private const array NOMBRES_DIA = [
        'lunes' => 'lunes',
        'martes' => 'martes',
        'miercoles' => 'miércoles',
        'jueves' => 'jueves',
        'viernes' => 'viernes',
        'sabado' => 'sábado',
        'domingo' => 'domingo',
    ];

    /** @var array<string, string> */
    private const array NOMBRES_DIA_SOLO = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábados',
        'domingo' => 'Domingos',
    ];

    public function __construct(
        private AuditoriaService $auditoria,
        private WhatsAppEnlaceService $whatsapp,
    ) {}

    /**
     * Valores tal como se guardaron, para el panel de administración.
     *
     * @return array{whatsapp: ?string, email: ?string, direccion: ?string, instagram: ?string, facebook: ?string, horarios: ?array<string, list<array{desde: string, hasta: string}>>}
     */
    public function obtener(): array
    {
        return $this->leerEstado();
    }

    public function whatsappDelNegocio(): ?string
    {
        return $this->leerEstado()['whatsapp'];
    }

    /**
     * PUT: reemplaza el bloque completo de contacto. Los campos ausentes quedan vacíos.
     *
     * @param  array{whatsapp?: ?string, email?: ?string, direccion?: ?string, instagram?: ?string, facebook?: ?string, horarios?: ?array<string, list<array{desde: string, hasta: string}>>}  $datos
     *
     * @throws ValidationException
     */
    public function actualizar(array $datos): void
    {
        DB::transaction(function () use ($datos): void {
            $entidad = $this->bloquearEntidad();
            $anteriores = $this->leerEstado(bloquear: true);
            $horarios = $datos['horarios'] ?? null;

            $nuevos = [
                'whatsapp' => $this->validarWhatsapp($this->textoOpcional($datos['whatsapp'] ?? null)),
                'email' => $this->textoOpcional($datos['email'] ?? null),
                'direccion' => $this->textoOpcional($datos['direccion'] ?? null),
                'instagram' => $this->textoOpcional($datos['instagram'] ?? null),
                'facebook' => $this->textoOpcional($datos['facebook'] ?? null),
                'horarios' => $this->horariosParaGuardar(is_array($horarios) ? $horarios : null),
            ];

            $this->guardarCampos($nuevos);
            $this->auditoria->registrarCambios('configuracion.contacto_actualizado', $entidad, $anteriores, $nuevos);
        });
    }

    /** @return array{whatsapp_url: ?string, direccion: ?string, email: ?string, instagram_url: ?string, facebook_url: ?string, horarios_texto: ?string, abierto_ahora: ?bool} */
    public function publico(): array
    {
        $estado = $this->leerEstado();

        return [
            'whatsapp_url' => $estado['whatsapp'] === null
                ? null
                : $this->whatsapp->urlParaTelefono($estado['whatsapp'], self::MENSAJE_CONSULTA),
            'direccion' => $estado['direccion'],
            'email' => $estado['email'],
            'instagram_url' => $estado['instagram'] === null ? null : 'https://www.instagram.com/'.$estado['instagram'].'/',
            'facebook_url' => $estado['facebook'],
            'horarios_texto' => $this->textoHorarios($estado['horarios']),
            'abierto_ahora' => $this->estaAbierto($estado['horarios'], null),
        ];
    }

    /**
     * Resume los horarios para mostrar. Agrupa días consecutivos con los mismos rangos y omite los días cerrados.
     * Devuelve null cuando no hay ningún rango configurado.
     *
     * Ejemplo: "Lunes a viernes de 9 a 13 y de 16 a 20 · Sábados de 9 a 13".
     */
    public function horariosTexto(): ?string
    {
        return $this->textoHorarios($this->leerEstado()['horarios']);
    }

    /**
     * Indica si el local atiende en el momento dado, en la zona horaria de Buenos Aires. Cada rango es
     * [desde, hasta): abre a la hora de inicio y cierra justo en la de fin.
     *
     * No considera feriados ni excepciones: solo el horario semanal cargado.
     * Devuelve null cuando no hay horarios configurados. Sin momento, usa el instante actual.
     */
    public function abiertoAhora(?CarbonInterface $momento = null): ?bool
    {
        return $this->estaAbierto($this->leerEstado()['horarios'], $momento);
    }

    /**
     * @param  ?array<string, list<array{desde: string, hasta: string}>>  $horarios
     */
    private function textoHorarios(?array $horarios): ?string
    {
        if ($horarios === null) {
            return null;
        }

        $grupos = [];
        $continua = false;

        foreach (self::DIAS as $dia) {
            $rangos = $horarios[$dia] ?? [];

            if ($rangos === []) {
                $continua = false;

                continue;
            }

            $clave = json_encode($rangos);
            $ultimo = array_key_last($grupos);

            if ($continua && $grupos[$ultimo]['clave'] === $clave) {
                $grupos[$ultimo]['dias'][] = $dia;
            } else {
                $grupos[] = ['clave' => $clave, 'rangos' => $rangos, 'dias' => [$dia]];
            }

            $continua = true;
        }

        $partes = array_map(
            fn (array $grupo): string => $this->etiquetaDias($grupo['dias']).' '.$this->textoRangos($grupo['rangos']),
            $grupos,
        );

        return $partes === [] ? null : implode(' · ', $partes);
    }

    /**
     * @param  list<string>  $dias
     */
    private function etiquetaDias(array $dias): string
    {
        if (count($dias) === 1) {
            return self::NOMBRES_DIA_SOLO[$dias[0]];
        }

        $primero = $this->capitalizar(self::NOMBRES_DIA[$dias[0]]);
        $ultimo = self::NOMBRES_DIA[$dias[array_key_last($dias)]];

        return count($dias) === 2
            ? $primero.' y '.$ultimo
            : $primero.' a '.$ultimo;
    }

    /**
     * @param  list<array{desde: string, hasta: string}>  $rangos
     */
    private function textoRangos(array $rangos): string
    {
        return implode(' y ', array_map(
            fn (array $rango): string => 'de '.$this->hora($rango['desde']).' a '.$this->hora($rango['hasta']),
            $rangos,
        ));
    }

    /** "09:00" → "9", "09:30" → "9:30", "13:00" → "13". */
    private function hora(string $horaMinuto): string
    {
        [$horas, $minutos] = explode(':', $horaMinuto);
        $horas = (int) $horas;

        return $minutos === '00' ? (string) $horas : $horas.':'.$minutos;
    }

    /**
     * @param  ?array<string, list<array{desde: string, hasta: string}>>  $horarios
     */
    private function estaAbierto(?array $horarios, ?CarbonInterface $momento): ?bool
    {
        if ($horarios === null) {
            return null;
        }

        $local = ($momento ?? Carbon::now())->copy()->setTimezone(self::ZONA_HORARIA);
        $dia = self::DIAS[$local->dayOfWeekIso - 1];
        $minutos = $local->hour * 60 + $local->minute;

        foreach ($horarios[$dia] ?? [] as $rango) {
            if ($this->aMinutos($rango['desde']) <= $minutos && $minutos < $this->aMinutos($rango['hasta'])) {
                return true;
            }
        }

        return false;
    }

    private function aMinutos(string $horaMinuto): int
    {
        [$horas, $minutos] = explode(':', $horaMinuto);

        return (int) $horas * 60 + (int) $minutos;
    }

    /**
     * @param  ?array<string, mixed>  $horarios
     * @return ?array<string, list<array{desde: string, hasta: string}>>
     *
     * @throws ValidationException
     */
    private function horariosParaGuardar(?array $horarios): ?array
    {
        if ($horarios === null) {
            return null;
        }

        $this->validarHorarios($horarios);

        return $this->estructurarHorarios($horarios);
    }

    /**
     * @param  array<string, mixed>  $horarios
     *
     * @throws ValidationException
     */
    private function validarHorarios(array $horarios): void
    {
        foreach (self::DIAS as $dia) {
            $rangos = $this->rangosDelDia($horarios, $dia);
            usort($rangos, fn (array $a, array $b): int => strcmp($a['desde'], $b['desde']));

            foreach ($rangos as $indice => $rango) {
                if ($rango['desde'] >= $rango['hasta']) {
                    throw ValidationException::withMessages([
                        'horarios' => 'En cada horario, la hora de cierre debe ser posterior a la de apertura.',
                    ]);
                }

                if ($indice > 0 && $rango['desde'] < $rangos[$indice - 1]['hasta']) {
                    throw ValidationException::withMessages([
                        'horarios' => 'Los horarios de un mismo día no pueden superponerse.',
                    ]);
                }
            }
        }
    }

    /**
     * Ordena los rangos de cada día y completa los días sin datos como cerrados. Devuelve null si ningún día tiene rangos.
     *
     * @param  array<string, mixed>  $horarios
     * @return ?array<string, list<array{desde: string, hasta: string}>>
     */
    private function estructurarHorarios(array $horarios): ?array
    {
        $estructurado = [];

        foreach (self::DIAS as $dia) {
            $rangos = $this->rangosDelDia($horarios, $dia);
            usort($rangos, fn (array $a, array $b): int => strcmp($a['desde'], $b['desde']));
            $estructurado[$dia] = array_values($rangos);
        }

        $hayRangos = array_any($estructurado, fn (array $rangos): bool => $rangos !== []);

        return $hayRangos ? $estructurado : null;
    }

    /**
     * @param  array<string, mixed>  $horarios
     * @return list<array{desde: string, hasta: string}>
     */
    private function rangosDelDia(array $horarios, string $dia): array
    {
        $lista = $horarios[$dia] ?? [];

        if (! is_array($lista)) {
            return [];
        }

        return array_values(array_map(
            fn (mixed $rango): array => [
                'desde' => (string) (is_array($rango) ? ($rango['desde'] ?? '') : ''),
                'hasta' => (string) (is_array($rango) ? ($rango['hasta'] ?? '') : ''),
            ],
            $lista,
        ));
    }

    /**
     * @return array{whatsapp: ?string, email: ?string, direccion: ?string, instagram: ?string, facebook: ?string, horarios: ?array<string, list<array{desde: string, hasta: string}>>}
     */
    private function leerEstado(bool $bloquear = false): array
    {
        $valores = Configuracion::query()
            ->whereIn('clave', array_values(self::CLAVES))
            ->when($bloquear, fn (Builder $consulta): Builder => $consulta->lockForUpdate())
            ->pluck('valor', 'clave');
        $horariosGuardados = $this->textoOpcional($valores->get(self::CLAVES['horarios']));
        $horarios = $horariosGuardados === null ? null : json_decode($horariosGuardados, true);

        return [
            'whatsapp' => $this->textoOpcional($valores->get(self::CLAVES['whatsapp'])),
            'email' => $this->textoOpcional($valores->get(self::CLAVES['email'])),
            'direccion' => $this->textoOpcional($valores->get(self::CLAVES['direccion'])),
            'instagram' => $this->textoOpcional($valores->get(self::CLAVES['instagram'])),
            'facebook' => $this->textoOpcional($valores->get(self::CLAVES['facebook'])),
            'horarios' => is_array($horarios) ? $this->estructurarHorarios($horarios) : null,
        ];
    }

    /**
     * @param  array<string, string|array<string, mixed>|null>  $campos
     */
    private function guardarCampos(array $campos): void
    {
        foreach ($campos as $campo => $valor) {
            $almacenado = match (true) {
                $valor === null => null,
                is_array($valor) => json_encode($valor, JSON_UNESCAPED_UNICODE),
                default => (string) $valor,
            };

            Configuracion::query()->updateOrCreate(['clave' => self::CLAVES[$campo]], ['valor' => $almacenado]);
        }
    }

    /**
     * La fila de WhatsApp funciona como entidad de auditoría de los datos de contacto.
     */
    private function bloquearEntidad(): Configuracion
    {
        return Configuracion::query()->lockForUpdate()->firstOrCreate(
            ['clave' => self::CLAVES['whatsapp']],
            ['valor' => null],
        );
    }

    /**
     * @throws ValidationException
     */
    private function validarWhatsapp(?string $telefono): ?string
    {
        if ($telefono === null) {
            return null;
        }

        if ($this->whatsapp->normalizarTelefono($telefono) === null) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Ingresá un WhatsApp argentino válido, con código de área. Por ejemplo, 2284 123456.',
            ]);
        }

        return $telefono;
    }

    private function textoOpcional(mixed $valor): ?string
    {
        $texto = $valor === null ? '' : trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function capitalizar(string $texto): string
    {
        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_substr($texto, 1);
    }
}
