<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Una foto de evidencia de un registro de medio ambiente (R7). */
class Evidencia extends Model
{
    protected $table = 'sse_registro_evidencias';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return ['bytes' => 'integer'];
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(Registro::class, 'registro_id');
    }
}
