<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBSSE-002: control diario de actividades.
 * Una fila por día trabajado. tipo separa la hoja general de la de medio ambiente (20 %).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('sse_proyectos')->cascadeOnDelete();
            // Repetido a propósito: la regla de 8 horas por día se revisa por estudiante,
            // sumando todos sus proyectos.
            $table->foreignId('estudiante_id')->constrained('sse_estudiantes')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('actividad');
            $table->unsignedTinyInteger('horas');            // enteras, de 1 a 8 (reglas 16 y 17)
            $table->string('tipo', 20)->default('general');  // general, ambiental
            $table->string('evidencia_ruta')->nullable();    // fotos de medio ambiente (regla 21)
            $table->string('estado', 20)->default('pendiente'); // pendiente, aprobado, rechazado
            $table->text('comentario_coordinador')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'fecha']);
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_registros');
    }
};
