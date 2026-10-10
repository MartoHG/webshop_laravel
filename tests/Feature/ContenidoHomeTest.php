<?php

namespace Tests\Feature;

use App\Enums\IconoBeneficio;
use App\Models\ArchivoImagenProducto;
use App\Models\BeneficioHome;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\ImagenContenido;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\RegistroAuditoria;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Contenido\ContactoService;
use Carbon\CarbonImmutable;
use Database\Seeders\BeneficioHomeSeeder;
use Database\Seeders\ContactoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContenidoHomeTest extends TestCase
{
    use RefreshDatabase;

    private const string ZONA = 'America/Argentina/Buenos_Aires';

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_administrador_gestiona_beneficios_con_orden_y_auditoria(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $envio = $this->postJson('/administracion/contenido/beneficios', [
            'icono' => 'envio',
            'texto' => 'Envíos a todo el país',
        ])->assertCreated()
            ->assertJsonPath('beneficio.orden', 0)
            ->assertJsonPath('beneficio.activo', true)
            ->json('beneficio.id');

        $pago = $this->postJson('/administracion/contenido/beneficios', [
            'icono' => 'pago',
            'texto' => 'Pagás recién al acordar el envío',
        ])->assertCreated()
            ->assertJsonPath('beneficio.orden', 1)
            ->json('beneficio.id');

        $this->patchJson("/administracion/contenido/beneficios/{$pago}", ['texto' => 'Pagás al acordar el envío'])
            ->assertOk()
            ->assertJsonPath('beneficio.texto', 'Pagás al acordar el envío');

        $this->putJson('/administracion/contenido/beneficios/orden', ['ids' => [$pago, $envio]])->assertOk();

        $this->getJson('/administracion/contenido/beneficios')
            ->assertOk()
            ->assertJsonPath('beneficios.0.id', $pago)
            ->assertJsonPath('beneficios.1.id', $envio);

        $this->patchJson("/administracion/contenido/beneficios/{$envio}/estado", ['activo' => false])
            ->assertOk()
            ->assertJsonPath('beneficio.activo', false);

        $this->deleteJson("/administracion/contenido/beneficios/{$envio}")->assertOk();
        $this->assertDatabaseMissing('beneficios_home', ['id' => $envio]);

        foreach (['contenido.beneficio_creado', 'contenido.beneficio_actualizado', 'contenido.beneficio_ordenado', 'contenido.beneficio_eliminado'] as $accion) {
            $this->assertDatabaseHas('registros_auditoria', ['accion' => $accion]);
        }
    }

    public function test_no_admite_mas_de_seis_beneficios_activos(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        BeneficioHome::factory()->count(6)->create();

        $this->postJson('/administracion/contenido/beneficios', [
            'icono' => 'calidad',
            'texto' => 'Calidad garantizada',
        ])->assertUnprocessable()->assertJsonValidationErrors('activo');

        $inactivo = BeneficioHome::factory()->inactivo()->create();
        $this->patchJson("/administracion/contenido/beneficios/{$inactivo->id}/estado", ['activo' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('activo');

        $this->postJson('/administracion/contenido/beneficios', [
            'icono' => 'calidad',
            'texto' => 'Calidad garantizada',
            'activo' => false,
        ])->assertCreated()->assertJsonPath('beneficio.activo', false);

        $activo = BeneficioHome::query()->where('activo', true)->firstOrFail();
        $this->patchJson("/administracion/contenido/beneficios/{$activo->id}/estado", ['activo' => false])->assertOk();
        $this->patchJson("/administracion/contenido/beneficios/{$inactivo->id}/estado", ['activo' => true])->assertOk();

        $this->assertSame(6, BeneficioHome::query()->where('activo', true)->count());
    }

    public function test_el_nuevo_orden_debe_incluir_cada_beneficio_una_sola_vez(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $ids = BeneficioHome::factory()->count(3)->create()->pluck('id')->all();

        $this->putJson('/administracion/contenido/beneficios/orden', ['ids' => [$ids[0], $ids[1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->putJson('/administracion/contenido/beneficios/orden', ['ids' => [$ids[0], $ids[0], $ids[1]]])
            ->assertUnprocessable();
    }

    public function test_telefono_invalido_se_rechaza_y_el_instagram_con_arroba_se_normaliza(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase(['whatsapp' => 'no es un teléfono']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('whatsapp');

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase([
            'whatsapp' => '2284 123456',
            'instagram' => '@elrefugio.olav',
        ]))->assertOk()
            ->assertJsonPath('contacto.whatsapp', '2284 123456')
            ->assertJsonPath('contacto.instagram', 'elrefugio.olav');

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase(['instagram' => 'el refugio']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('instagram');
    }

    public function test_facebook_solo_acepta_https_sobre_el_dominio_de_facebook(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        foreach ([
            'https://www.instagram.com/elrefugio',
            'http://www.facebook.com/elrefugio',
            'https://facebook.com.evil.example/elrefugio',
        ] as $url) {
            $this->putJson('/administracion/contenido/contacto', $this->contactoBase(['facebook' => $url]))
                ->assertUnprocessable()
                ->assertJsonValidationErrors('facebook');
        }

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase(['facebook' => 'https://www.facebook.com/elrefugio']))
            ->assertOk()
            ->assertJsonPath('contacto.facebook', 'https://www.facebook.com/elrefugio');
    }

    public function test_horarios_rechazan_superposiciones_rangos_invertidos_y_mas_de_tres_rangos(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase([
            'horarios' => ['lunes' => [$this->rango('09:00', '13:00'), $this->rango('12:00', '15:00')]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('horarios');

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase([
            'horarios' => ['martes' => [$this->rango('14:00', '10:00')]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('horarios');

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase([
            'horarios' => ['miercoles' => [
                $this->rango('08:00', '09:00'),
                $this->rango('10:00', '11:00'),
                $this->rango('12:00', '13:00'),
                $this->rango('14:00', '15:00'),
            ]],
        ]))->assertUnprocessable()->assertJsonValidationErrors('horarios.miercoles');

        $this->putJson('/administracion/contenido/contacto', $this->contactoBase([
            'horarios' => ['lunes' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')]],
        ]))->assertOk();
    }

    public function test_contacto_se_audita_solo_con_los_campos_que_cambiaron(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        $datos = $this->contactoBase(['direccion' => 'Pringles 2136, Olavarría', 'email' => 'hola@elrefugio.com']);

        $this->putJson('/administracion/contenido/contacto', $datos)->assertOk();
        $this->putJson('/administracion/contenido/contacto', array_merge($datos, ['email' => 'ventas@elrefugio.com']))->assertOk();

        $registro = RegistroAuditoria::query()
            ->where('accion', 'configuracion.contacto_actualizado')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame(['email' => 'hola@elrefugio.com'], $registro->datos_anteriores);
        $this->assertSame(['email' => 'ventas@elrefugio.com'], $registro->datos_nuevos);
    }

    public function test_horarios_texto_agrupa_dias_consecutivos_con_mismos_horarios(): void
    {
        $servicio = app(ContactoService::class);

        $servicio->actualizar(['horarios' => [
            'lunes' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'martes' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'miercoles' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'jueves' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'viernes' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'sabado' => [$this->rango('09:00', '13:00')],
        ]]);

        $this->assertSame('Lunes a viernes de 9 a 13 y de 16 a 20 · Sábados de 9 a 13', $servicio->horariosTexto());

        $servicio->actualizar(['horarios' => [
            'lunes' => [$this->rango('09:00', '13:00')],
            'jueves' => [$this->rango('09:00', '13:00')],
        ]]);

        $this->assertSame('Lunes de 9 a 13 · Jueves de 9 a 13', $servicio->horariosTexto());

        $servicio->actualizar(['horarios' => [
            'lunes' => [$this->rango('09:00', '13:00')],
            'martes' => [$this->rango('09:00', '13:00')],
            'miercoles' => [$this->rango('10:00', '14:00')],
        ]]);

        $this->assertSame('Lunes y martes de 9 a 13 · Miércoles de 10 a 14', $servicio->horariosTexto());

        $servicio->actualizar(['horarios' => [
            'viernes' => [$this->rango('09:30', '13:00'), $this->rango('17:00', '20:30')],
        ]]);

        $this->assertSame('Viernes de 9:30 a 13 y de 17 a 20:30', $servicio->horariosTexto());

        $servicio->actualizar(['horarios' => null]);

        $this->assertNull($servicio->horariosTexto());
    }

    public function test_abierto_ahora_usa_la_hora_de_buenos_aires_y_respeta_el_fin_del_rango(): void
    {
        $servicio = app(ContactoService::class);
        $servicio->actualizar(['horarios' => [
            'lunes' => [$this->rango('09:00', '13:00'), $this->rango('16:00', '20:00')],
            'sabado' => [$this->rango('09:00', '13:00')],
        ]]);

        $lunes = CarbonImmutable::parse('2026-10-12 10:00:00', self::ZONA);
        $domingo = CarbonImmutable::parse('2026-10-11 10:00:00', self::ZONA);
        $this->assertSame(1, $lunes->dayOfWeekIso);
        $this->assertSame(7, $domingo->dayOfWeekIso);

        $this->assertTrue($servicio->abiertoAhora($lunes));
        $this->assertFalse($servicio->abiertoAhora(CarbonImmutable::parse('2026-10-12 14:00:00', self::ZONA)));
        $this->assertFalse($servicio->abiertoAhora(CarbonImmutable::parse('2026-10-12 13:00:00', self::ZONA)));
        $this->assertFalse($servicio->abiertoAhora($domingo));
        $this->assertTrue($servicio->abiertoAhora(CarbonImmutable::parse('2026-10-12 13:00:00', 'UTC')));

        Carbon::setTestNow(CarbonImmutable::parse('2026-10-12 17:30:00', self::ZONA));
        $this->assertTrue($servicio->abiertoAhora());

        $servicio->actualizar(['horarios' => null]);
        $this->assertNull($servicio->abiertoAhora($lunes));
    }

    public function test_inicio_compone_portada_beneficios_categorias_destacadas_y_contacto(): void
    {
        $this->getJson('/tienda/inicio')
            ->assertOk()
            ->assertJsonPath('portada', null)
            ->assertJsonCount(0, 'beneficios')
            ->assertJsonCount(0, 'categorias')
            ->assertJsonCount(0, 'ofertas');

        $imagenPortada = ImagenContenido::factory()->listo()->create(['tipo' => ImagenContenido::TIPO_PORTADA]);
        Configuracion::query()->create(['clave' => 'portada.imagen_id', 'valor' => (string) $imagenPortada->id]);
        $this->getJson('/tienda/inicio')->assertJsonPath('portada', null);

        Configuracion::query()->create(['clave' => 'portada.titulo', 'valor' => 'Verano en el jardín']);

        BeneficioHome::factory()->create(['icono' => IconoBeneficio::Envio, 'texto' => 'Envíos', 'orden' => 1]);
        BeneficioHome::factory()->create(['icono' => IconoBeneficio::Pago, 'texto' => 'Pago', 'orden' => 0]);
        BeneficioHome::factory()->inactivo()->create(['icono' => IconoBeneficio::Garantia, 'texto' => 'Oculto', 'orden' => 2]);

        $imagenCategoria = ImagenContenido::factory()->listo()->create(['tipo' => ImagenContenido::TIPO_CATEGORIA]);
        $this->crearCategoria([
            'nombre' => 'Sillas',
            'slug' => 'sillas',
            'orden' => 1,
            'destacada_en_home' => true,
            'imagen_contenido_id' => $imagenCategoria->id,
        ]);
        $this->crearCategoria(['nombre' => 'Mesas', 'slug' => 'mesas', 'orden' => 2, 'destacada_en_home' => false]);
        $this->crearCategoria([
            'nombre' => 'Reposeras',
            'slug' => 'reposeras',
            'orden' => 3,
            'activo' => false,
            'destacada_en_home' => true,
        ]);

        Configuracion::query()->create(['clave' => 'contacto.direccion', 'valor' => 'Pringles 2136, Olavarría']);
        Configuracion::query()->create(['clave' => 'contacto.whatsapp', 'valor' => '2284 123456']);
        Configuracion::query()->create(['clave' => 'contacto.instagram', 'valor' => 'elrefugio.olav']);

        $respuesta = $this->getJson('/tienda/inicio')
            ->assertOk()
            ->assertJsonPath('portada.titulo', 'Verano en el jardín')
            ->assertJsonPath('portada.imagen_url', $imagenPortada->url())
            ->assertJsonCount(2, 'beneficios')
            ->assertJsonPath('beneficios.0.texto', 'Pago')
            ->assertJsonPath('beneficios.1.icono', 'envio')
            ->assertJsonCount(1, 'categorias')
            ->assertJsonPath('categorias.0.slug', 'sillas')
            ->assertJsonCount(0, 'ofertas')
            ->assertJsonPath('contacto.direccion', 'Pringles 2136, Olavarría')
            ->assertJsonPath('contacto.instagram_url', 'https://www.instagram.com/elrefugio.olav/')
            ->assertJsonPath('contacto.whatsapp_url', 'https://wa.me/5492284123456?text='.rawurlencode('Hola El Refugio, quiero hacer una consulta.'))
            ->assertJsonPath('contacto.horarios_texto', null)
            ->assertJsonPath('contacto.abierto_ahora', null);

        $this->assertNotNull($respuesta->json('categorias.0.imagen_url'));
        $this->assertNotNull($respuesta->json('categorias.0.imagen_miniatura_url'));
    }

    public function test_ofertas_excluye_agotados_y_queda_vacia_sin_ajuste_o_con_ofertas_inactiva(): void
    {
        $ofertas = $this->crearCategoria([
            'nombre' => 'Ofertas',
            'slug' => 'ofertas',
            'clave_sistema' => 'ofertas',
            'activo' => false,
        ]);

        for ($indice = 1; $indice <= 9; $indice++) {
            $producto = $this->crearVariante('Sillón '.$indice)->producto;
            $this->hacerPublicable($producto, $ofertas);
        }

        $agotado = $this->crearVariante('Sillón agotado', disponible: false)->producto;
        $this->hacerPublicable($agotado, $ofertas);

        $this->getJson('/tienda/inicio')->assertOk()->assertJsonCount(0, 'ofertas');

        $this->configurarAjuste('0');
        $this->getJson('/tienda/inicio')->assertOk()->assertJsonCount(0, 'ofertas');

        $ofertas->forceFill(['activo' => true])->save();
        $nombres = collect($this->getJson('/tienda/inicio')->assertOk()->assertJsonCount(8, 'ofertas')->json('ofertas'))
            ->pluck('nombre')
            ->all();

        $this->assertNotContains('Sillón agotado', $nombres);
        $this->getJson('/tienda/inicio')->assertJsonPath('ofertas.0.variantes.0.precio_publicado', '100.00');
    }

    public function test_retiro_arma_el_mensaje_solo_con_las_lineas_disponibles(): void
    {
        $this->configurarAjuste('0');
        Configuracion::query()->create(['clave' => 'contacto.whatsapp', 'valor' => '2284 123456']);
        $silla = $this->crearVariante('Sillón Gran California', precioBase: '79000.00', color: 'Blanco');
        $master = $this->crearVariante('Sillón Máster', precioBase: '69000.00');
        $agotado = $this->crearVariante('Sillón Viejo', precioBase: '10000.00', color: 'Negro');

        $this->postJson('/carrito/items', ['variante_producto_id' => $silla->id, 'cantidad' => 2])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $master->id, 'cantidad' => 1])->assertOk();
        $this->postJson('/carrito/items', ['variante_producto_id' => $agotado->id, 'cantidad' => 1])->assertOk();
        $agotado->forceFill(['disponible' => false])->save();

        $mensaje = implode("\n", [
            'Hola El Refugio, quiero retirar en el local:',
            '• 2 × Sillón Gran California (Blanco) – $158.000',
            '• 1 × Sillón Máster – $69.000',
            'Total: $227.000',
            '¿Cuándo puedo pasar?',
        ]);

        $this->getJson('/carrito/retiro-whatsapp')
            ->assertOk()
            ->assertJsonPath('mensaje', $mensaje)
            ->assertJsonPath('url', 'https://wa.me/5492284123456?text='.rawurlencode($mensaje));
    }

    public function test_retiro_rechaza_carrito_sin_disponibles_y_negocio_sin_whatsapp(): void
    {
        $this->configurarAjuste('0');

        $this->getJson('/carrito/retiro-whatsapp')
            ->assertUnprocessable()
            ->assertJsonPath('errors.carrito.0', 'Tu carrito no tiene productos disponibles.');

        $variante = $this->crearVariante('Sillón Máster', precioBase: '69000.00');
        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 1])->assertOk();

        $this->getJson('/carrito/retiro-whatsapp')
            ->assertUnprocessable()
            ->assertJsonPath('errors.whatsapp.0', 'El negocio todavía no cargó su WhatsApp.');
    }

    public function test_retiro_sin_ajuste_ecommerce_responde_503_como_el_carrito(): void
    {
        $this->getJson('/carrito/retiro-whatsapp')
            ->assertStatus(503)
            ->assertJsonPath('codigo', 'ajuste_ecommerce_no_configurado');
    }

    public function test_seeders_son_idempotentes(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(
            ['Envíos a todo el país por expreso', 'Pagás recién cuando acordamos el envío', 'Retiro en el local de Olavarría', 'Te atendemos por WhatsApp'],
            BeneficioHome::query()->orderBy('orden')->pluck('texto')->all(),
        );
        $this->assertSame(2, Configuracion::query()->where('clave', 'like', 'contacto.%')->count());
        $this->assertDatabaseHas('configuraciones', ['clave' => 'contacto.direccion', 'valor' => 'Pringles 2136, Olavarría']);
        $this->assertDatabaseHas('configuraciones', ['clave' => 'contacto.instagram', 'valor' => 'elrefugio.olav']);
        $this->assertDatabaseMissing('configuraciones', ['clave' => 'contacto.whatsapp']);
    }

    public function test_los_seeders_no_pisan_datos_existentes(): void
    {
        BeneficioHome::factory()->create(['texto' => 'Propio', 'orden' => 0]);
        Configuracion::query()->create(['clave' => 'contacto.email', 'valor' => 'hola@elrefugio.com']);

        (new BeneficioHomeSeeder)->run();
        (new ContactoSeeder)->run();

        $this->assertSame(1, BeneficioHome::query()->count());
        $this->assertDatabaseMissing('configuraciones', ['clave' => 'contacto.direccion']);
    }

    public function test_cliente_no_puede_editar_el_contenido_de_la_home(): void
    {
        $this->actingAs(Usuario::factory()->create());
        $beneficio = BeneficioHome::factory()->create();

        $this->getJson('/administracion/contenido/beneficios')->assertForbidden();
        $this->postJson('/administracion/contenido/beneficios', ['icono' => 'envio', 'texto' => 'Hackeo'])->assertForbidden();
        $this->patchJson("/administracion/contenido/beneficios/{$beneficio->id}", ['texto' => 'Hackeo'])->assertForbidden();
        $this->patchJson("/administracion/contenido/beneficios/{$beneficio->id}/estado", ['activo' => false])->assertForbidden();
        $this->putJson('/administracion/contenido/beneficios/orden', ['ids' => [$beneficio->id]])->assertForbidden();
        $this->deleteJson("/administracion/contenido/beneficios/{$beneficio->id}")->assertForbidden();
        $this->getJson('/administracion/contenido/contacto')->assertForbidden();
        $this->putJson('/administracion/contenido/contacto', $this->contactoBase(['email' => 'hola@elrefugio.com']))->assertForbidden();

        $this->assertDatabaseHas('beneficios_home', ['id' => $beneficio->id, 'texto' => $beneficio->texto]);
        $this->assertDatabaseMissing('configuraciones', ['clave' => 'contacto.email']);
    }

    /** @return array<string, mixed> */
    private function contactoBase(array $cambios = []): array
    {
        return array_merge([
            'whatsapp' => null,
            'email' => null,
            'direccion' => null,
            'instagram' => null,
            'facebook' => null,
            'horarios' => null,
        ], $cambios);
    }

    /** @return array{desde: string, hasta: string} */
    private function rango(string $desde, string $hasta): array
    {
        return ['desde' => $desde, 'hasta' => $hasta];
    }

    private function configurarAjuste(string $porcentaje): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    /** @param array<string, mixed> $atributos */
    private function crearCategoria(array $atributos = []): Categoria
    {
        $extra = Arr::only($atributos, ['destacada_en_home', 'imagen_contenido_id']);
        $categoria = Categoria::factory()->create(Arr::except($atributos, array_keys($extra)));
        $categoria->forceFill($extra)->save();

        return $categoria->refresh();
    }

    private function crearVariante(string $nombre, bool $disponible = true, string $precioBase = '100.00', ?string $color = null): VarianteProducto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'activo' => true,
            'disponible' => $disponible,
        ]);

        return VarianteProducto::factory()->create([
            'producto_id' => $producto->id,
            'color' => $color,
            'precio_base' => $precioBase,
            'activo' => true,
            'disponible' => true,
        ]);
    }

    private function hacerPublicable(Producto $producto, Categoria $categoria): void
    {
        $producto->categorias()->attach($categoria);

        $archivo = ArchivoImagenProducto::query()->create([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'temporal/foto-'.$producto->id.'.jpg',
            'disco' => 'public',
            'ruta' => 'productos/foto-'.$producto->id.'.jpg',
            'ruta_miniatura' => 'productos/foto-'.$producto->id.'-miniatura.jpg',
            'estado' => 'listo',
        ]);

        ImagenProducto::query()->create([
            'producto_id' => $producto->id,
            'variante_producto_id' => null,
            'archivo_imagen_producto_id' => $archivo->id,
            'ruta' => $archivo->ruta,
            'ruta_miniatura' => $archivo->ruta_miniatura,
            'estado' => 'listo',
            'orden' => 0,
            'es_principal' => true,
        ]);
    }
}
