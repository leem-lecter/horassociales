<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Itca\HorasSociales\Http\Requests\ResolucionRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Finalizacion;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\Fechas;
use Itca\HorasSociales\Support\HojaFila;
use Itca\HorasSociales\Support\Resumen;

/**
 * M6 · Vista del profesor (R12).
 * Bandeja con lo que espera revisión, tabla de avance por estudiante y ficha de cada estudiante.
 * Solo se resuelve lo que espera: un proyecto "enviado", un registro "pendiente" o una hoja
 * de finalización "enviada". Un registro ya resuelto se puede cambiar mientras el registro
 * diario no esté cerrado (R13); los proyectos y las hojas no (decisión 3).
 */
class RevisionController extends Controller
{
    public const YA_REVISADO = 'Esto ya fue revisado (quizá en otra pestaña), así que no se cambió nada.';

    public const REGISTRO_CERRADO = 'El estudiante ya envió su hoja de finalización: las decisiones sobre sus horas ya no se cambian. Si hay que corregir algo, primero devuelve la hoja.';

    public const POR_PAGINA = 30;

    /** Bandeja de revisión y tabla de avance. */
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $finalizaciones = Finalizacion::with('proyecto.estudiante')
            ->where('estado', Finalizacion::ENVIADO)
            ->orderBy('enviado_at')->orderBy('id')
            ->get();

        $estudiantes = Estudiante::query()
            ->with(['proyectos' => fn ($q) => $q->latest('id')])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $patron = '%'.addcslashes($buscar, '%_\\').'%';
                $q->where(fn ($q) => $q->where('nombre', 'like', $patron)->orWhere('carnet', 'like', $patron));
            })
            ->orderBy('nombre')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('horas-sociales::revision', [
            'proyectos' => Proyecto::with('estudiante')
                ->where('estado', Proyecto::ENVIADO)
                ->orderBy('enviado_at')->orderBy('id')
                ->get(),
            'registros' => Registro::with(['estudiante', 'evidencias'])
                ->where('estado', Registro::PENDIENTE)
                ->orderBy('fecha')->orderBy('id')
                ->get(),
            'finalizaciones' => $finalizaciones,
            // Horas que aún esperan revisión en el proyecto de cada hoja enviada (decisión 2).
            'pendientesPorProyecto' => self::horasPendientes($finalizaciones->pluck('proyecto_id')->all()),
            'estudiantes' => $estudiantes,
            'resumenes' => $estudiantes->getCollection()->mapWithKeys(fn (Estudiante $e) => [$e->id => Resumen::de($e)]),
            'buscar' => $buscar,
        ]);
    }

    /** Ficha completa de un estudiante: datos, proyecto, registros y hoja de finalización. */
    public function estudiante(Request $request, Estudiante $estudiante)
    {
        $pestana = $request->query('pestana') === Registro::AMBIENTAL ? Registro::AMBIENTAL : Registro::GENERAL;
        // Igual que las pantallas del estudiante: se trabaja con su proyecto más reciente.
        $proyecto = $estudiante->proyectos()->with(['objetivos', 'horarios', 'finalizacion'])->latest('id')->first();

        $registros = $proyecto
            ? $proyecto->registros()->with('evidencias')->where('tipo', $pestana)->orderBy('fecha')->orderBy('id')->get()
            : collect();

        return view('horas-sociales::revision-estudiante', [
            'estudiante' => $estudiante,
            'resumen' => Resumen::de($estudiante),
            'proyecto' => $proyecto,
            'finalizacion' => $proyecto?->finalizacion,
            'otrosProyectos' => max(0, $estudiante->proyectos()->count() - 1),
            'pestana' => $pestana,
            'registros' => $registros,
            'hojaFila' => HojaFila::para($registros),
            'cerrado' => (bool) $proyecto?->registroCerrado(),
            'pendientesProyecto' => $proyecto ? (self::horasPendientes([$proyecto->id])[$proyecto->id] ?? 0) : 0,
            'ultimo' => $proyecto?->ultimoDiaRegistrado(),
        ]);
    }

    /** Aprobar o no aprobar un proyecto de trabajo enviado. */
    public function proyecto(ResolucionRequest $request, Proyecto $proyecto)
    {
        $aprueba = $request->aprueba();

        $hecho = DB::transaction(function () use ($request, $proyecto, $aprueba) {
            $actual = Proyecto::whereKey($proyecto->id)->lockForUpdate()->first();
            if (! $actual || $actual->estado !== Proyecto::ENVIADO) {
                return false;
            }
            $actual->update(self::resolucion($request, $aprueba ? Proyecto::APROBADO : Proyecto::RECHAZADO));

            return true;
        });

        if (! $hecho) {
            return back()->with('sse_error', self::YA_REVISADO);
        }

        $nombre = $proyecto->estudiante?->nombre;

        return back()->with('sse_aviso', $aprueba
            ? "Proyecto de {$nombre} aprobado. Ya puede registrar horas."
            : "Proyecto de {$nombre} no aprobado. Verá tu comentario y podrá corregirlo.");
    }

    /** Aprobar o no aprobar un registro; también cambiar una decisión ya tomada (decisión 3). */
    public function registro(ResolucionRequest $request, Registro $registro)
    {
        $nuevo = $request->aprueba() ? Registro::APROBADO : Registro::RECHAZADO;

        $error = DB::transaction(function () use ($request, $registro, $nuevo) {
            // El mismo bloqueo que usa el registro diario para R2.
            Estudiante::whereKey($registro->estudiante_id)->lockForUpdate()->first();
            $actual = Registro::whereKey($registro->id)->lockForUpdate()->first();

            if (! $actual || $actual->estado === $nuevo) {
                return self::YA_REVISADO;
            }
            if ($actual->estado !== Registro::PENDIENTE && $actual->proyecto?->registroCerrado()) {
                return self::REGISTRO_CERRADO;
            }
            if ($actual->estado === Registro::RECHAZADO && $nuevo === Registro::APROBADO) {
                // R2: mientras estuvo no aprobado, el estudiante pudo registrar otras horas ese día.
                $max = (int) config('horas-sociales.max_horas_por_dia');
                $ya = (int) Registro::where('estudiante_id', $actual->estudiante_id)
                    ->whereDate('fecha', $actual->fecha->format('Y-m-d'))
                    ->whereKeyNot($actual->id)
                    ->vigentes()
                    ->sum('horas');
                if ($ya + $actual->horas > $max) {
                    return self::mensajeR2($actual, $ya, $max);
                }
            }

            $actual->update(self::resolucion($request, $nuevo));

            return null;
        });

        if ($error) {
            return back()->with('sse_error', $error);
        }

        $quien = $registro->estudiante?->nombre;
        $dia = Fechas::larga($registro->fecha);

        return back()->with('sse_aviso', $nuevo === Registro::APROBADO
            ? "Aprobadas {$registro->horas} h de {$quien} del {$dia}."
            : "Registro de {$quien} del {$dia} marcado como no aprobado.");
    }

    /** Aprobar varios registros pendientes de una vez (decisión 4). No aprobar va uno por uno. */
    public function registros(Request $request)
    {
        $ids = $request->validate(
            ['ids' => ['required', 'array', 'max:500'], 'ids.*' => ['integer']],
            ['ids.required' => 'Marca al menos un registro para aprobar.', 'ids.*' => 'La selección no es válida.'],
        )['ids'];
        $ids = array_values(array_unique(array_map('intval', $ids)));

        // Solo cambian los que siguen pendientes; aprobar no mueve el total de R2 (ya contaban).
        $aprobados = Registro::whereIn('id', $ids)->where('estado', Registro::PENDIENTE)->update([
            'estado' => Registro::APROBADO,
            'resuelto_at' => now(),
            'resuelto_por' => $request->user()->getKey(),
        ]);
        $omitidos = count($ids) - $aprobados;

        if (! $aprobados) {
            return back()->with('sse_error', 'Ninguno de los registros marcados seguía en revisión, así que no se cambió nada.');
        }

        return back()->with('sse_aviso', ($aprobados === 1 ? 'Se aprobó 1 registro.' : "Se aprobaron {$aprobados} registros.")
            .($omitidos ? ' '.($omitidos === 1 ? '1 ya no estaba' : "{$omitidos} ya no estaban").' en revisión y no cambió.' : ''));
    }

    /** Aprobar o devolver una hoja de finalización enviada. Devolverla reabre el registro diario (R13). */
    public function finalizacion(ResolucionRequest $request, Finalizacion $finalizacion)
    {
        $aprueba = $request->aprueba();

        $error = DB::transaction(function () use ($request, $finalizacion, $aprueba) {
            $actual = Finalizacion::whereKey($finalizacion->id)->lockForUpdate()->first();
            if (! $actual || $actual->estado !== Finalizacion::ENVIADO) {
                return self::YA_REVISADO;
            }

            if ($aprueba) {
                $proyecto = $actual->proyecto;
                // Decisión 2: primero se revisan todas las horas del proyecto.
                $pendientes = $proyecto->registros()->where('estado', Registro::PENDIENTE)->count();
                if ($pendientes) {
                    return self::mensajePendientes($pendientes);
                }
                // R8: si se rechazó el último día, la fecha de la hoja ya no coincide.
                $ultimo = $proyecto->ultimoDiaRegistrado();
                $fecha = $actual->fecha_finalizacion?->format('Y-m-d');
                if ($ultimo !== $fecha) {
                    return self::mensajeR8($fecha, $ultimo);
                }
            }

            $actual->update(self::resolucion($request, $aprueba ? Finalizacion::APROBADO : Finalizacion::RECHAZADO));

            return null;
        });

        if ($error) {
            return back()->with('sse_error', $error);
        }

        $nombre = $finalizacion->proyecto?->estudiante?->nombre;

        return back()->with('sse_aviso', $aprueba
            ? "Hoja de finalización de {$nombre} aprobada."
            : "Hoja de finalización devuelta a {$nombre}. Su registro diario se abrió otra vez.");
    }

    public static function mensajePendientes(int $n): string
    {
        return 'Este proyecto todavía tiene '.($n === 1 ? '1 registro' : "{$n} registros").' en revisión. Revísalos antes de aprobar la hoja de finalización.';
    }

    public static function mensajeR8(?string $fecha, ?string $ultimo): string
    {
        return 'La fecha de finalización'.($fecha ? ' ('.Fechas::larga($fecha).')' : '')
            .' ya no coincide con el último día con horas'.($ultimo ? ' ('.Fechas::larga($ultimo).')' : '')
            .'. Devuelve la hoja para que el estudiante la corrija.';
    }

    public static function mensajeR2(Registro $registro, int $ya, int $max): string
    {
        return 'No se puede aprobar: el '.Fechas::larga($registro->fecha).' el estudiante ya tiene '.$ya
            .' h en otros registros y con estas '.$registro->horas.' pasaría del máximo de '.$max.' por día.';
    }

    /** Columnas que deja cada decisión del profesor. */
    private static function resolucion(ResolucionRequest $request, string $estado): array
    {
        return [
            'estado' => $estado,
            'comentario_coordinador' => $request->comentario(),
            'resuelto_at' => now(),
            'resuelto_por' => $request->user()->getKey(),
        ];
    }

    /** @return array<int, int> proyecto_id => horas pendientes */
    private static function horasPendientes(array $proyectoIds): array
    {
        if (! $proyectoIds) {
            return [];
        }

        return Registro::whereIn('proyecto_id', $proyectoIds)
            ->where('estado', Registro::PENDIENTE)
            ->groupBy('proyecto_id')
            ->selectRaw('proyecto_id, SUM(horas) AS total')
            ->pluck('total', 'proyecto_id')
            ->map(fn ($t) => (int) $t)
            ->all();
    }
}
