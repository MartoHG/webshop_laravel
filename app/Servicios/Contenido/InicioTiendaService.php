<?php

namespace App\Servicios\Contenido;

use App\Models\BeneficioHome;
use App\Models\Categoria;
use App\Servicios\Catalogo\CatalogoService;

class InicioTiendaService
{
    private const int MAXIMO_OFERTAS = 8;

    public function __construct(
        private PortadaService $portada,
        private CatalogoService $catalogo,
        private ContactoService $contacto,
    ) {}

    /**
     * Datos de la home pública. Las ofertas usan el mismo criterio de visibilidad del catálogo en modo inicio:
     * no incluyen productos agotados ni no disponibles.
     *
     * @return array{portada: ?array<string, mixed>, beneficios: list<array{icono: string, texto: string}>, categorias: list<array{nombre: string, slug: string, imagen_url: ?string, imagen_miniatura_url: ?string}>, ofertas: list<array<string, mixed>>, contacto: array<string, mixed>}
     */
    public function componer(): array
    {
        return [
            'portada' => $this->portada->obtener(),
            'beneficios' => BeneficioHome::query()
                ->where('activo', true)
                ->orderBy('orden')
                ->orderBy('id')
                ->get()
                ->map(fn (BeneficioHome $beneficio): array => [
                    'icono' => $beneficio->icono->value,
                    'texto' => $beneficio->texto,
                ])
                ->values()
                ->all(),
            'categorias' => Categoria::query()
                ->with('imagenContenido')
                ->where('activo', true)
                ->where('destacada_en_home', true)
                ->orderBy('orden')
                ->orderBy('nombre')
                ->get()
                ->map(fn (Categoria $categoria): array => [
                    'nombre' => $categoria->nombre,
                    'slug' => $categoria->slug,
                    'imagen_url' => $categoria->imagen_url,
                    'imagen_miniatura_url' => $categoria->imagen_miniatura_url,
                ])
                ->values()
                ->all(),
            'ofertas' => $this->ofertas(),
            'contacto' => $this->contacto->publico(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function ofertas(): array
    {
        $ofertas = Categoria::query()
            ->where('clave_sistema', 'ofertas')
            ->where('activo', true)
            ->first();

        if ($ofertas === null || ! $this->catalogo->estaConfigurado()) {
            return [];
        }

        return $this->catalogo->listar([
            'contexto' => 'inicio',
            'categoria' => $ofertas->slug,
            'por_pagina' => self::MAXIMO_OFERTAS,
        ])->items();
    }
}
