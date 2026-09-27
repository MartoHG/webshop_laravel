<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 160);
            $table->string('slug')->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(false);
            $table->boolean('disponible')->default(false);
            $table->timestamps();
        });

        Schema::create('variantes_producto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('color', 100)->nullable();
            $table->string('etiqueta', 160)->nullable();
            $table->string('sku', 100)->unique();
            $table->decimal('precio_base', 15, 2);
            $table->boolean('activo')->default(false);
            $table->boolean('disponible')->default(false);
            $table->timestamps();
            $table->index(['producto_id', 'color']);
        });

        DB::statement('ALTER TABLE variantes_producto ADD CONSTRAINT variantes_producto_precio_base_positivo CHECK (precio_base > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('variantes_producto');
        Schema::dropIfExists('productos');
    }
};
