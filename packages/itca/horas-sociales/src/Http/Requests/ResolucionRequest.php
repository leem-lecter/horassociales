<?php

namespace Itca\HorasSociales\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Decisión del profesor sobre un proyecto, un registro o una hoja de finalización (R12).
 * Al no aprobar o devolver, el comentario es obligatorio (decisión 1 de M6).
 */
class ResolucionRequest extends FormRequest
{
    public const APROBAR = 'aprobar';
    public const RECHAZAR = 'rechazar';

    public const MENSAJE_COMENTARIO = 'Escribe un comentario para que el estudiante sepa qué corregir.';

    public function authorize(): bool
    {
        return Gate::allows('sse-revisar');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['comentario' => trim((string) $this->input('comentario', '')) ?: null]);
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:'.self::APROBAR.','.self::RECHAZAR],
            'comentario' => ['nullable', 'string', 'max:500', 'required_if:decision,'.self::RECHAZAR],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.*' => 'Elige Aprobar o No aprobar.',
            'comentario.required_if' => self::MENSAJE_COMENTARIO,
            'comentario.max' => 'El comentario puede tener hasta 500 letras.',
        ];
    }

    public function aprueba(): bool
    {
        return $this->validated('decision') === self::APROBAR;
    }

    public function comentario(): ?string
    {
        return $this->validated('comentario');
    }
}
