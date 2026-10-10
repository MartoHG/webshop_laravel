<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_actualizaciones_precios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('operacion', 30);
            $table->decimal('valor', 15, 6);
            $table->string('alcance', 20);
            $table->json('filtro')->nullable();
            $table->unsignedInteger('cantidad_afectados');
            $table->json('detalle');
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_actualizaciones_precios');
    }
};
