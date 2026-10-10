<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reglas_precio_pago', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_medio_pago', 30);
            $table->unsignedTinyInteger('cuotas');
            $table->decimal('porcentaje', 8, 6);
            $table->boolean('activo')->default(false);
            $table->timestamps();

            $table->unique(['tipo_medio_pago', 'cuotas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reglas_precio_pago');
    }
};
