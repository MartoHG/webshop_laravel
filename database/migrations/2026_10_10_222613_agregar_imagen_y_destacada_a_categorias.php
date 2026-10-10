<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categorias', function (Blueprint $table): void {
            $table->foreignId('imagen_contenido_id')->nullable()->after('orden')->constrained('imagenes_contenido')->nullOnDelete();
            $table->boolean('destacada_en_home')->default(false)->after('imagen_contenido_id');
        });
    }

    public function down(): void
    {
        Schema::table('categorias', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('imagen_contenido_id');
            $table->dropColumn('destacada_en_home');
        });
    }
};
