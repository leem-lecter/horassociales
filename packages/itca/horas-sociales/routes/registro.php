<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\RegistroController;

/*
| Registro diario (FBSSE-002) y, en M6, revisión de horas por el profesor.
|
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
| El menú lateral activa cada opción cuando existe la ruta con este nombre:
|   sse.registro   -> Registro diario
|   sse.revision   -> Revisión de horas (vista del profesor, M6)
*/

// M4 · Registro diario (FBSSE-002)
Route::get('registro', [RegistroController::class, 'index'])->name('registro');
Route::post('registro', [RegistroController::class, 'guardar'])->name('registro.guardar');
Route::delete('registro/{registro}', [RegistroController::class, 'borrar'])->name('registro.borrar');
Route::get('registro/{registro}/fotos/{evidencia}', [RegistroController::class, 'foto'])->name('registro.foto');
