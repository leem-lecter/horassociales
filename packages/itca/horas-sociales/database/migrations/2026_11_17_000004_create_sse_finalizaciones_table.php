<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBSSE-003: hoja de finalización. Una por proyecto.
 * Los datos del estudiante (sede, jornada, modalidad...) se leen de sse_estudiantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_finalizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained('sse_proyectos')->cascadeOnDelete();
            $table->string('direccion_institucion')->nullable();
            $table->string('telefonos_institucion', 60)->nullable();
            $table->string('correo_institucional')->nullable();
            $table->date('fecha_finalizacion')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('borrador'); // borrador, enviado, aprobado, rechazado
            $table->text('comentario_coordinador')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_finalizaciones');
    }
};
