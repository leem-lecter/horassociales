<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\RegistroController;

/*
| Registro diario (FBSSE-002). La revisión del profesor está en routes/revision.php (M6).
|
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
| El menú lateral activa cada opción cuando existe la ruta con este nombre:
|   sse.registro   -> Registro diario
*/

// M4 · Registro diario (FBSSE-002)
Route::get('registro', [RegistroController::class, 'index'])->middleware('sse.estudiante')->name('registro');
Route::post('registro', [RegistroController::class, 'guardar'])->name('registro.guardar');
Route::delete('registro/{registro}', [RegistroController::class, 'borrar'])->name('registro.borrar');
Route::get('registro/{registro}/fotos/{evidencia}', [RegistroController::class, 'foto'])->name('registro.foto');
