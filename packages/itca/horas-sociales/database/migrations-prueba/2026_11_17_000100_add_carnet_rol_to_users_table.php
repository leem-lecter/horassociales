<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SOLO PARA EL PROYECTO DE PRUEBAS (SSE_LOGIN_PRUEBA=true).
 * La aplicación del profesor ya tiene sus propios usuarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'carnet')) {
                $table->string('carnet', 20)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'rol')) {
                $table->string('rol', 20)->default('estudiante')->after('carnet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['carnet']);
            $table->dropColumn(['carnet', 'rol']);
        });
    }
};
