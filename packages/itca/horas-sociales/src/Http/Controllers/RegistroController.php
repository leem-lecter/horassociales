<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Itca\HorasSociales\Http\Requests\RegistroRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Evidencia;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\Fechas;
use Itca\HorasSociales\Support\HojaFila;
use Itca\HorasSociales\Support\Resumen;

/**
 * Registro diario (FBSSE-002).
 * El estudiante anota cada día de servicio social; queda "pendiente" hasta que el
 * profesor lo revisa (M6). Los registros no aprobados se pueden borrar (R11).
 */
class RegistroController extends Controller
{
    public function index(Request $request)
    {
        $estudiante = $this->estudiante($request);
        if (! $estudiante) {
            return view('horas-sociales::sin-perfil', ['esProfesor' => Gate::allows('sse-revisar')]);
        }

        $pestana = $request->query('pestana') === Registro::AMBIENTAL ? Registro::AMBIENTAL : Registro::GENERAL;
        $proyecto = $this->proyectoActual($estudiante);

        // La hoja en papel es por proyecto: la tabla muestra los registros del proyecto actual.
        $registros = $proyecto
            ? $proyecto->registros()->with('evidencias')->where('tipo', $pestana)->orderBy('fecha')->orderBy('id')->get()
            : collect();

        $hoy = Fechas::hoy();
        $inicio = $proyecto?->fecha_inicio?->format('Y-m-d');

        return view('horas-sociales::registro', [
            'estudiante' => $estudiante,
            'proyecto' => $proyecto,
            'activo' => (bool) $proyecto?->permiteRegistrarHoras(),
            'pestana' => $pestana,
            'registros' => $registros,
            'hojaFila' => HojaFila::para($registros),
            'resumen' => Resumen::de($estudiante),
            'hoy' => $hoy,
            'inicio' => $inicio,
            'periodos' => PeriodoNoHabil::paraPantalla(),
            // Años que muestra "Ver días no hábiles": desde el inicio del proyecto hasta hoy (máximo 2).
            'anios' => range(max((int) substr($inicio ?? $hoy, 0, 4), (int) substr($hoy, 0, 4) - 1), (int) substr($hoy, 0, 4)),
            // R2 en pantalla: horas que ya tiene cada día, entre todos sus proyectos y sin los rechazados.
            'horasPorDia' => $estudiante->registros()->vigentes()->get(['fecha', 'horas'])
                ->groupBy(fn (Registro $r) => $r->fecha->format('Y-m-d'))
                ->map(fn ($dia) => $dia->sum('horas'))
                ->all(),
            // El envío pesaba más de lo que acepta PHP (ver HorasSocialesServiceProvider).
            'envioMuyPesado' => $request->query('error') === 'fotos',
        ]);
    }

    public function guardar(RegistroRequest $request)
    {
        $estudiante = $request->estudiante();
        $proyecto = $request->proyecto();
        $datos = $request->validated();
        $fotos = $datos['tipo'] === Registro::AMBIENTAL ? $request->file('fotos', []) : [];
        $disco = Storage::disk(config('horas-sociales.disco_evidencias'));
        $carpeta = null;

        try {
            DB::transaction(function () use ($estudiante, $proyecto, $datos, $fotos, $disco, &$carpeta) {
                // Bloquea al estudiante mientras suma: dos envíos seguidos no pasan juntos el límite.
                Estudiante::whereKey($estudiante->id)->lockForUpdate()->first();

                // R2: máximo 8 horas por día, entre todos sus proyectos y sin contar los rechazados.
                $max = (int) config('horas-sociales.max_horas_por_dia');
                $ya = (int) Registro::where('estudiante_id', $estudiante->id)
                    ->whereDate('fecha', $datos['fecha'])
                    ->vigentes()
                    ->sum('horas');

                if ($ya + (int) $datos['horas'] > $max) {
                    throw ValidationException::withMessages(['horas' => self::mensajeR2($datos['fecha'], $ya, $max)]);
                }

                $registro = $proyecto->registros()->create([
                    'estudiante_id' => $estudiante->id,
                    'fecha' => $datos['fecha'],
                    'actividad' => $datos['actividad'],
                    'horas' => (int) $datos['horas'],
                    'tipo' => $datos['tipo'],
                    'estado' => Registro::PENDIENTE,
                ]);

                // R7: fotos de evidencia, en el disco privado.
                $carpeta = $registro->carpetaEvidencias();
                foreach ($fotos as $foto) {
                    $ruta = $foto->store($carpeta, ['disk' => config('horas-sociales.disco_evidencias')]);
                    if (! $ruta) {
                        // Sin espacio o sin permiso de escritura: se deshace todo el registro.
                        throw new \RuntimeException("No se pudo guardar la foto en {$carpeta}.");
                    }
                    $registro->evidencias()->create([
                        'ruta' => $ruta,
                        'nombre_original' => mb_substr($foto->getClientOriginalName(), 0, 255),
                        'bytes' => (int) $foto->getSize(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Si algo falló después de guardar fotos, no quedan archivos sueltos.
            if ($carpeta) {
                $disco->deleteDirectory($carpeta);
            }
            throw $e;
        }

        $n = count($fotos);

        return redirect()->route('sse.registro', ['pestana' => $datos['tipo']])->with('sse_aviso', $n
            ? 'Registro enviado a revisión con '.$n.($n === 1 ? ' foto.' : ' fotos.')
            : 'Registro enviado a revisión.');
    }

    /** Borra un registro propio que no esté aprobado, con sus fotos (R11). */
    public function borrar(Request $request, Registro $registro)
    {
        $estudiante = $this->estudiante($request);
        abort_unless($estudiante && (int) $registro->estudiante_id === (int) $estudiante->id, 404);

        $volver = redirect()->route('sse.registro', ['pestana' => $registro->tipo]);

        if (! $registro->borrable()) {
            return $volver->with('sse_error', 'Este registro ya fue aprobado y no se puede borrar.');
        }

        $registro->borrarConFotos();

        return $volver->with('sse_aviso', 'Registro del '.Fechas::larga($registro->fecha).' eliminado.');
    }

    /** Muestra una foto de evidencia. Solo la ven el estudiante dueño y el profesor. */
    public function foto(Request $request, Registro $registro, Evidencia $evidencia)
    {
        abort_unless((int) $evidencia->registro_id === (int) $registro->id, 404);

        if (! Gate::allows('sse-revisar')) {
            $estudiante = $this->estudiante($request);
            abort_unless($estudiante && (int) $registro->estudiante_id === (int) $estudiante->id, 404);
        }

        $disco = Storage::disk(config('horas-sociales.disco_evidencias'));
        abort_unless($disco->exists($evidencia->ruta), 404);

        return $disco->response($evidencia->ruta, $evidencia->nombre_original, [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',   // el navegador la trata solo como imagen
        ]);
    }

    /** Mensaje de R2, también lo arma la pantalla para el aviso inmediato. */
    public static function mensajeR2(string $fecha, int $ya, int $max): string
    {
        $dia = 'El '.Fechas::larga($fecha).' ya tiene '.$ya.' h registradas';

        return $ya >= $max
            ? $dia.': es el máximo de '.$max.' por día.'
            : $dia.'. Puedes agregar hasta '.($max - $ya).' h (máximo '.$max.' por día).';
    }

    private function estudiante(Request $request): ?Estudiante
    {
        return Gate::allows('sse-revisar') ? null : Estudiante::delUsuario($request->user());
    }

    /** Igual que en "Proyecto de trabajo": el proyecto más reciente del estudiante. */
    private function proyectoActual(Estudiante $estudiante): ?Proyecto
    {
        return $estudiante->proyectos()->latest('id')->first();
    }
}
