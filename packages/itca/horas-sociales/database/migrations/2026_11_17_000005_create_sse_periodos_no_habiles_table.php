<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Días en que no se pueden hacer horas sociales: asuetos y vacaciones.
 * Un asueto de un día tiene la misma fecha de inicio y fin.
 * Los automáticos los crea "php artisan sse:periodos"; el profesor puede agregar manuales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sse_periodos_no_habiles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 20);                         // asueto, vacaciones, otro
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('origen', 20)->default('manual');    // automatico, manual
            $table->timestamps();

            $table->index(['fecha_inicio', 'fecha_fin']);
            $table->unique(['nombre', 'fecha_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sse_periodos_no_habiles');
    }
};
