<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\ProyectoController;

/*
| RUTAS DE LA PERSONA B
| Proyecto de trabajo (FBSSE-001), finalización (FBSSE-003), pantalla de reglas
| y revisión de proyectos y finalizaciones por el profesor.
|
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
| El menú lateral activa cada opción cuando existe la ruta con este nombre:
|   sse.proyecto       -> Proyecto de trabajo
|   sse.finalizacion   -> Finalización
|   sse.reglas         -> Reglas
*/

// M3 · Proyecto de trabajo (FBSSE-001)
Route::get('proyecto', [ProyectoController::class, 'editar'])->name('proyecto');
Route::post('proyecto', [ProyectoController::class, 'guardar'])->name('proyecto.guardar');
Route::post('proyecto/reabrir', [ProyectoController::class, 'reabrir'])->name('proyecto.reabrir');
