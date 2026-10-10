<?php

namespace Tests\Feature;

use App\Enums\TipoMedioPago;
use App\Models\RegistroAuditoria;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Servicios\Precios\CompensacionPagoService;
use Database\Seeders\ReglaPrecioPagoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class ReglasPrecioPagoTest extends TestCase
{
    use RefreshDatabase;

    private const string RUTA_REGLAS = '/administracion/precios/reglas-pago';

    public function test_compensacion_de_100000_al_20_por_ciento_da_125000(): void
    {
        $this->assertSame('125000.00', app(CompensacionPagoService::class)->calcularImporte('100000', '20'));
    }

    public function test_compensacion_redondea_hacia_arriba_al_centavo(): void
    {
        $servicio = app(CompensacionPagoService::class);

        $this->assertSame('1250.02', $servicio->calcularImporte('1000.01', '20'));
        $this->assertSame('103.10', $servicio->calcularImporte('100', '3'));
    }

    public function test_porcentaje_cero_devuelve_el_mismo_total(): void
    {
        $servicio = app(CompensacionPagoService::class);

        $this->assertSame('1234.50', $servicio->calcularImporte('1234.50', '0'));
        $this->assertSame('0.01', $servicio->calcularImporte('0.01', '0.000000'));
    }

    public function test_calculo_rechaza_total_no_positivo_o_porcentaje_fuera_de_rango(): void
    {
        $servicio = app(CompensacionPagoService::class);

        $this->assertThrows(fn () => $servicio->calcularImporte('0', '10'), InvalidArgumentException::class);
        $this->assertThrows(fn () => $servicio->calcularImporte('100', '100'), InvalidArgumentException::class);
        $this->assertThrows(fn () => $servicio->calcularImporte('100', '-1'), InvalidArgumentException::class);
    }

    public function test_regla_inactiva_o_inexistente_no_se_procesa_como_cero(): void
    {
        ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'porcentaje' => '0',
            'activo' => false,
        ]);
        $servicio = app(CompensacionPagoService::class);

        $this->assertThrows(
            fn () => $servicio->obtenerReglaActiva(TipoMedioPago::Credito, 3),
            fn (ValidationException $excepcion): bool => array_key_exists('modalidad', $excepcion->errors()),
        );
        $this->assertThrows(
            fn () => $servicio->obtenerReglaActiva(TipoMedioPago::Credito, 6),
            fn (ValidationException $excepcion): bool => array_key_exists('modalidad', $excepcion->errors()),
        );

        ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 6,
            'porcentaje' => '20',
        ]);

        $this->assertSame(6, $servicio->obtenerReglaActiva(TipoMedioPago::Credito, 6)->cuotas);
    }

    public function test_opciones_de_pago_solo_incluye_reglas_activas_con_su_importe(): void
    {
        $contado = ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 1,
            'porcentaje' => '0',
        ]);
        $tresCuotas = ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'porcentaje' => '10',
        ]);
        ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 6,
            'porcentaje' => '20',
        ]);
        $debito = ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Debito,
            'cuotas' => 1,
            'porcentaje' => '5',
        ]);
        ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::DineroEnCuenta,
            'cuotas' => 1,
            'porcentaje' => '3',
        ]);

        $this->assertSame([
            ['regla_id' => $contado->id, 'tipo_medio_pago' => 'credito', 'cuotas' => 1, 'porcentaje' => '0.000000', 'importe' => '100000.00'],
            ['regla_id' => $tresCuotas->id, 'tipo_medio_pago' => 'credito', 'cuotas' => 3, 'porcentaje' => '10.000000', 'importe' => '111111.12'],
            ['regla_id' => $debito->id, 'tipo_medio_pago' => 'debito', 'cuotas' => 1, 'porcentaje' => '5.000000', 'importe' => '105263.16'],
        ], app(CompensacionPagoService::class)->opcionesDePago('100000'));
    }

    public function test_administrador_crea_y_actualiza_reglas_y_queda_auditado(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador)
            ->postJson(self::RUTA_REGLAS, [
                'tipo_medio_pago' => 'credito',
                'cuotas' => 6,
                'porcentaje' => 12.5,
            ])
            ->assertCreated()
            ->assertJsonPath('regla.tipo_medio_pago', 'credito')
            ->assertJsonPath('regla.cuotas', 6)
            ->assertJsonPath('regla.porcentaje', '12.500000')
            ->assertJsonPath('regla.activo', false);

        $regla = ReglaPrecioPago::query()->where('tipo_medio_pago', 'credito')->where('cuotas', 6)->sole();

        $this->actingAs($administrador)
            ->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['porcentaje' => '15', 'activo' => true])
            ->assertOk()
            ->assertJsonPath('regla.porcentaje', '15.000000')
            ->assertJsonPath('regla.activo', true);

        $this->actingAs($administrador)
            ->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['porcentaje' => '15', 'activo' => true])
            ->assertOk();

        $creada = RegistroAuditoria::query()->where('accion', 'regla_precio_pago.creada')->sole();
        $this->assertSame($administrador->id, $creada->usuario_id);
        $this->assertSame('reglas_precio_pago', $creada->entidad);
        $this->assertSame($regla->id, $creada->entidad_id);
        $this->assertEqualsCanonicalizing(
            ['tipo_medio_pago' => 'credito', 'cuotas' => 6, 'porcentaje' => '12.5', 'activo' => false],
            $creada->datos_nuevos,
        );

        $actualizada = RegistroAuditoria::query()->where('accion', 'regla_precio_pago.actualizada')->sole();
        $this->assertEqualsCanonicalizing(['porcentaje' => '12.5', 'activo' => false], $actualizada->datos_anteriores);
        $this->assertEqualsCanonicalizing(['porcentaje' => '15', 'activo' => true], $actualizada->datos_nuevos);
    }

    public function test_actualizacion_interpreta_activo_textual_y_exige_algun_campo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $regla = ReglaPrecioPago::factory()->activa()->create([
            'tipo_medio_pago' => TipoMedioPago::Debito,
            'cuotas' => 1,
            'porcentaje' => '4',
        ]);

        $this->patchJson(self::RUTA_REGLAS.'/'.$regla->id, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['porcentaje', 'activo']);

        $this->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['activo' => '0'])
            ->assertOk()
            ->assertJsonPath('regla.activo', false);

        $this->assertDatabaseHas('reglas_precio_pago', ['id' => $regla->id, 'activo' => false, 'porcentaje' => '4.000000']);
    }

    public function test_tipo_y_cuotas_no_se_pueden_modificar_ni_repetir(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $regla = ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 3,
            'porcentaje' => '10',
        ]);
        ReglaPrecioPago::factory()->create([
            'tipo_medio_pago' => TipoMedioPago::Credito,
            'cuotas' => 6,
        ]);

        $this->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['tipo_medio_pago' => 'debito'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tipo_medio_pago');

        $this->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['cuotas' => 6])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cuotas');

        $this->postJson(self::RUTA_REGLAS, [
            'tipo_medio_pago' => 'credito',
            'cuotas' => 3,
            'porcentaje' => '5',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cuotas')
            ->assertJsonPath('errors.cuotas.0', 'Ya existe una regla para este medio de pago y cantidad de cuotas.');

        $this->assertDatabaseHas('reglas_precio_pago', [
            'id' => $regla->id,
            'tipo_medio_pago' => 'credito',
            'cuotas' => 3,
        ]);
    }

    public function test_debito_y_dinero_en_cuenta_solo_admiten_un_pago(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'debito', 'cuotas' => 3, 'porcentaje' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cuotas');

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'dinero_en_cuenta', 'cuotas' => 2, 'porcentaje' => '1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cuotas');

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'debito', 'cuotas' => 1, 'porcentaje' => '1'])
            ->assertCreated();

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'dinero_en_cuenta', 'cuotas' => 1, 'porcentaje' => '1'])
            ->assertCreated();
    }

    public function test_porcentaje_debe_ser_mayor_o_igual_a_cero_y_menor_a_100(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'credito', 'cuotas' => 1, 'porcentaje' => '-1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'credito', 'cuotas' => 1, 'porcentaje' => '100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'credito', 'cuotas' => 1, 'porcentaje' => '100.000001'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'credito', 'cuotas' => 1, 'porcentaje' => '0'])
            ->assertCreated();

        $this->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'credito', 'cuotas' => 3, 'porcentaje' => '99.999999'])
            ->assertCreated();

        $regla = ReglaPrecioPago::query()->where('cuotas', 3)->sole();

        $this->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['porcentaje' => '100'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('porcentaje');
    }

    public function test_cliente_no_puede_gestionar_reglas_de_pago(): void
    {
        $regla = ReglaPrecioPago::factory()->create(['porcentaje' => '10']);
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente)->getJson(self::RUTA_REGLAS)->assertForbidden();

        $this->actingAs($cliente)
            ->postJson(self::RUTA_REGLAS, ['tipo_medio_pago' => 'debito', 'cuotas' => 1, 'porcentaje' => '1'])
            ->assertForbidden();

        $this->actingAs($cliente)
            ->patchJson(self::RUTA_REGLAS.'/'.$regla->id, ['porcentaje' => '5'])
            ->assertForbidden();

        $this->assertDatabaseHas('reglas_precio_pago', ['id' => $regla->id, 'porcentaje' => '10.000000']);
    }

    public function test_seeder_crea_reglas_iniciales_inactivas_sin_pisar_las_existentes(): void
    {
        $this->seed(ReglaPrecioPagoSeeder::class);

        $this->assertSame(6, ReglaPrecioPago::query()->count());
        $this->assertSame(0, ReglaPrecioPago::query()->where('activo', true)->count());
        $this->assertSame(0, ReglaPrecioPago::query()->where('porcentaje', '<>', '0')->count());

        $regla = ReglaPrecioPago::query()->where('tipo_medio_pago', 'credito')->where('cuotas', 3)->sole();
        $regla->update(['porcentaje' => '9', 'activo' => true]);

        $this->seed(ReglaPrecioPagoSeeder::class);

        $this->assertSame(6, ReglaPrecioPago::query()->count());
        $regla->refresh();
        $this->assertTrue($regla->activo);
        $this->assertSame('9.000000', $regla->porcentaje);
    }
}
