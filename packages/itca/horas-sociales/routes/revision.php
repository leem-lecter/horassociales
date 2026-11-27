<?php

use Illuminate\Support\Facades\Route;
use Itca\HorasSociales\Http\Controllers\RevisionController;

/*
| M6 · Vista del profesor (R12).
| Solo entra quien pasa el permiso "sse-revisar"; un estudiante recibe 403.
| Ya están dentro del grupo /horas-sociales con nombres que empiezan por "sse.".
|   sse.revision   -> Revisión de horas (menú del profesor)
*/

Route::middleware('can:sse-revisar')->group(function () {
    Route::get('revision', [RevisionController::class, 'index'])->name('revision');
    Route::get('revision/estudiantes/{estudiante}', [RevisionController::class, 'estudiante'])->name('revision.estudiante');

    Route::post('revision/proyectos/{proyecto}', [RevisionController::class, 'proyecto'])->name('revision.proyecto');
    Route::post('revision/registros', [RevisionController::class, 'registros'])->name('revision.registros');
    Route::post('revision/registros/{registro}', [RevisionController::class, 'registro'])->name('revision.registro');
    Route::post('revision/finalizaciones/{finalizacion}', [RevisionController::class, 'finalizacion'])->name('revision.finalizacion');
});
