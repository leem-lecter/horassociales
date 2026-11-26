<?php

namespace Itca\HorasSociales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Proyecto;

/**
 * Validación de la hoja de finalización (FBSSE-003).
 *
 * Dos botones mandan el mismo formulario, igual que en "Proyecto de trabajo":
 *   accion=borrador -> solo revisa formatos; se puede guardar a medias.
 *   accion=enviar   -> además pide todos los datos.
 *
 * R1 (proyecto aprobado) se revisa antes que todo, en prepareForValidation().
 * R8 (fecha igual al último día registrado) se revisa en el controlador, dentro de la
 * transacción que guarda, para que un registro nuevo no la cambie a medio envío.
 */
class FinalizacionRequest extends FormRequest
{
    public const MENSAJE_R1 = 'Todavía no puedes llenar la hoja de finalización: primero el coordinador debe aprobar tu proyecto de trabajo.';

    /** Campos que solo aceptan las 8 cifras de un teléfono. */
    private const TELEFONOS = ['telefono', 'telefonos_institucion'];

    private ?Estudiante $estudiante = null;

    private ?Proyecto $proyecto = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function enviando(): bool
    {
        return $this->input('accion') === 'enviar';
    }

    /** Perfil del estudiante que envía (null si es el profesor). */
    public function estudiante(): ?Estudiante
    {
        if (! $this->estudiante && ! Gate::allows('sse-revisar')) {
            $this->estudiante = Estudiante::delUsuario($this->user());
        }

        return $this->estudiante;
    }

    /** Proyecto actual: el más reciente, igual que en "Proyecto de trabajo" y "Registro diario". */
    public function proyecto(): ?Proyecto
    {
        return $this->proyecto ??= $this->estudiante()?->proyectos()->latest('id')->first();
    }

    protected function prepareForValidation(): void
    {
        abort_unless($this->estudiante(), 403);

        // R1: solo se finaliza un proyecto aprobado.
        if (! $this->proyecto()?->permiteRegistrarHoras()) {
            throw ValidationException::withMessages(['proyecto' => self::MENSAJE_R1]);
        }

        // Teléfonos: se aceptan con guion o espacios ("2234-5678") y se guardan solo con cifras.
        $limpios = [];
        foreach (self::TELEFONOS as $campo) {
            if (is_string($this->input($campo))) {
                $limpios[$campo] = preg_replace('/[\s-]/', '', $this->input($campo));
            }
        }
        foreach (['direccion_institucion', 'correo', 'correo_institucional', 'observaciones'] as $campo) {
            if (is_string($this->input($campo))) {
                $limpios[$campo] = trim($this->input($campo));
            }
        }

        $this->merge($limpios);
    }

    public function rules(): array
    {
        $req = $this->enviando() ? 'required' : 'nullable';

        return [
            'accion' => ['required', Rule::in(['borrador', 'enviar'])],

            // Datos del estudiante (se guardan en sse_estudiantes, como en el proyecto)
            'sede' => [$req, Rule::in(Estudiante::SEDES)],
            'jornada' => [$req, Rule::in(Estudiante::JORNADAS)],
            'modalidad' => [$req, Rule::in(Estudiante::MODALIDADES)],
            'anio_estudios' => [$req, Rule::in(Estudiante::ANIOS_ESTUDIO)],
            'telefono' => [$req, 'digits:8'],
            'correo' => [$req, 'email', 'max:255'],

            // Datos de la institución (se guardan en sse_finalizaciones)
            'direccion_institucion' => [$req, 'string', 'max:255'],
            'telefonos_institucion' => [$req, 'digits:8'],
            'correo_institucional' => [$req, 'email', 'max:255'],
            'fecha_finalizacion' => ['bail', $req, 'date_format:Y-m-d'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Este dato es obligatorio para enviar la hoja de finalización.',
            'email' => 'Escribe un correo válido, por ejemplo nombre@ejemplo.com.',
            'in' => 'Elige una opción de la lista.',
            'string' => 'Escribe un texto.',
            'max' => 'Es demasiado largo: máximo :max caracteres.',
            'digits' => 'Escribe solo números: :digits cifras.',
            'fecha_finalizacion.required' => 'Elige la fecha de finalización.',
            'fecha_finalizacion.date_format' => 'Escribe una fecha válida.',
        ];
    }

    /** Columnas de sse_estudiantes que se actualizan desde esta hoja. */
    public function datosEstudiante(): array
    {
        return $this->safe()->only(['sede', 'jornada', 'modalidad', 'anio_estudios', 'telefono', 'correo']);
    }

    /** Columnas de sse_finalizaciones. */
    public function datosFinalizacion(): array
    {
        return $this->safe()->only([
            'direccion_institucion', 'telefonos_institucion', 'correo_institucional', 'fecha_finalizacion', 'observaciones',
        ]);
    }
}
