<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos de evidencia de los registros de medio ambiente (R7, regla 21).
 * Un registro puede tener varias fotos; sse_registros.evidencia_ruta solo guardaba una
 * y queda sin usar (las migraciones que ya están en main no se editan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_registro_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_id')->constrained('sse_registros')->cascadeOnDelete();
            $table->string('ruta');                 // dentro del disco config('horas-sociales.disco_evidencias')
            $table->string('nombre_original');      // nombre del archivo en el equipo del estudiante
            $table->unsignedInteger('bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_registro_evidencias');
    }
};
