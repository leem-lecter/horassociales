<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** FBSSE-001, sección 4: una celda del horario (día y turno). */
class ProyectoHorario extends Model
{
    /** 1 = lunes ... 7 = domingo, igual que Carbon::dayOfWeekIso */
    public const DIAS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public const TURNOS = ['am' => 'AM', 'pm' => 'PM'];

    protected $table = 'sse_proyecto_horarios';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
