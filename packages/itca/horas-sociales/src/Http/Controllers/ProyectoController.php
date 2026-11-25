<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Itca\HorasSociales\Http\Requests\ProyectoRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;

/**
 * Proyecto de trabajo (FBSSE-001).
 * Flujo: borrador -> enviado -> (aprobado | rechazado). R10: enviado o aprobado
 * no se edita; "Editar y volver a enviar" lo regresa a borrador.
 */
class ProyectoController extends Controller
{
    public function editar(Request $request)
    {
        $estudiante = $this->estudiante($request);
        if (! $estudiante) {
            return view('horas-sociales::sin-perfil', ['esProfesor' => Gate::allows('sse-revisar')]);
        }

        $proyecto = $this->proyectoActual($estudiante)?->load(['objetivos', 'horarios']);

        // Horario como [dia][turno] => "8:00 a 12:00"
        $horario = [];
        foreach ($proyecto?->horarios ?? [] as $h) {
            $horario[$h->dia][$h->turno] = $h->horas;
        }

        return view('horas-sociales::proyecto', [
            'estudiante' => $estudiante,
            'proyecto' => $proyecto,
            'horario' => $horario,
            'editable' => ! $proyecto || $proyecto->editable(),
            // Para avisar al instante si la fecha de inicio cae en día no hábil (R5): [nombre, inicio, fin]
            'periodos' => PeriodoNoHabil::paraPantalla(),
            // Primer día con horas registradas: la fecha de inicio no puede quedar después
            'primeraHora' => $proyecto?->registros()->where('estado', '!=', Registro::RECHAZADO)->min('fecha'),
        ]);
    }

    public function guardar(ProyectoRequest $request)
    {
        $estudiante = $this->estudiante($request);
        abort_unless($estudiante, 403);

        $proyecto = $this->proyectoActual($estudiante)
            ?? new Proyecto(['estudiante_id' => $estudiante->id, 'estado' => Proyecto::BORRADOR]);

        // R10, revisado en el servidor aunque la pantalla deshabilite los campos.
        if ($proyecto->exists && ! $proyecto->editable()) {
            return redirect()->route('sse.proyecto')->with('sse_error',
                'Este proyecto ya está enviado o aprobado y no se puede cambiar. Usa "Editar y volver a enviar" primero.');
        }

        $enviar = $request->enviando();

        DB::transaction(function () use ($request, $estudiante, $proyecto, $enviar) {
            $estudiante->update($request->datosEstudiante());

            $proyecto->fill($request->datosProyecto());
            if ($enviar) {
                $proyecto->fill([
                    'estado' => Proyecto::ENVIADO,
                    'enviado_at' => now(),
                    'comentario_coordinador' => null,
                    'resuelto_at' => null,
                    'resuelto_por' => null,
                ]);
            }
            $proyecto->save();

            $proyecto->objetivos()->delete();
            $proyecto->objetivos()->createMany($request->objetivos());

            $proyecto->horarios()->delete();
            $proyecto->horarios()->createMany($request->horarios());
        });

        return redirect()->route('sse.proyecto')->with('sse_aviso', $enviar
            ? 'Proyecto enviado al coordinador. Te avisará si lo aprueba.'
            : 'Borrador guardado.');
    }

    /** "Editar y volver a enviar": enviado o aprobado vuelve a borrador sin perder datos. */
    public function reabrir(Request $request)
    {
        $estudiante = $this->estudiante($request);
        abort_unless($estudiante, 403);

        $proyecto = $this->proyectoActual($estudiante);
        abort_unless($proyecto, 404);

        if (in_array($proyecto->estado, [Proyecto::ENVIADO, Proyecto::APROBADO], true)) {
            $proyecto->update(['estado' => Proyecto::BORRADOR]);
        }

        return redirect()->route('sse.proyecto')->with('sse_aviso',
            'Ya puedes editar tu proyecto. Cuando termines, envíalo de nuevo: necesita una nueva aprobación.');
    }

    private function estudiante(Request $request): ?Estudiante
    {
        return Gate::allows('sse-revisar') ? null : Estudiante::delUsuario($request->user());
    }

    /** Por ahora la pantalla trabaja con el proyecto más reciente del estudiante. */
    private function proyectoActual(Estudiante $estudiante): ?Proyecto
    {
        return $estudiante->proyectos()->latest('id')->first();
    }
}
