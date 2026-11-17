<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FBSSE-001: proyecto de trabajo. Uno por cada institución (regla 19).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_proyectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('sse_estudiantes')->cascadeOnDelete();
            $table->string('estado', 20)->default('borrador');   // borrador, enviado, aprobado, rechazado

            // Sección 2: institución
            $table->string('institucion')->nullable();
            $table->string('institucion_telefono', 20)->nullable();
            $table->text('actividades_principales')->nullable();
            $table->string('supervisor_nombre')->nullable();
            $table->string('supervisor_correo')->nullable();
            $table->string('supervisor_telefono', 20)->nullable();
            $table->string('tiempo_estimado', 60)->nullable();
            $table->date('fecha_inicio')->nullable();

            // Sección 4: declaración
            $table->string('lugar_ejecucion')->nullable();
            $table->boolean('declaracion_aceptada')->default(false);

            // Sección 5: resolución del coordinador
            $table->text('comentario_coordinador')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();

            $table->timestamps();
            $table->index(['estudiante_id', 'estado']);
        });

        // Sección 3: actividades principales con su objetivo y duración
        Schema::create('sse_proyecto_objetivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('sse_proyectos')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden')->default(1);
            $table->string('actividad');
            $table->string('objetivo');
            $table->string('duracion', 60);
            $table->timestamps();
        });

        // Sección 4: horario semanal (AM y PM de lunes a domingo)
        Schema::create('sse_proyecto_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('sse_proyectos')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia');     // 1 = lunes ... 7 = domingo (ISO-8601)
            $table->string('turno', 2);             // am, pm
            $table->string('horas', 40);            // por ejemplo "8:00 a 12:00"
            $table->timestamps();
            $table->unique(['proyecto_id', 'dia', 'turno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_proyecto_horarios');
        Schema::dropIfExists('sse_proyecto_objetivos');
        Schema::dropIfExists('sse_proyectos');
    }
};
