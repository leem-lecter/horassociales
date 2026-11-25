<?php

namespace Itca\HorasSociales\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Rules\DiaHabil;
use Itca\HorasSociales\Support\Fechas;

/**
 * Validación del registro diario (FBSSE-002).
 *
 * Orden de las revisiones:
 *   R1 (proyecto aprobado) antes que todo, en prepareForValidation().
 *   R3, R4, R5 y R7 aquí, en rules().
 *   R2 (máximo 8 horas por día) en el controlador, dentro de la transacción que guarda,
 *   para que dos envíos al mismo tiempo no pasen juntos el límite.
 */
class RegistroRequest extends FormRequest
{
    public const MENSAJE_R1 = 'Aún no puedes registrar horas: primero el coordinador debe aprobar tu proyecto de trabajo.';

    private ?Estudiante $estudiante = null;

    private ?Proyecto $proyecto = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** Perfil del estudiante que envía (null si es el profesor). */
    public function estudiante(): ?Estudiante
    {
        if (! $this->estudiante && ! Gate::allows('sse-revisar')) {
            $this->estudiante = Estudiante::delUsuario($this->user());
        }

        return $this->estudiante;
    }

    /** Proyecto actual del estudiante: el más reciente, igual que en "Proyecto de trabajo". */
    public function proyecto(): ?Proyecto
    {
        return $this->proyecto ??= $this->estudiante()?->proyectos()->latest('id')->first();
    }

    protected function prepareForValidation(): void
    {
        abort_unless($this->estudiante(), 403);

        // R1: sin proyecto aprobado no se registran horas.
        if (! $this->proyecto()?->permiteRegistrarHoras()) {
            throw ValidationException::withMessages(['proyecto' => self::MENSAJE_R1]);
        }

        if (is_string($this->input('horas'))) {
            $this->merge(['horas' => trim($this->input('horas'))]);
        }
        if (is_string($this->input('actividad'))) {
            $this->merge(['actividad' => trim($this->input('actividad'))]);
        }
    }

    public function rules(): array
    {
        $ambiental = 'exclude_unless:tipo,'.Registro::AMBIENTAL;

        return [
            'tipo' => ['required', Rule::in([Registro::GENERAL, Registro::AMBIENTAL])],
            'fecha' => ['bail', 'required', 'date_format:Y-m-d', $this->fechaEnRango(...), new DiaHabil],  // R4, R5
            'actividad' => ['required', 'string', 'min:5', 'max:500'],
            'horas' => ['bail', 'required', 'integer', 'between:1,'.$this->maxDia()],                       // R3
            // R7: las fotos solo se piden (y se revisan) en medio ambiente.
            'fotos' => [$ambiental, 'required', 'array', 'max:'.config('horas-sociales.max_fotos')],
            'fotos.*' => [$ambiental, 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.config('horas-sociales.max_kb_foto')],
        ];
    }

    /** R4: la fecha no puede ser futura ni anterior al inicio del proyecto. */
    private function fechaEnRango(string $atributo, mixed $valor, Closure $fallar): void
    {
        if ($valor > Fechas::hoy()) {
            $fallar('La fecha no puede ser futura.');

            return;
        }

        $inicio = $this->proyecto()?->fecha_inicio?->format('Y-m-d');
        if ($inicio && $valor < $inicio) {
            $fallar('Es anterior al inicio de tu proyecto ('.Fechas::larga($inicio).').');
        }
    }

    public function messages(): array
    {
        $horas = 'Escribe horas enteras, de 1 a '.$this->maxDia().'.';
        $sinFotos = 'Sube al menos una foto de evidencia: es obligatoria para las horas de medio ambiente.';
        $foto = 'Cada foto debe ser JPG, PNG o WEBP de hasta '.round(config('horas-sociales.max_kb_foto') / 1024, 1).' MB.';

        return [
            'tipo.required' => 'Elige el tipo de actividad.',
            'tipo.in' => 'Elige el tipo de actividad.',
            'fecha.required' => 'Elige la fecha.',
            'fecha.date_format' => 'Escribe una fecha válida.',
            'actividad.max' => 'La descripción puede tener hasta 500 letras.',
            'actividad.required' => 'Describe la actividad que hiciste (al menos 5 letras).',
            'actividad.string' => 'Describe la actividad que hiciste (al menos 5 letras).',
            'actividad.min' => 'Describe la actividad que hiciste (al menos 5 letras).',
            'horas.required' => $horas,
            'horas.integer' => $horas,
            'horas.between' => $horas,
            'fotos.required' => $sinFotos,
            'fotos.array' => $sinFotos,
            'fotos.max' => 'Puedes subir hasta '.config('horas-sociales.max_fotos').' fotos por registro.',
            'fotos.*.uploaded' => $foto,
            'fotos.*.file' => $foto,
            'fotos.*.mimes' => $foto,
            'fotos.*.max' => $foto,
        ];
    }

    private function maxDia(): int
    {
        return (int) config('horas-sociales.max_horas_por_dia');
    }
}
