<?php

namespace Tests\Feature;

use App\Models\ArchivoImagenProducto;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\VarianteProducto;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VistasCarritoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_carrito_en_html_muestra_lineas_y_totales(): void
    {
        $this->configurarAjuste();
        $blanco = $this->agregarVariante($this->crearProducto('Sillón Gran California'), ['color' => 'Blanco', 'precio_base' => '79000.00']);
        $negro = $this->agregarVariante($this->crearProducto('Sillón Máster reforzado'), ['color' => 'Negro', 'precio_base' => '69000.00']);
        $this->agregarAlCarrito($blanco, 2);
        $this->agregarAlCarrito($negro, 1);

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertViewIs('tienda.carrito')
            ->assertSee('<title>Tu carrito · El Refugio</title>', false)
            ->assertSee('3 unidades')
            ->assertSee('Sillón Gran California')
            ->assertSee('Sillón Máster reforzado')
            ->assertSee('Blanco')
            ->assertSee('$158.000')
            ->assertSee('$227.000')
            ->assertSee('Lo acordamos por WhatsApp')
            ->assertSee('Todavía no pagás nada.')
            ->assertSee('Continuar con el pedido')
            ->assertSee('href="'.route('checkout.resumen').'"', false)
            ->assertSee('href="'.route('catalogo.mostrar', 'sillon-gran-california').'"', false)
            ->assertSee('Carrito, 3 unidades');
    }

    public function test_agregar_desde_el_formulario_redirige_al_carrito_con_aviso(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Sillón Gran California');
        $variante = $this->agregarVariante($producto, ['color' => 'Blanco', 'precio_base' => '79000.00']);
        $pagina = route('catalogo.mostrar', $producto->slug);

        $this->from($pagina)
            ->post(route('carrito.items.agregar'), ['variante_producto_id' => $variante->id, 'cantidad' => 2])
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('estado', 'Agregaste 2 × Sillón Gran California');

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Agregaste 2 × Sillón Gran California')
            ->assertSee('Carrito, 2 unidades');
    }

    public function test_un_error_al_agregar_vuelve_a_la_pagina_del_producto_con_el_motivo(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Silla Plegable');
        $this->agregarVariante($producto, ['color' => 'Blanco', 'precio_base' => '45000.00']);
        $agotada = $this->agregarVariante($producto, ['color' => 'Azul', 'precio_base' => '46000.00', 'disponible' => false]);
        $pagina = route('catalogo.mostrar', $producto->slug);

        $this->from($pagina)
            ->post(route('carrito.items.agregar'), ['variante_producto_id' => $agotada->id, 'cantidad' => 1])
            ->assertRedirect($pagina)
            ->assertSessionHasErrors('variante_producto_id');

        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get($pagina)
            ->assertOk()
            ->assertSee('La variante está agotada y no puede agregarse al carrito.');
    }

    public function test_una_cantidad_invalida_vuelve_a_la_pagina_del_producto_con_el_error(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Reposera Mascardi');
        $variante = $this->agregarVariante($producto, ['precio_base' => '45000.00']);
        $pagina = route('catalogo.mostrar', $producto->slug);

        $respuesta = $this->from($pagina)
            ->post(route('carrito.items.agregar'), ['variante_producto_id' => $variante->id, 'cantidad' => 0])
            ->assertRedirect($pagina)
            ->assertSessionHasErrors('cantidad');

        $mensaje = session('errors')->first('cantidad');
        $this->assertNotEmpty($mensaje);

        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get($pagina)->assertOk()->assertSee($mensaje);
    }

    public function test_cambiar_cantidad_y_quitar_con_formularios_clasicos_redirigen_al_carrito(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Silla Kansas'), ['precio_base' => '45000.00']);
        $this->agregarAlCarrito($variante, 1);

        $this->post(route('carrito.items.actualizar', $variante), ['_method' => 'PATCH', 'cantidad' => 3])
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('estado', 'Cantidad actualizada.');
        $this->getJson('/carrito')->assertJsonPath('carrito.cantidad_unidades', 3);

        $this->post(route('carrito.items.quitar', $variante), ['_method' => 'DELETE'])
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('estado', 'Quitaste el mueble del carrito.');
        $this->get(route('carrito.mostrar'))->assertSee('Tu carrito está vacío.');

        $this->agregarAlCarrito($variante, 2);
        $this->post(route('carrito.vaciar'), ['_method' => 'DELETE'])
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('estado', 'Vaciaste el carrito.');
    }

    public function test_con_htmx_cambiar_cantidad_devuelve_solo_el_contenido_y_el_contador(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Silla Kansas'), ['precio_base' => '45000.00']);
        $this->agregarAlCarrito($variante, 1);

        $this->withHeaders(['HX-Request' => 'true'])
            ->post(route('carrito.items.actualizar', $variante), ['_method' => 'PATCH', 'cantidad' => 3])
            ->assertOk()
            ->assertViewIs('tienda.partials.carrito-actualizado')
            ->assertSee('id="carrito-contenido"', false)
            ->assertSee('id="carrito-enlace"', false)
            ->assertSee('hx-swap-oob="true"', false)
            ->assertSee('Carrito, 3 unidades')
            ->assertDontSee('<!DOCTYPE html>', false)
            ->assertDontSee('<header', false);
    }

    public function test_con_htmx_quitar_devuelve_el_estado_vacio_y_el_contador_en_cero(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Silla Kansas'), ['precio_base' => '45000.00']);
        $this->agregarAlCarrito($variante, 1);

        $this->withHeaders(['HX-Request' => 'true'])
            ->post(route('carrito.items.quitar', $variante), ['_method' => 'DELETE'])
            ->assertOk()
            ->assertViewIs('tienda.partials.carrito-actualizado')
            ->assertSee('Tu carrito está vacío.')
            ->assertSee('Carrito, 0 unidades')
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_una_linea_no_disponible_muestra_aviso_y_bloquea_continuar(): void
    {
        $this->configurarAjuste();
        $mesa = $this->agregarVariante($this->crearProducto('Mesa Aruba'), ['color' => 'Blanco', 'precio_base' => '30000.00']);
        $silla = $this->agregarVariante($this->crearProducto('Silla Vieja'), ['color' => 'Verde', 'precio_base' => '31000.00']);
        $this->agregarAlCarrito($mesa, 1);
        $this->agregarAlCarrito($silla, 1);
        $silla->forceFill(['disponible' => false])->save();

        $respuesta = $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Hay productos que ya no están disponibles. Quitalos para continuar.')
            ->assertSee('Agotado')
            ->assertSee('No suma al total');

        $this->assertTrue($this->nodo($respuesta, '//button[normalize-space()="Continuar con el pedido"]')->hasAttribute('disabled'));
        $this->assertSame(0, $this->cuenta($respuesta, '//a[@href="'.route('checkout.resumen').'"]'));
        $this->assertSame(0, $this->cuenta($respuesta, '//button[@aria-label="Sumar uno a Silla Vieja"]'));
        $this->assertSame(1, $this->cuenta($respuesta, '//button[@aria-label="Sumar uno a Mesa Aruba"]'));
    }

    public function test_carrito_vacio_muestra_estado_vacio_con_enlace_a_muebles(): void
    {
        $this->configurarAjuste();

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Tu carrito está vacío.')
            ->assertSee('Ver muebles')
            ->assertDontSee('Continuar con el pedido');
    }

    public function test_el_retiro_abre_whatsapp_y_solo_aparece_con_el_whatsapp_del_negocio(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Reposera Mascardi'), ['precio_base' => '45000.00']);
        $this->agregarAlCarrito($variante, 2);

        $this->get(route('carrito.mostrar'))->assertOk()->assertDontSee('Retirar en el local');

        Configuracion::query()->create(['clave' => 'contacto.whatsapp', 'valor' => '2284 123456']);
        Configuracion::query()->create(['clave' => 'contacto.direccion', 'valor' => 'Pringles 2136, Olavarría']);

        $this->get(route('carrito.mostrar'))
            ->assertOk()
            ->assertSee('Retirar en el local')
            ->assertSee('href="'.route('carrito.retiro').'"', false)
            ->assertSee('Pringles 2136, Olavarría');

        $url = $this->getJson(route('carrito.retiro'))->assertOk()->json('url');
        $this->get(route('carrito.retiro'))->assertRedirect($url);
    }

    public function test_el_retiro_sin_whatsapp_o_sin_disponibles_vuelve_al_carrito_con_error(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Reposera Mascardi'), ['precio_base' => '45000.00']);
        $this->agregarAlCarrito($variante, 1);

        $this->get(route('carrito.retiro'))
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('error', 'El negocio todavía no cargó su WhatsApp.');
        $this->get(route('carrito.mostrar'))->assertSee('El negocio todavía no cargó su WhatsApp.');

        $variante->forceFill(['disponible' => false])->save();

        $this->get(route('carrito.retiro'))
            ->assertRedirect(route('carrito.mostrar'))
            ->assertSessionHas('error', 'Tu carrito no tiene productos disponibles.');
    }

    public function test_sin_ajuste_configurado_el_carrito_html_muestra_la_pagina_de_preparacion(): void
    {
        $this->get(route('carrito.mostrar'))
            ->assertStatus(503)
            ->assertSee('La tienda se está preparando');
    }

    public function test_los_endpoints_json_del_carrito_siguen_igual(): void
    {
        $this->configurarAjuste();
        $variante = $this->agregarVariante($this->crearProducto('Silla Kansas'), ['precio_base' => '45000.00']);

        $this->postJson('/carrito/items', ['variante_producto_id' => $variante->id, 'cantidad' => 2])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Producto agregado al carrito.');

        $this->getJson('/carrito')->assertOk()->assertJsonPath('carrito.cantidad_unidades', 2);

        $this->patchJson('/carrito/items/'.$variante->id, ['cantidad' => 3])
            ->assertOk()
            ->assertJsonPath('mensaje', 'Cantidad actualizada.');

        $this->deleteJson('/carrito/items/'.$variante->id)
            ->assertOk()
            ->assertJsonPath('mensaje', 'Producto quitado del carrito.');
    }

    private function configurarAjuste(string $porcentaje = '0'): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    private function agregarAlCarrito(VarianteProducto $variante, int $cantidad): void
    {
        $this->post(route('carrito.items.agregar'), ['variante_producto_id' => $variante->id, 'cantidad' => $cantidad])
            ->assertRedirect(route('carrito.mostrar'));
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function agregarVariante(Producto $producto, array $atributos = []): VarianteProducto
    {
        return VarianteProducto::factory()->create(array_merge([
            'producto_id' => $producto->id,
            'precio_base' => '100.00',
            'activo' => true,
            'disponible' => true,
        ], $atributos));
    }

    /**
     * Con foto general principal y una categoría activa el producto es publicable.
     */
    private function crearProducto(string $nombre): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'activo' => true,
            'disponible' => true,
        ]);

        $categoria = Categoria::query()->firstOrCreate(['slug' => 'general'], ['nombre' => 'General', 'activo' => true]);
        $producto->categorias()->attach($categoria);

        $ruta = 'productos/'.$producto->slug.'-general.jpg';
        $archivo = ArchivoImagenProducto::query()->create([
            'disco_temporal' => 'local',
            'ruta_temporal' => 'temporal/'.$ruta,
            'disco' => 'public',
            'ruta' => $ruta,
            'ruta_miniatura' => Str::replaceLast('.jpg', '-miniatura.jpg', $ruta),
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

        return $producto;
    }

    private function nodo(TestResponse $respuesta, string $consulta): DOMElement
    {
        $nodo = $this->documento($respuesta)->query($consulta)->item(0);

        $this->assertInstanceOf(DOMElement::class, $nodo, 'No se encontró el elemento: '.$consulta);

        return $nodo;
    }

    private function cuenta(TestResponse $respuesta, string $consulta): int
    {
        return $this->documento($respuesta)->query($consulta)->length;
    }

    private function documento(TestResponse $respuesta): DOMXPath
    {
        $documento = new DOMDocument;
        libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8">'.$respuesta->getContent());
        libxml_clear_errors();

        return new DOMXPath($documento);
    }
}
