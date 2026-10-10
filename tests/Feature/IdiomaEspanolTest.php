<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Usuario;
use App\Notifications\Pedidos\PedidoCreado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Tests\TestCase;

class IdiomaEspanolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setLocale('es');
    }

    public function test_sin_sesion_iniciada_responde_en_espanol(): void
    {
        $this->getJson('/checkout')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Iniciá sesión para continuar.']);
    }

    public function test_checkout_sin_localidad_responde_el_error_en_espanol(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs($usuario)
            ->postJson('/checkout', [
                'provincia' => 'cordoba',
                'localidad' => '',
                'codigo_postal' => '5800',
                'direccion' => 'Av. Italia',
                'numero' => '123',
                'huella' => str_repeat('a', 64),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El campo localidad es obligatorio.')
            ->assertJsonPath('errors.localidad.0', 'El campo localidad es obligatorio.');
    }

    public function test_mail_de_pedido_creado_usa_el_layout_en_espanol(): void
    {
        $pedido = Pedido::factory()->create();

        $html = (new PedidoCreado($pedido))->toMail(new AnonymousNotifiable)->render();

        $this->assertStringContainsString('Saludos,', $html);
        $this->assertStringContainsString('Todos los derechos reservados.', $html);
        $this->assertStringContainsString('Si tenés problemas para hacer clic en el botón', $html);
        $this->assertStringNotContainsString('Regards', $html);
        $this->assertStringNotContainsString('Hello!', $html);
        $this->assertStringNotContainsString('All rights reserved', $html);
        $this->assertStringNotContainsString('having trouble clicking', $html);
    }
}
