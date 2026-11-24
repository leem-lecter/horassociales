<?php

namespace Itca\HorasSociales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\ProyectoHorario;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Rules\DiaHabil;

/**
 * Validación del proyecto de trabajo (FBSSE-001).
 *
 * Dos botones mandan el mismo formulario:
 *   accion=borrador -> solo revisa formatos; se puede guardar a medias.
 *   accion=enviar   -> además pide todos los datos del formulario.
 */
class ProyectoRequest extends FormRequest
{
    /** Campos que solo aceptan las 8 cifras de un teléfono. */
    private const TELEFONOS = ['telefono', 'emergencia_telefono', 'institucion_telefono', 'supervisor_telefono'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function enviando(): bool
    {
        return $this->input('accion') === 'enviar';
    }

    /** Quita las filas de objetivos que quedaron vacías y las renumera. */
    protected function prepareForValidation(): void
    {
        $objetivos = collect($this->input('objetivos', []))
            ->filter(fn ($fila) => is_array($fila) && collect($fila)->filter(fn ($v) => filled($v))->isNotEmpty())
            ->values()
            ->all();

        // Teléfonos: se aceptan escritos con guion o espacios ("7000-0001") y se guardan solo con cifras.
        $telefonos = [];
        foreach (self::TELEFONOS as $campo) {
            if (is_string($this->input($campo))) {
                $telefonos[$campo] = preg_replace('/[\s-]/', '', $this->input($campo));
            }
        }

        $this->merge(['objetivos' => $objetivos] + $telefonos);
    }

    public function rules(): array
    {
        // Al guardar borrador todo es opcional menos el nombre; al enviar, todo es obligatorio.
        $req = $this->enviando() ? 'required' : 'nullable';
        $texto = fn (int $max) => [$req, 'string', "max:{$max}"];

        return [
            'accion' => ['required', Rule::in(['borrador', 'enviar'])],

            // Sección 1: datos generales (se guardan en sse_estudiantes)
            'nombre' => ['required', 'string', 'max:255'],
            'anio_academico' => [$req, 'digits:4'],
            'escuela' => $texto(255),
            'sede' => [$req, Rule::in(Estudiante::SEDES)],
            'carrera' => $texto(255),
            'tipo_carrera' => [$req, Rule::in([Estudiante::TECNICO, Estudiante::INGENIERIA])],
            'anio_estudios' => [$req, Rule::in(Estudiante::ANIOS_ESTUDIO)],
            'jornada' => [$req, Rule::in(Estudiante::JORNADAS)],
            'modalidad' => [$req, Rule::in(Estudiante::MODALIDADES)],
            'correo' => [$req, 'email', 'max:255'],
            'telefono' => [$req, 'digits:8'],
            'emergencia_nombre' => $texto(255),
            'emergencia_telefono' => [$req, 'digits:8'],
            'coordinador' => $texto(255),

            // Sección 2: institución
            'institucion' => $texto(255),
            'institucion_telefono' => [$req, 'digits:8'],
            'actividades_principales' => $texto(2000),
            'supervisor_nombre' => $texto(255),
            'supervisor_correo' => [$req, 'email', 'max:255'],
            'supervisor_telefono' => [$req, 'digits:8'],
            'tiempo_estimado' => $texto(60),
            'fecha_inicio' => ['bail', $req, 'date', new DiaHabil],   // R5

            // Sección 3: objetivos. Una fila empezada debe quedar completa para enviar.
            'objetivos' => [$this->enviando() ? 'required' : 'nullable', 'array', 'max:10'],
            'objetivos.*.actividad' => $texto(255),
            'objetivos.*.objetivo' => $texto(255),
            'objetivos.*.duracion' => [$req, 'integer', 'between:1,2000'],   // en horas

            // Sección 4: horario, lugar y declaración
            'horario' => ['nullable', 'array'],
            // "08:00 a 12:00": reloj de 12 horas en cada fila (AM o PM), sin otros símbolos.
            'horario.*.*' => ['nullable', 'string', 'regex:/^(0?\d|1[0-2]):[0-5]\d a (0?\d|1[0-2]):[0-5]\d$/'],
            'lugar_ejecucion' => $texto(255),
            'declaracion_aceptada' => $this->enviando() ? ['accepted'] : ['nullable'],
        ];
    }

    /**
     * Si el proyecto ya tiene horas registradas, la fecha de inicio no puede
     * quedar después de la primera (las horas quedarían antes del inicio, R4).
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $fecha = $this->input('fecha_inicio');
                if (! $fecha || $validator->errors()->has('fecha_inicio')) {
                    return;
                }

                $proyecto = Estudiante::delUsuario($this->user())?->proyectos()->latest('id')->first();
                $primera = $proyecto?->registros()->where('estado', '!=', Registro::RECHAZADO)->min('fecha');

                if ($primera && Carbon::parse($fecha)->gt(Carbon::parse($primera))) {
                    $validator->errors()->add('fecha_inicio', 'Ya tienes horas registradas desde el '
                        .Carbon::parse($primera)->locale('es')->isoFormat('D [de] MMMM [de] YYYY')
                        .'. La fecha de inicio no puede ser posterior.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Este dato es obligatorio para enviar el proyecto.',
            'nombre.required' => 'Escribe tu nombre completo.',
            'email' => 'Escribe un correo válido, por ejemplo nombre@ejemplo.com.',
            'date' => 'Escribe una fecha válida.',
            'in' => 'Elige una opción de la lista.',
            'string' => 'Escribe un texto.',
            'max' => 'Es demasiado largo: máximo :max caracteres.',
            'digits' => 'Escribe solo números: :digits cifras.',
            'anio_academico.digits' => 'Escribe el año con 4 cifras, por ejemplo 2026.',
            'horario.*.*.regex' => 'Escribe el horario como 08:00 a 12:00.',
            'objetivos.*.duracion.integer' => 'Escribe solo el número de horas.',
            'objetivos.*.duracion.between' => 'Escribe de 1 a 2000 horas.',
            'objetivos.required' => 'Escribe al menos una actividad con su objetivo y duración.',
            'objetivos.max' => 'Puedes poner hasta 10 actividades.',
            'objetivos.*.actividad.required' => 'Falta la actividad de esta fila.',
            'objetivos.*.objetivo.required' => 'Falta el objetivo de esta fila.',
            'objetivos.*.duracion.required' => 'Falta la duración de esta fila.',
            'declaracion_aceptada.accepted' => 'Marca la declaración para poder enviar el proyecto.',
        ];
    }

    /** Columnas de sse_estudiantes que llena la sección 1. */
    public function datosEstudiante(): array
    {
        return $this->safe()->only([
            'nombre', 'anio_academico', 'escuela', 'sede', 'carrera', 'tipo_carrera', 'anio_estudios',
            'jornada', 'modalidad', 'correo', 'telefono', 'emergencia_nombre', 'emergencia_telefono', 'coordinador',
        ]);
    }

    /** Columnas de sse_proyectos que llenan las secciones 2 y 4. */
    public function datosProyecto(): array
    {
        return $this->safe()->only([
            'institucion', 'institucion_telefono', 'actividades_principales', 'supervisor_nombre',
            'supervisor_correo', 'supervisor_telefono', 'tiempo_estimado', 'fecha_inicio', 'lugar_ejecucion',
        ]) + ['declaracion_aceptada' => $this->boolean('declaracion_aceptada')];
    }

    /** Filas para sse_proyecto_objetivos, en orden. */
    public function objetivos(): array
    {
        return collect($this->validated('objetivos') ?? [])
            ->values()
            ->map(fn ($fila, $i) => [
                'orden' => $i + 1,
                'actividad' => $fila['actividad'] ?? '',
                'objetivo' => $fila['objetivo'] ?? '',
                'duracion' => (string) ($fila['duracion'] ?? ''),
            ])
            ->all();
    }

    /** Filas para sse_proyecto_horarios: solo las celdas con algo escrito. */
    public function horarios(): array
    {
        $horario = $this->validated('horario') ?? [];
        $filas = [];

        foreach (array_keys(ProyectoHorario::DIAS) as $dia) {
            foreach (array_keys(ProyectoHorario::TURNOS) as $turno) {
                $horas = trim((string) ($horario[$dia][$turno] ?? ''));
                if ($horas !== '') {
                    $filas[] = ['dia' => $dia, 'turno' => $turno, 'horas' => $horas];
                }
            }
        }

        return $filas;
    }
}
