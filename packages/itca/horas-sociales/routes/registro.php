<?php

use Illuminate\Support\Facades\Route;

/*
| RUTAS DE LA PERSONA A
| Registro diario (FBSSE-002) y revisión de horas por el profesor.
|
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
| El menú lateral activa cada opción cuando existe la ruta con este nombre:
|   sse.registro   -> Registro diario
|   sse.revision   -> Revisión de horas (vista del profesor)
|
| Ejemplo:
|   Route::get('registro', [RegistroController::class, 'index'])->name('registro');
|   Route::post('registro', [RegistroController::class, 'guardar'])->name('registro.guardar');
*/
