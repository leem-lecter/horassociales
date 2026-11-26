<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Itca\HorasSociales\Http\Requests\FinalizacionRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Finalizacion;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\Fechas;

/**
 * Hoja de finalización (FBSSE-003).
 * Flujo: borrador -> enviado -> (aprobado | rechazado). Enviada o aprobada no se edita,
 * y el registro diario del proyecto queda cerrado (R13). Solo el profesor la devuelve (M6).
 */
class FinalizacionController extends Controller
{
    public const MENSAJE_SIN_REGISTROS = 'Todavía no tienes horas registradas en este proyecto, así que no hay nada que finalizar.';

    public function editar(Request $request)
    {
        $estudiante = $this->estudiante($request);
        if (! $estudiante) {
            return view('horas-sociales::sin-perfil', ['esProfesor' => Gate::allows('sse-revisar')]);
        }

        $proyecto = $estudiante->proyectos()->latest('id')->first();
        $finalizacion = $proyecto?->finalizacion;
        $puede = (bool) $proyecto?->permiteRegistrarHoras();
        $ultimo = $proyecto?->ultimoDiaRegistrado();

        return view('horas-sociales::finalizacion', [
            'estudiante' => $estudiante,
            'proyecto' => $proyecto,
            'finalizacion' => $finalizacion,
            'puede' => $puede,
            'editable' => $puede && (! $finalizacion || $finalizacion->editable()),
            'ultimo' => $ultimo,
            // Horas de este proyecto que el profesor aún no revisa: solo un aviso, no impide enviar.
            'pendientes' => (int) $proyecto?->registros()->where('estado', Registro::PENDIENTE)->sum('horas'),
            // La fecha llega rellena con el último día registrado (R8).
            'fechaFin' => $finalizacion?->fecha_finalizacion?->format('Y-m-d') ?? $ultimo,
            'meses' => (int) config('horas-sociales.plazo_entrega_meses'),
        ]);
    }

    public function guardar(FinalizacionRequest $request)
    {
        $estudiante = $request->estudiante();
        $proyecto = $request->proyecto();

        $finalizacion = $proyecto->finalizacion
            ?? new Finalizacion(['proyecto_id' => $proyecto->id, 'estado' => Finalizacion::BORRADOR]);

        // Enviada o aprobada no se cambia, aunque la pantalla deshabilite los campos.
        if ($finalizacion->exists && ! $finalizacion->editable()) {
            return redirect()->route('sse.finalizacion')->with('sse_error',
                'Ya enviaste tu hoja de finalización y no se puede cambiar. Si hay que corregirla, el coordinador debe devolvértela.');
        }

        $enviar = $request->enviando();

        DB::transaction(function () use ($request, $estudiante, $proyecto, $finalizacion, $enviar) {
            // El mismo bloqueo que usa el registro diario: mientras se envía, no entra un registro nuevo.
            Estudiante::whereKey($estudiante->id)->lockForUpdate()->first();

            if ($enviar) {
                self::revisarR8($proyecto, $request->validated('fecha_finalizacion'));
            }

            $estudiante->update($request->datosEstudiante());

            $finalizacion->fill($request->datosFinalizacion());
            if ($enviar) {
                $finalizacion->fill([
                    'estado' => Finalizacion::ENVIADO,
                    'enviado_at' => now(),
                    'comentario_coordinador' => null,
                    'resuelto_at' => null,
                    'resuelto_por' => null,
                ]);
            }
            $finalizacion->save();
        });

        return redirect()->route('sse.finalizacion')->with('sse_aviso', $enviar
            ? 'Hoja de finalización enviada al coordinador. Tu registro diario de este proyecto quedó cerrado.'
            : 'Borrador guardado.');
    }

    /** R8: la fecha de finalización es la del último registro que cuenta (no rechazado). */
    private static function revisarR8(Proyecto $proyecto, ?string $fecha): void
    {
        $ultimo = $proyecto->ultimoDiaRegistrado();

        if (! $ultimo) {
            throw ValidationException::withMessages(['fecha_finalizacion' => self::MENSAJE_SIN_REGISTROS]);
        }
        if ($fecha !== $ultimo) {
            throw ValidationException::withMessages(['fecha_finalizacion' => self::mensajeR8($ultimo)]);
        }
    }

    /** Mensaje de R8, también lo usa la pantalla para el aviso inmediato. */
    public static function mensajeR8(string $ultimo): string
    {
        return 'La fecha de finalización debe coincidir con tu último día registrado ('.Fechas::larga($ultimo).').';
    }

    private function estudiante(Request $request): ?Estudiante
    {
        return Gate::allows('sse-revisar') ? null : Estudiante::delUsuario($request->user());
    }
}
