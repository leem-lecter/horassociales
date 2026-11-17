<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\IngresoPruebaController;
use Itca\HorasSociales\Http\Controllers\PanelController;

/*
| ESTE ARCHIVO NO SE EDITA.
| Cada persona agrega sus rutas en su propio archivo, para no chocar en Git:
|   routes/registro.php  -> Persona A (registro diario y revisión de horas)
|   routes/proyecto.php  -> Persona B (proyecto, finalización, reglas y revisión de proyectos)
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
        Route::get('/', [PanelController::class, 'index'])->name('panel');

        require __DIR__.'/registro.php';
        require __DIR__.'/proyecto.php';
    });
