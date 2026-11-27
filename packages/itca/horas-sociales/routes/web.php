<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\IngresoPruebaController;
use Itca\HorasSociales\Http\Controllers\PanelController;

/*
| Rutas del módulo. Cada pantalla tiene su archivo:
|   routes/registro.php  -> registro diario (M4)
|   routes/proyecto.php  -> proyecto, finalización y reglas (M3 y M5)
|   routes/revision.php  -> vista del profesor (M6)
| 'sse.estudiante' manda al profesor a Revisión desde las pantallas del estudiante.
*/

$prefijo = config('horas-sociales.prefijo');

// Pantalla de ingreso: solo existe en el proyecto de pruebas.
if (config('horas-sociales.login_de_prueba')) {
    Route::middleware('web')->prefix($prefijo)->name('sse.')->group(function () {
        Route::get('ingresar', [IngresoPruebaController::class, 'formulario'])->name('ingreso');
        Route::post('ingresar', [IngresoPruebaController::class, 'ingresar'])->name('ingreso.enviar');
        Route::post('salir', [IngresoPruebaController::class, 'salir'])->name('salir');
    });
}

Route::middleware(config('horas-sociales.middleware'))
    ->prefix($prefijo)
    ->name('sse.')
    ->group(function () {
        Route::get('/', [PanelController::class, 'index'])->middleware('sse.estudiante')->name('panel');

        require __DIR__.'/registro.php';
        require __DIR__.'/proyecto.php';
        require __DIR__.'/revision.php';
    });
