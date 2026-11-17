<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\Resumen;

/** "Mi avance": la primera pantalla del estudiante. */
class PanelController extends Controller
{
    public function index(Request $request)
    {
        $esProfesor = Gate::allows('sse-revisar');
        // El profesor no tiene perfil de estudiante. Su pantalla de revisión llega en el paso 7.
        $estudiante = $esProfesor ? null : Estudiante::delUsuario($request->user());

        if (! $estudiante) {
            return view('horas-sociales::sin-perfil', ['esProfesor' => $esProfesor]);
        }

        return view('horas-sociales::panel', [
            'estudiante' => $estudiante,
            'resumen' => Resumen::de($estudiante),
            'proyecto' => $estudiante->proyectos()->latest()->first(),
            'ultimos' => $estudiante->registros()->orderByDesc('fecha')->limit(6)->get(),
            'noAprobados' => $estudiante->registros()->where('estado', Registro::RECHAZADO)->count(),
        ]);
    }
}
