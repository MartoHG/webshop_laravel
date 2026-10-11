<?php

namespace Tests\Feature;

use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_de_ejemplo_no_corre_en_produccion(): void
    {
        $this->app->instance('env', 'production');

        try {
            app(DemoSeeder::class)->run();
        } catch (RuntimeException $excepcion) {
            $this->assertStringContainsString('solo puede correr en los entornos local o testing', $excepcion->getMessage());
        }

        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('productos', 0);
    }
}
