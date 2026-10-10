<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beneficios_home', function (Blueprint $table): void {
            $table->id();
            $table->string('icono', 20);
            $table->string('texto', 60);
            $table->unsignedSmallInteger('orden');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->index(['activo', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficios_home');
    }
};
