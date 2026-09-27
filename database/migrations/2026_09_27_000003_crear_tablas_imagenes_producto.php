<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('archivos_imagenes_producto', function (Blueprint $table): void {
            $table->id();
            $table->string('disco_temporal', 100);
            $table->string('ruta_temporal');
            $table->string('disco', 100);
            $table->string('ruta')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->string('sha256', 64)->nullable()->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('imagenes_producto', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('variante_producto_id')->nullable()->constrained('variantes_producto')->restrictOnDelete();
            $table->foreignId('archivo_imagen_producto_id')->constrained('archivos_imagenes_producto')->restrictOnDelete();
            $table->string('ruta')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();
            $table->index(['producto_id', 'variante_producto_id', 'orden'], 'img_prod_var_orden_idx');
            $table->index(['producto_id', 'variante_producto_id', 'es_principal'], 'img_prod_var_principal_idx');
            $table->unique(['producto_id', 'variante_producto_id', 'archivo_imagen_producto_id'], 'img_prod_var_archivo_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_producto');
        Schema::dropIfExists('archivos_imagenes_producto');
    }
};
