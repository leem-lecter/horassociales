<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** FBSSE-003: hoja de finalización. */
class Finalizacion extends Model
{
    public const BORRADOR = 'borrador';
    public const ENVIADO = 'enviado';
    public const APROBADO = 'aprobado';
    public const RECHAZADO = 'rechazado';

    protected $table = 'sse_finalizaciones';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'fecha_finalizacion' => 'date',
            'enviado_at' => 'datetime',
            'resuelto_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    /** Regla 18: fecha límite para entregar la documentación. */
    public function fechaLimiteEntrega()
    {
        return $this->fecha_finalizacion?->copy()->addMonths(config('horas-sociales.plazo_entrega_meses'));
    }
}
