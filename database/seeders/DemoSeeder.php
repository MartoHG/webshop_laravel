<?php

namespace Database\Seeders;

use App\Enums\TipoMedioPago;
use App\Models\Categoria;
use App\Models\ImagenProducto;
use App\Models\Producto;
use App\Models\ReglaPrecioPago;
use App\Models\Usuario;
use App\Models\VarianteProducto;
use App\Servicios\Categorias\CategoriaImagenService;
use App\Servicios\Categorias\CategoriaService;
use App\Servicios\Contenido\ContactoService;
use App\Servicios\Contenido\PortadaService;
use App\Servicios\Imagenes\ImagenProductoService;
use App\Servicios\Precios\AjusteEcommerceService;
use App\Servicios\Precios\ReglaPrecioPagoService;
use App\Servicios\Productos\ProductoService;
use App\Servicios\Productos\VarianteProductoService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DemoSeeder extends Seeder
{
    private const string DIRECTORIO_FOTOS = 'app/demo';

    private const string PREFIJO_SKU = 'DEMO-';

    public function __construct(
        private AjusteEcommerceService $ajusteEcommerce,
        private ReglaPrecioPagoService $reglasPrecioPago,
        private CategoriaService $categorias,
        private CategoriaImagenService $imagenesCategorias,
        private ProductoService $productos,
        private VarianteProductoService $variantes,
        private ImagenProductoService $imagenesProducto,
        private PortadaService $portada,
        private ContactoService $contacto,
    ) {}

    /**
     * Carga datos de demostración para probar la tienda en local. No duplica categorías (por slug), productos
     * (por SKU DEMO-) ni usuarios (por email). Todo pasa por los servicios de negocio, sin insertar filas a mano.
     *
     * @throws RuntimeException Si el entorno no es local ni testing.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('DemoSeeder solo puede correr en los entornos local o testing. Entorno actual: '.app()->environment().'.');
        }

        config(['queue.default' => 'sync']);

        $this->call([CategoriaSeeder::class, ReglaPrecioPagoSeeder::class]);

        $this->configurarPrecios();
        $categorias = $this->cargarCategorias();
        $this->cargarProductos($categorias);
        $this->cargarPortada();
        $this->cargarContacto();
        $this->crearUsuarios();
        $this->mostrarResumen();
    }

    private function configurarPrecios(): void
    {
        $this->ajusteEcommerce->guardarPorcentaje('0');

        foreach ($this->reglasDemo() as [$tipo, $cuotas, $datos]) {
            $regla = ReglaPrecioPago::query()
                ->where('tipo_medio_pago', $tipo->value)
                ->where('cuotas', $cuotas)
                ->firstOrFail();

            $this->reglasPrecioPago->actualizar($regla, $datos);
        }
    }

    /**
     * @return array<string, Categoria> Categorías por slug, incluida Ofertas.
     */
    private function cargarCategorias(): array
    {
        $ofertas = Categoria::query()->where('clave_sistema', 'ofertas')->firstOrFail();
        $categorias = ['ofertas' => $this->categorias->cambiarEstado($ofertas->id, true)];

        foreach ($this->categoriasDemo() as $datos) {
            $categoria = Categoria::query()->where('slug', $datos['slug'])->first()
                ?? $this->categorias->crear(['nombre' => $datos['nombre'], 'slug' => $datos['slug']]);

            $categorias[$datos['slug']] = $this->categorias->cambiarEstado($categoria->id, true);
        }

        $this->categorias->ordenar(array_map(
            fn (array $datos): int => $categorias[$datos['slug']]->id,
            $this->categoriasDemo(),
        ));

        foreach ($this->categoriasDemo() as $datos) {
            $categoria = $categorias[$datos['slug']];

            $this->imagenesCategorias->marcarDestacada($categoria, true);
            $this->cargarFotoCategoria($categoria, $datos['foto']);
        }

        return $categorias;
    }

    private function cargarFotoCategoria(Categoria $categoria, string $foto): void
    {
        $categoria->loadMissing('imagenContenido');

        if ($categoria->imagenContenido?->estado === 'listo') {
            return;
        }

        $this->cargarFoto(
            $foto,
            "cargar la foto de la categoría {$categoria->nombre}",
            fn (UploadedFile $archivo): mixed => $this->imagenesCategorias->cambiarImagen($categoria, $archivo),
        );
    }

    /**
     * @param  array<string, Categoria>  $categorias
     */
    private function cargarProductos(array $categorias): void
    {
        foreach ($this->productosDemo() as $datos) {
            $producto = $this->productoExistente($datos) ?? $this->crearProducto($datos, $categorias);

            $this->activarProducto($producto, $datos);
        }
    }

    /**
     * @param  array{nombre: string, descripcion: string, categorias: list<string>, foto_general: string, variantes: list<array{color: string, sku: string, precio: string, foto: ?string, disponible: bool}>}  $datos
     */
    private function productoExistente(array $datos): ?Producto
    {
        $variante = VarianteProducto::query()->where('sku', $datos['variantes'][0]['sku'])->first();

        return $variante?->producto->load('variantes');
    }

    /**
     * @param  array{nombre: string, descripcion: string, categorias: list<string>, foto_general: string, variantes: list<array{color: string, sku: string, precio: string, foto: ?string, disponible: bool}>}  $datos
     * @param  array<string, Categoria>  $categorias
     */
    private function crearProducto(array $datos, array $categorias): Producto
    {
        $producto = $this->productos->crear([
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'variantes' => array_map(
                fn (array $variante): array => [
                    'color' => $variante['color'],
                    'sku' => $variante['sku'],
                    'precio_base' => $variante['precio'],
                ],
                $datos['variantes'],
            ),
        ]);

        $this->categorias->sincronizarProducto(
            $producto->id,
            array_map(fn (string $slug): int => $categorias[$slug]->id, $datos['categorias']),
        );

        $fotosSubidas = [];
        $this->cargarFotoProducto($producto, null, $datos['foto_general'], $fotosSubidas);

        foreach ($datos['variantes'] as $variante) {
            if ($variante['foto'] !== null) {
                $this->cargarFotoProducto(
                    $producto,
                    $producto->variantes->firstWhere('sku', $variante['sku']),
                    $variante['foto'],
                    $fotosSubidas,
                );
            }
        }

        return $producto;
    }

    /**
     * Una foto se sube una sola vez; en las demás asignaciones del mismo producto se reutiliza el archivo ya procesado.
     *
     * @param  array<string, int>  $fotosSubidas  ID del archivo procesado, por nombre de foto.
     */
    private function cargarFotoProducto(Producto $producto, ?VarianteProducto $variante, string $foto, array &$fotosSubidas): void
    {
        $ambito = [
            'producto_id' => $producto->id,
            'variante_producto_id' => $variante?->id,
            'es_principal' => true,
        ];
        $descripcion = "cargar la foto {$foto} de {$producto->nombre}".($variante === null ? '' : " ({$variante->color})");

        if (isset($fotosSubidas[$foto])) {
            $this->intentar($descripcion, fn () => $this->imagenesProducto->asignar(
                $ambito + ['archivo_imagen_producto_id' => $fotosSubidas[$foto]],
            ));

            return;
        }

        $this->cargarFoto($foto, $descripcion, function (UploadedFile $archivo) use ($ambito, $foto, &$fotosSubidas): void {
            $fotosSubidas[$foto] = $this->imagenesProducto->subir($archivo, $ambito)['archivo_imagen_producto_id'];
        });
    }

    /**
     * Las variantes se activan antes que el producto, porque el producto exige al menos una variante activa.
     *
     * @param  array{nombre: string, descripcion: string, categorias: list<string>, foto_general: string, variantes: list<array{color: string, sku: string, precio: string, foto: ?string, disponible: bool}>}  $datos
     */
    private function activarProducto(Producto $producto, array $datos): void
    {
        $this->intentar("activar «{$datos['nombre']}»", function () use ($producto, $datos): void {
            foreach ($datos['variantes'] as $variante) {
                $modelo = $producto->variantes->firstWhere('sku', $variante['sku']);

                if ($modelo !== null) {
                    $this->variantes->actualizarEstado($producto, $modelo, [
                        'activo' => true,
                        'disponible' => $variante['disponible'],
                    ]);
                }
            }

            $this->productos->actualizarEstado($producto, ['activo' => true, 'disponible' => true]);
        });
    }

    private function cargarPortada(): void
    {
        $this->portada->actualizarTextos([
            'titulo' => 'Muebles de jardín para disfrutar el verano',
            'subtitulo' => 'Sillas, sillones, mesas y reposeras. Envíos a todo el país o retiro en el local de Olavarría.',
            'boton_texto' => 'Ver muebles',
            'boton_destino' => '/catalogo',
        ]);

        if (($this->portada->obtenerParaAdministracion()['imagen']['estado'] ?? null) === 'listo') {
            return;
        }

        $this->cargarFoto(
            'portada-reposeras-pileta.jpg',
            'cargar la imagen de la portada',
            fn (UploadedFile $archivo): mixed => $this->portada->cambiarImagen($archivo),
        );
    }

    /**
     * El WhatsApp no se fija en el código: sale de DEMO_WHATSAPP. Si no está definido, se conserva el que ya hubiera.
     */
    private function cargarContacto(): void
    {
        $tramos = [['desde' => '09:00', 'hasta' => '13:00'], ['desde' => '16:00', 'hasta' => '20:00']];

        $this->contacto->actualizar([
            'whatsapp' => env('DEMO_WHATSAPP') ?: $this->contacto->obtener()['whatsapp'],
            'email' => 'tienda@example.com',
            'direccion' => 'Pringles 2136, Olavarría',
            'instagram' => 'elrefugio.olav',
            'facebook' => null,
            'horarios' => [
                'lunes' => $tramos,
                'martes' => $tramos,
                'miercoles' => $tramos,
                'jueves' => $tramos,
                'viernes' => $tramos,
                'sabado' => [['desde' => '09:00', 'hasta' => '13:00']],
                'domingo' => [],
            ],
        ]);
    }

    private function crearUsuarios(): void
    {
        $clave = env('DEMO_PASSWORD', 'demo-local-1234');

        $this->crearUsuario([
            'nombre' => 'Admin',
            'apellido' => 'Demo',
            'dni' => '20000001',
            'email' => 'admin@demo.test',
            'telefono' => '2284 15 000000',
            'rol' => 'administrador',
        ], $clave);

        $this->crearUsuario([
            'nombre' => 'Cliente',
            'apellido' => 'Demo',
            'dni' => '30123456',
            'email' => 'cliente@demo.test',
            'telefono' => '2284 15 123456',
            'rol' => 'cliente',
        ], $clave);
    }

    /**
     * La verificación de email no es asignable en masa, por eso se escribe con forceFill.
     *
     * @param  array{nombre: string, apellido: string, dni: string, email: string, telefono: string, rol: string}  $datos
     */
    private function crearUsuario(array $datos, string $clave): void
    {
        $usuario = Usuario::query()->firstOrNew(['email' => $datos['email']]);

        if ($usuario->exists) {
            return;
        }

        $usuario->fill([...$datos, 'password' => $clave, 'activo' => true]);
        $usuario->forceFill(['email_verified_at' => now()])->save();
    }

    private function mostrarResumen(): void
    {
        $productosDemo = Producto::query()
            ->whereHas('variantes', fn (Builder $consulta) => $consulta->where('sku', 'like', self::PREFIJO_SKU.'%'))
            ->get(['id', 'activo']);

        $slugsCategorias = [...array_column($this->categoriasDemo(), 'slug'), 'ofertas'];

        $this->command?->table(['Dato', 'Cantidad'], [
            ['Categorías (incluida Ofertas)', Categoria::query()->whereIn('slug', $slugsCategorias)->count()],
            ['Productos demo', $productosDemo->count()],
            ['Productos activos', $productosDemo->where('activo', true)->count()],
            ['Variantes demo', VarianteProducto::query()->where('sku', 'like', self::PREFIJO_SKU.'%')->count()],
            ['Fotos de producto procesadas', ImagenProducto::query()->whereIn('producto_id', $productosDemo->pluck('id'))->where('estado', 'listo')->count()],
            ['Usuarios demo', Usuario::query()->whereIn('email', ['admin@demo.test', 'cliente@demo.test'])->count()],
        ]);
        $this->command?->line('Ingreso local: admin@demo.test (administrador) y cliente@demo.test (cliente).');
    }

    /**
     * Una foto faltante no detiene la carga: se avisa y se omite.
     *
     * @param  Closure(UploadedFile): mixed  $accion
     */
    private function cargarFoto(string $foto, string $descripcion, Closure $accion): void
    {
        $ruta = storage_path(self::DIRECTORIO_FOTOS.'/'.$foto);

        if (! is_file($ruta)) {
            $this->advertir("No existe la foto demo {$foto}: se omite {$descripcion}.");

            return;
        }

        $this->intentar($descripcion, fn () => $accion(new UploadedFile($ruta, $foto, 'image/jpeg', null, true)));
    }

    private function intentar(string $descripcion, Closure $accion): void
    {
        try {
            $accion();
        } catch (ValidationException|RuntimeException $excepcion) {
            $this->advertir("No se pudo {$descripcion}: ".$this->mensajeDe($excepcion));
        }
    }

    private function mensajeDe(Throwable $excepcion): string
    {
        return $excepcion instanceof ValidationException
            ? implode(' ', Arr::flatten($excepcion->errors()))
            : $excepcion->getMessage();
    }

    private function advertir(string $mensaje): void
    {
        $this->command?->warn($mensaje);
    }

    /**
     * @return list<array{nombre: string, slug: string, foto: string}>
     */
    private function categoriasDemo(): array
    {
        return [
            ['nombre' => 'Juegos de living', 'slug' => 'juegos-de-living', 'foto' => 'living-ratan-pasto.jpg'],
            ['nombre' => 'Reposeras', 'slug' => 'reposeras', 'foto' => 'reposeras-colores.jpg'],
            ['nombre' => 'Sillas', 'slug' => 'sillas', 'foto' => 'silla-ratan.jpg'],
            ['nombre' => 'Sillones', 'slug' => 'sillones', 'foto' => 'sillon-ratan.jpg'],
        ];
    }

    /**
     * @return list<array{nombre: string, descripcion: string, categorias: list<string>, foto_general: string, variantes: list<array{color: string, sku: string, precio: string, foto: ?string, disponible: bool}>}>
     */
    private function productosDemo(): array
    {
        return [
            [
                'nombre' => 'Juego de living símil ratán',
                'descripcion' => '[Producto de ejemplo] Juego de living de ratán sintético, resistente a la intemperie y pensado para terrazas y jardines.',
                'categorias' => ['juegos-de-living', 'ofertas'],
                'foto_general' => 'living-ratan-pasto.jpg',
                'variantes' => [
                    ['color' => 'Grafito', 'sku' => 'DEMO-JUEGO-LIVING-GRAFITO', 'precio' => '299000', 'foto' => null, 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Sillón Máster reforzado',
                'descripcion' => '[Producto de ejemplo] Sillón de estructura reforzada y respaldo cómodo, pensado para un uso intensivo al aire libre.',
                'categorias' => ['sillones', 'ofertas'],
                'foto_general' => 'sillon-master.jpg',
                'variantes' => [
                    ['color' => 'Negro', 'sku' => 'DEMO-SILLON-MASTER-NEGRO', 'precio' => '69000', 'foto' => null, 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Silla Aruba Mascardi (pack x 4)',
                'descripcion' => '[Producto de ejemplo] Pack de cuatro sillas Aruba Mascardi en color grafito, livianas y fáciles de guardar.',
                'categorias' => ['sillas', 'ofertas'],
                'foto_general' => 'silla-aruba.jpg',
                'variantes' => [
                    ['color' => 'Grafito', 'sku' => 'DEMO-SILLA-ARUBA-PACK4-GRAFITO', 'precio' => '239000', 'foto' => null, 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Sillón Gran California',
                'descripcion' => '[Producto de ejemplo] Sillón Gran California de gran tamaño, disponible en blanco y azul para galerías y living exterior.',
                'categorias' => ['sillones', 'ofertas'],
                'foto_general' => 'juego-california-blanco.jpg',
                'variantes' => [
                    ['color' => 'Blanco', 'sku' => 'DEMO-GRAN-CALIFORNIA-BLANCO', 'precio' => '79000', 'foto' => 'juego-california-blanco.jpg', 'disponible' => true],
                    ['color' => 'Azul', 'sku' => 'DEMO-GRAN-CALIFORNIA-AZUL', 'precio' => '79000', 'foto' => 'sillon-california-azul.jpg', 'disponible' => false],
                ],
            ],
            [
                'nombre' => 'Sillón símil ratán',
                'descripcion' => '[Producto de ejemplo] Sillón de ratán sintético en color grafito, con tejido trenzado y estructura liviana.',
                'categorias' => ['sillones'],
                'foto_general' => 'sillon-ratan.jpg',
                'variantes' => [
                    ['color' => 'Grafito', 'sku' => 'DEMO-SILLON-RATAN-GRAFITO', 'precio' => '85000', 'foto' => null, 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Silla símil ratán con apoyabrazos',
                'descripcion' => '[Producto de ejemplo] Silla de ratán sintético con apoyabrazos en color grafito, cómoda para la mesa o para descansar.',
                'categorias' => ['sillas'],
                'foto_general' => 'silla-ratan.jpg',
                'variantes' => [
                    ['color' => 'Grafito', 'sku' => 'DEMO-SILLA-RATAN-GRAFITO', 'precio' => '45000', 'foto' => null, 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Reposera rotomoldeada',
                'descripcion' => '[Producto de ejemplo] Reposera rotomoldeada de plástico resistente, disponible en naranja, verde y amarillo.',
                'categorias' => ['reposeras', 'ofertas'],
                'foto_general' => 'reposeras-colores.jpg',
                'variantes' => [
                    ['color' => 'Naranja', 'sku' => 'DEMO-REPOSERA-NARANJA', 'precio' => '189000', 'foto' => 'reposeras-colores.jpg', 'disponible' => true],
                    ['color' => 'Verde', 'sku' => 'DEMO-REPOSERA-VERDE', 'precio' => '189000', 'foto' => 'reposeras-colores.jpg', 'disponible' => true],
                    ['color' => 'Amarillo', 'sku' => 'DEMO-REPOSERA-AMARILLO', 'precio' => '189000', 'foto' => 'reposeras-colores.jpg', 'disponible' => true],
                ],
            ],
            [
                'nombre' => 'Reposera rotomoldeada blanca',
                'descripcion' => '[Producto de ejemplo] Reposera rotomoldeada en color blanco, pensada para la pileta y el jardín.',
                'categorias' => ['reposeras'],
                'foto_general' => 'portada-reposeras-pileta.jpg',
                'variantes' => [
                    ['color' => 'Blanco', 'sku' => 'DEMO-REPOSERA-BLANCA', 'precio' => '189000', 'foto' => null, 'disponible' => true],
                ],
            ],
        ];
    }

    /**
     * @return list<array{0: TipoMedioPago, 1: int, 2: array{porcentaje?: string, activo: bool}}>
     */
    private function reglasDemo(): array
    {
        return [
            [TipoMedioPago::Debito, 1, ['porcentaje' => '3', 'activo' => true]],
            [TipoMedioPago::DineroEnCuenta, 1, ['porcentaje' => '5', 'activo' => true]],
            [TipoMedioPago::Credito, 1, ['porcentaje' => '6', 'activo' => true]],
            [TipoMedioPago::Credito, 3, ['porcentaje' => '15', 'activo' => true]],
            [TipoMedioPago::Credito, 6, ['porcentaje' => '25', 'activo' => true]],
            [TipoMedioPago::Credito, 12, ['activo' => false]],
        ];
    }
}
