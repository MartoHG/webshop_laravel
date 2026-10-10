<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table): void {
            $table->id();
            $table->string('numero', 20)->unique()->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('pedido_origen_id')->nullable()->constrained('pedidos')->restrictOnDelete();
            $table->string('token_pago', 64)->unique()->nullable();
            $table->string('nombre_cliente');
            $table->string('apellido_cliente');
            $table->string('dni_cliente');
            $table->string('email_cliente');
            $table->string('telefono_cliente');
            $table->string('provincia_envio', 40);
            $table->string('localidad_envio', 100);
            $table->string('codigo_postal_envio', 10);
            $table->string('direccion_envio', 160);
            $table->string('numero_envio', 10);
            $table->string('direccion_adicional', 60)->nullable();
            $table->string('observaciones_envio', 500)->nullable();
            $table->string('expreso', 100)->nullable();
            $table->decimal('subtotal_productos', 15, 2);
            $table->decimal('costo_envio', 15, 2)->nullable();
            $table->decimal('total_base', 15, 2)->nullable();
            $table->string('estado_pedido', 30);
            $table->string('estado_contacto', 30);
            $table->string('estado_envio', 30);
            $table->string('estado_pago', 30);
            $table->timestamp('contactado_en')->nullable();
            $table->timestamp('pago_habilitado_en')->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->timestamp('preparado_en')->nullable();
            $table->timestamp('despachado_en')->nullable();
            $table->timestamp('entregado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();
            $table->foreignId('cancelado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->string('motivo_cancelacion', 30)->nullable();
            $table->string('detalle_cancelacion', 500)->nullable();
            $table->string('huella_checkout', 64)->nullable();
            $table->timestamps();

            $table->index('usuario_id');
            $table->index(['usuario_id', 'huella_checkout']);
            $table->index('estado_pedido');
            $table->index('estado_pago');
            $table->index('estado_envio');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
