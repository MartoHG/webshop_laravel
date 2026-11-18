<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->foreignId('regla_precio_pago_id')->nullable()->constrained('reglas_precio_pago')->nullOnDelete();
            $table->string('id_pago_mercado_pago', 40)->nullable()->unique();
            $table->string('id_orden_mercado_pago', 120)->nullable();
            $table->string('clave_idempotencia', 64)->unique();
            $table->string('tipo_medio_pago', 30);
            $table->unsignedTinyInteger('cuotas');
            $table->decimal('importe_base', 15, 2);
            $table->decimal('porcentaje_aplicado', 8, 6);
            $table->decimal('importe_cobrado', 15, 2);
            $table->string('estado', 30);
            $table->string('detalle_estado', 255)->nullable();
            $table->string('motivo_revision_resuelta', 40)->nullable();
            $table->string('medio_pago_mercado_pago', 40)->nullable();
            $table->timestamp('pagado_en')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
