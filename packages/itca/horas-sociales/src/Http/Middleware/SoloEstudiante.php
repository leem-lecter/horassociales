<?php

namespace Itca\HorasSociales\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/**
 * Pantallas del estudiante (Mi avance, Proyecto, Registro, Finalización).
 * El profesor no tiene perfil de estudiante: se le manda a su pantalla de Revisión.
 */
class SoloEstudiante
{
    public function handle(Request $request, Closure $next)
    {
        if (Gate::allows('sse-revisar') && Route::has('sse.revision')) {
            return redirect()->route('sse.revision');
        }

        return $next($request);
    }
}
