<?php

namespace Tests\Feature;

use App\Models\ArchivoImagenProducto;
use App\Models\Categoria;
use App\Models\Configuracion;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\VarianteProducto;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VistasCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_en_html_muestra_productos_y_solo_las_categorias_activas(): void
    {
        $this->configurarAjuste();
        $sillas = $this->crearCategoria('Sillas', 'sillas');
        $mesas = $this->crearCategoria('Mesas', 'mesas');
        $this->crearCategoria('Reposeras ocultas', 'reposeras-ocultas', activo: false);
        $this->publicar($this->crearProducto('Silla Kansas', [$sillas]));
        $this->publicar($this->crearProducto('Mesa Aruba', [$mesas]));

        $this->get('/catalogo')
            ->assertOk()
            ->assertViewIs('tienda.catalogo')
            ->assertSee('<title>Muebles · El Refugio</title>', false)
            ->assertSee('id="buscar"', false)
            ->assertSee('Buscar muebles')
            ->assertSee('Todos')
            ->assertSee('Sillas')
            ->assertSee('Mesas')
            ->assertDontSee('Reposeras ocultas')
            ->assertSee('Silla Kansas')
            ->assertSee('Mesa Aruba');
    }

    public function test_filtrar_por_categoria_cambia_el_titulo_la_categoria_marcada_y_los_resultados(): void
    {
        $this->configurarAjuste();
        $sillas = $this->crearCategoria('Sillas', 'sillas');
        $mesas = $this->crearCategoria('Mesas', 'mesas');
        $this->publicar($this->crearProducto('Silla Kansas', [$sillas]));
        $this->publicar($this->crearProducto('Mesa Aruba', [$mesas]));

        $respuesta = $this->get('/catalogo?categoria=sillas')
            ->assertOk()
            ->assertSee('<title>Sillas · El Refugio</title>', false)
            ->assertSee('Silla Kansas')
            ->assertDontSee('Mesa Aruba');

        $this->assertSame('Sillas', $this->textoDelNodo($respuesta, '//nav[@aria-label="Categorías"]//a[@aria-current="page"]'));
        $this->assertNull($this->nodo($respuesta, '//nav[@aria-label="Categorías"]//a[text()="Todos"]')->attributes->getNamedItem('aria-current'));
    }

    public function test_buscar_cambia_el_titulo_y_filtra_los_resultados(): void
    {
        $this->configurarAjuste();
        $this->publicar($this->crearProducto('Silla Kansas'));
        $this->publicar($this->crearProducto('Mesa Aruba'));

        $this->get('/catalogo?buscar=Kansas')
            ->assertOk()
            ->assertSee('<title>Resultados para «Kansas» · El Refugio</title>', false)
            ->assertSee('Silla Kansas')
            ->assertDontSee('Mesa Aruba');
    }

    public function test_los_agotados_aparecen_en_el_listado_marcados_como_agotados(): void
    {
        $this->configurarAjuste();
        $this->publicar($this->crearProducto('Silla Plegable', disponible: false));

        $this->get('/catalogo?buscar=Plegable')
            ->assertOk()
            ->assertSee('Silla Plegable')
            ->assertSee('Agotado');
    }

    public function test_la_paginacion_usa_textos_en_espanol_y_conserva_la_busqueda(): void
    {
        $this->configurarAjuste();
        $reposeras = $this->crearCategoria('Reposeras', 'reposeras');

        foreach (range(1, 13) as $numero) {
            $this->publicar($this->crearProducto('Reposera '.str_pad((string) $numero, 2, '0', STR_PAD_LEFT), [$reposeras]));
        }

        $this->get('/catalogo?buscar=Reposera')
            ->assertOk()
            ->assertSee('Página 1 de 2')
            ->assertSee('Anterior')
            ->assertSee('Siguiente')
            ->assertSee('buscar=Reposera&amp;page=2', false);

        $this->get('/catalogo?buscar=Reposera&page=2')
            ->assertOk()
            ->assertSee('Página 2 de 2')
            ->assertSee('buscar=Reposera&amp;page=1', false);
    }

    public function test_sin_resultados_muestra_un_mensaje_amable_con_enlace_a_todos_los_muebles(): void
    {
        $this->configurarAjuste();

        $this->get('/catalogo?buscar=zapatillas')
            ->assertOk()
            ->assertSee('No encontramos muebles que coincidan con «zapatillas».')
            ->assertSee('Ver todos los muebles')
            ->assertSee('href="'.route('catalogo.index').'"', false);
    }

    public function test_la_pagina_del_producto_muestra_colores_y_deshabilita_el_agotado(): void
    {
        $this->configurarAjuste();
        $sillas = $this->crearCategoria('Sillas', 'sillas');
        $producto = $this->crearProducto('Sillón Gran California', [$sillas], descripcion: 'Sillón de plástico. Resiste 120 kilos.');
        $blanco = $this->agregarVariante($producto, ['color' => 'Blanco', 'precio_base' => '79000.00']);
        $negro = $this->agregarVariante($producto, ['color' => 'Negro', 'precio_base' => '81000.00']);
        $azul = $this->agregarVariante($producto, ['color' => 'Azul', 'precio_base' => '90000.00', 'disponible' => false]);

        $respuesta = $this->get(route('catalogo.mostrar', $producto->slug))
            ->assertOk()
            ->assertViewIs('tienda.producto')
            ->assertSee('<title>Sillón Gran California · El Refugio</title>', false)
            ->assertSee('<meta name="description" content="Sillón de plástico.">', false)
            ->assertSee('Elegí el color')
            ->assertSee('Desde')
            ->assertSee('$79.000')
            ->assertSee('Sobre este producto')
            ->assertSee('href="'.route('catalogo.index', ['categoria' => 'sillas']).'"', false);

        $this->assertFalse($this->nodo($respuesta, '//input[@value="'.$blanco->id.'"]')->hasAttribute('disabled'));
        $this->assertFalse($this->nodo($respuesta, '//input[@value="'.$negro->id.'"]')->hasAttribute('disabled'));
        $this->assertTrue($this->nodo($respuesta, '//input[@value="'.$azul->id.'"]')->hasAttribute('disabled'));
        $this->assertSame('Agotado', $this->textoDelNodo($respuesta, '//label[.//input[@value="'.$azul->id.'"]]//span[normalize-space()="Agotado"]'));
    }

    public function test_con_una_variante_elegida_el_precio_y_las_fotos_son_los_de_esa_variante(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Sillón Máster');
        $blanco = $this->agregarVariante($producto, ['color' => 'Blanco', 'precio_base' => '69000.00']);
        $negro = $this->agregarVariante($producto, ['color' => 'Negro', 'precio_base' => '72000.00']);
        $this->agregarImagen($producto, $negro, 'productos/master-negro.jpg');

        $respuesta = $this->get(route('catalogo.mostrar', $producto->slug).'?variante='.$negro->id)
            ->assertOk()
            ->assertSee('$72.000')
            ->assertDontSee('Desde')
            ->assertSee(Storage::disk('public')->url('productos/master-negro.jpg'));

        $this->assertNotNull($this->nodo($respuesta, '//input[@value="'.$negro->id.'"]')->attributes->getNamedItem('checked'));
    }

    public function test_el_formulario_de_compra_envia_la_variante_y_la_cantidad(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Reposera Mascardi');
        $variante = $this->agregarVariante($producto, ['precio_base' => '45000.00']);

        $respuesta = $this->get(route('catalogo.mostrar', $producto->slug))->assertOk();
        $formulario = '//form[@action="'.route('carrito.items.agregar').'"]';

        $this->assertSame((string) $variante->id, $this->nodo($respuesta, $formulario.'//input[@name="variante_producto_id"]')->getAttribute('value'));
        $this->assertSame('1', $this->nodo($respuesta, $formulario.'//input[@name="cantidad"]')->getAttribute('value'));
        $this->assertSame('1', $this->nodo($respuesta, $formulario.'//input[@name="cantidad"]')->getAttribute('min'));
        $this->assertSame('99', $this->nodo($respuesta, $formulario.'//input[@name="cantidad"]')->getAttribute('max'));
        $this->assertNotNull($this->nodo($respuesta, $formulario.'//button[@data-paso="1"]'));
        $this->assertFalse($this->nodo($respuesta, $formulario.'//button[@type="submit"]')->hasAttribute('disabled'));
    }

    public function test_el_formulario_de_compra_con_colores_envia_la_variante_elegida(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Juego de living');
        $blanco = $this->agregarVariante($producto, ['color' => 'Blanco']);
        $this->agregarVariante($producto, ['color' => 'Naranja']);

        $respuesta = $this->get(route('catalogo.mostrar', $producto->slug))->assertOk()->assertSee('Elegí el color');
        $formulario = '//form[@action="'.route('carrito.items.agregar').'"]';

        $this->assertSame((string) $blanco->id, $this->nodo($respuesta, $formulario.'//input[@type="radio"][@name="variante_producto_id"][@required]')->getAttribute('value'));
    }

    public function test_un_producto_agotado_no_permite_agregar_al_carrito(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Silla Plegable', disponible: false);
        $this->agregarVariante($producto);

        $respuesta = $this->get(route('catalogo.mostrar', $producto->slug))
            ->assertOk()
            ->assertSee('Está agotado por ahora');

        $this->assertTrue($this->nodo($respuesta, '//form//button[@type="submit"]')->hasAttribute('disabled'));
    }

    public function test_una_peticion_htmx_devuelve_solo_el_fragmento_de_la_variante(): void
    {
        $this->configurarAjuste();
        $producto = $this->crearProducto('Sillón Máster');
        $blanco = $this->agregarVariante($producto, ['color' => 'Blanco', 'precio_base' => '69000.00']);
        $negro = $this->agregarVariante($producto, ['color' => 'Negro', 'precio_base' => '72000.00']);
        $this->agregarImagen($producto, $negro, 'productos/master-negro.jpg');
        $urlGeneral = Storage::disk('public')->url('productos/'.$producto->slug.'-general.jpg');

        $this->withHeaders(['HX-Request' => 'true'])
            ->get(route('catalogo.mostrar', $producto->slug).'?variante='.$negro->id)
            ->assertOk()
            ->assertViewIs('tienda.partials.producto-variante')
            ->assertSee(Storage::disk('public')->url('productos/master-negro.jpg'))
            ->assertSee('$72.000')
            ->assertDontSee('<!DOCTYPE html>', false)
            ->assertDontSee('Saltar al contenido')
            ->assertDontSee('Elegí el color');

        $this->withHeaders(['HX-Request' => 'true'])
            ->get(route('catalogo.mostrar', $producto->slug).'?variante='.$blanco->id)
            ->assertOk()
            ->assertSee($urlGeneral, false)
            ->assertSee('$69.000');
    }

    public function test_un_producto_inexistente_o_inactivo_muestra_la_pagina_404_en_espanol(): void
    {
        $this->configurarAjuste();
        $inactivo = $this->crearProducto('Silla Oculta');
        $this->agregarVariante($inactivo);
        $inactivo->forceFill(['activo' => false])->save();

        $this->get('/catalogo/no-existe')
            ->assertNotFound()
            ->assertSee('No encontramos lo que buscabas')
            ->assertSee('Ver muebles');

        $this->get(route('catalogo.mostrar', $inactivo->slug))
            ->assertNotFound()
            ->assertSee('No encontramos lo que buscabas');
    }

    public function test_sin_ajuste_configurado_la_tienda_html_muestra_que_se_esta_preparando(): void
    {
        $this->get('/catalogo')
            ->assertStatus(503)
            ->assertSee('La tienda se está preparando');

        $this->get('/catalogo/cualquier-producto')
            ->assertStatus(503)
            ->assertSee('La tienda se está preparando');
    }

    public function test_los_endpoints_json_del_catalogo_siguen_respondiendo_igual(): void
    {
        $this->configurarAjuste();
        $sillas = $this->crearCategoria('Sillas', 'sillas');
        $producto = $this->crearProducto('Silla Kansas', [$sillas]);
        $this->agregarVariante($producto, ['precio_base' => '45000.00']);

        $this->getJson('/catalogo')
            ->assertOk()
            ->assertJsonPath('productos.0.nombre', 'Silla Kansas');

        $this->getJson(route('catalogo.mostrar', $producto->slug))
            ->assertOk()
            ->assertJsonPath('producto.variantes.0.precio_publicado', '45000.00');

        $this->getJson('/catalogo/categorias')
            ->assertOk()
            ->assertJsonPath('categorias.0.slug', 'sillas');
    }

    public function test_el_retiro_en_el_local_usa_la_direccion_del_negocio(): void
    {
        $this->configurarAjuste();
        Configuracion::query()->create(['clave' => 'contacto.direccion', 'valor' => 'Pringles 2136, Olavarría']);
        $producto = $this->publicar($this->crearProducto('Reposera Mascardi'));

        $this->get(route('catalogo.mostrar', $producto->slug))
            ->assertOk()
            ->assertSee('Retiro en el local.')
            ->assertSee('Pringles 2136, Olavarría');
    }

    private function configurarAjuste(string $porcentaje = '0'): void
    {
        Configuracion::query()->updateOrCreate(
            ['clave' => 'ajuste_ecommerce_porcentaje'],
            ['valor' => $porcentaje],
        );
    }

    private function crearCategoria(string $nombre, string $slug, bool $activo = true): Categoria
    {
        return Categoria::factory()->create(['nombre' => $nombre, 'slug' => $slug, 'activo' => $activo]);
    }

    /**
     * @param  list<Categoria>  $categorias
     */
    private function crearProducto(string $nombre, array $categorias = [], bool $disponible = true, ?string $descripcion = null): Producto
    {
        $producto = Producto::factory()->create([
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => $descripcion,
            'activo' => true,
            'disponible' => $disponible,
        ]);

        $categorias = $categorias === []
            ? [Categoria::query()->firstOrCreate(['slug' => 'general'], ['nombre' => 'General', 'activo' => true])]
            : $categorias;

        $producto->categorias()->attach(array_map(fn (Categoria $categoria): int => $categoria->id, $categorias));
        $this->agregarImagen($producto);

        return $producto;
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
     * El catálogo solo lista productos con al menos una variante activa.
     */
    private function publicar(Producto $producto): Producto
    {
        $this->agregarVariante($producto);

        return $producto;
    }

    private function agregarImagen(Producto $producto, ?VarianteProducto $variante = null, ?string $ruta = null): void
    {
        $ruta ??= 'productos/'.$producto->slug.'-'.($variante?->id ?? 'general').'.jpg';

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
            'variante_producto_id' => $variante?->id,
            'archivo_imagen_producto_id' => $archivo->id,
            'ruta' => $archivo->ruta,
            'ruta_miniatura' => $archivo->ruta_miniatura,
            'estado' => 'listo',
            'orden' => 0,
            'es_principal' => true,
        ]);
    }

    private function nodo(TestResponse $respuesta, string $consulta): \DOMElement
    {
        $nodo = $this->documento($respuesta)->query($consulta)->item(0);

        $this->assertInstanceOf(\DOMElement::class, $nodo, 'No se encontró el elemento: '.$consulta);

        return $nodo;
    }

    private function textoDelNodo(TestResponse $respuesta, string $consulta): string
    {
        return trim($this->nodo($respuesta, $consulta)->textContent);
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
