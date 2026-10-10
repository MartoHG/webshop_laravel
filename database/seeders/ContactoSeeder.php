<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ContactoSeeder extends Seeder
{
    /**
     * Carga solo la dirección y el usuario de Instagram. El WhatsApp del negocio lo carga el administrador.
     */
    public function run(): void
    {
        if (Configuracion::query()->where('clave', 'like', 'contacto.%')->exists()) {
            return;
        }

        Configuracion::query()->create(['clave' => 'contacto.direccion', 'valor' => 'Pringles 2136, Olavarría']);
        Configuracion::query()->create(['clave' => 'contacto.instagram', 'valor' => 'elrefugio.olav']);
    }
}
