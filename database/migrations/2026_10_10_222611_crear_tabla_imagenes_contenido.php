<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imagenes_contenido', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo', 20)->index();
            $table->string('disco_temporal', 100);
            $table->string('ruta_temporal')->nullable();
            $table->string('disco', 100);
            $table->string('ruta')->nullable();
            $table->string('ruta_miniatura')->nullable();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imagenes_contenido');
    }
};
