<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** FBSSE-001, sección 3: actividad principal con su objetivo y duración. */
class ProyectoObjetivo extends Model
{
    protected $table = 'sse_proyecto_objetivos';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }
}
