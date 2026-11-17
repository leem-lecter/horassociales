<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datos del estudiante que piden los formularios FBSSE-001 y FBSSE-003.
 * Se enlaza con el usuario de la aplicación anfitriona por el carnet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_estudiantes', function (Blueprint $table) {
            $table->id();
            // Sin llave foránea: no sabemos cómo se llama la tabla de usuarios del profesor.
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('carnet', 20)->unique();
            $table->string('nombre');
            $table->string('correo')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('escuela')->nullable();
            $table->string('sede', 40)->nullable();              // Santa Tecla, Zacatecoluca, San Miguel, Santa Ana, La Unión
            $table->string('carrera')->nullable();
            $table->string('tipo_carrera', 20)->nullable();      // tecnico (300 h) | ingenieria (500 h)
            $table->string('anio_academico', 10)->nullable();
            $table->string('anio_estudios', 15)->nullable();     // Primero, Segundo, Tercero, Otro
            $table->string('jornada', 15)->nullable();           // Diurna, Nocturna
            $table->string('modalidad', 20)->nullable();         // Presencial, Semipresencial, Virtual
            $table->string('emergencia_nombre')->nullable();
            $table->string('emergencia_telefono', 20)->nullable();
            $table->string('coordinador')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_estudiantes');
    }
};
