<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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

    /** Mientras está enviada o aprobada, el estudiante no la puede cambiar (como el proyecto, R10). */
    public function editable(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::RECHAZADO], true);
    }

    /** Regla 18 (R9): fecha límite para entregar la documentación. */
    public function fechaLimiteEntrega()
    {
        return $this->fecha_finalizacion ? self::limitePara($this->fecha_finalizacion) : null;
    }

    /**
     * Fecha de finalización + 6 meses (config 'plazo_entrega_meses').
     * Sin desborde: del 31 de agosto pasa al 28 de febrero, no al 3 de marzo.
     */
    public static function limitePara($fecha): Carbon
    {
        return Carbon::parse($fecha)->startOfDay()->addMonthsNoOverflow((int) config('horas-sociales.plazo_entrega_meses'));
    }
}
