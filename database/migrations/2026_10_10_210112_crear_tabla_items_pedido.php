<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_pedido', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('variante_producto_id')->constrained('variantes_producto')->restrictOnDelete();
            $table->string('nombre_producto', 160);
            $table->string('color', 100)->nullable();
            $table->string('etiqueta', 160)->nullable();
            $table->string('sku', 100);
            $table->decimal('precio_unitario', 15, 2);
            $table->unsignedSmallInteger('cantidad');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_pedido');
    }
};
