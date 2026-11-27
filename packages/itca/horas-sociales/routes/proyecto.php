<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\FinalizacionController;
use Itca\HorasSociales\Http\Controllers\ProyectoController;
use Itca\HorasSociales\Http\Controllers\ReglasController;

/*
| Proyecto de trabajo (FBSSE-001), finalización (FBSSE-003) y pantalla de reglas.
| La revisión del profesor está en routes/revision.php (M6).
|
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
| El menú lateral activa cada opción cuando existe la ruta con este nombre:
|   sse.proyecto       -> Proyecto de trabajo
|   sse.finalizacion   -> Finalización
|   sse.reglas         -> Reglas
*/

// M3 · Proyecto de trabajo (FBSSE-001)
Route::get('proyecto', [ProyectoController::class, 'editar'])->middleware('sse.estudiante')->name('proyecto');
Route::post('proyecto', [ProyectoController::class, 'guardar'])->name('proyecto.guardar');
Route::post('proyecto/reabrir', [ProyectoController::class, 'reabrir'])->name('proyecto.reabrir');

// M5 · Finalización (FBSSE-003) y Reglas
Route::get('finalizacion', [FinalizacionController::class, 'editar'])->middleware('sse.estudiante')->name('finalizacion');
Route::post('finalizacion', [FinalizacionController::class, 'guardar'])->name('finalizacion.guardar');
Route::get('reglas', [ReglasController::class, 'index'])->name('reglas');
