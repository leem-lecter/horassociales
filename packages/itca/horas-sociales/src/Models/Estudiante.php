<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estudiante extends Model
{
    public const TECNICO = 'tecnico';
    public const INGENIERIA = 'ingenieria';

    public const SEDES = ['Santa Tecla', 'Zacatecoluca', 'San Miguel', 'Santa Ana', 'La Unión'];
    public const JORNADAS = ['Diurna', 'Nocturna'];
    public const MODALIDADES = ['Presencial', 'Semipresencial', 'Virtual'];
    public const ANIOS_ESTUDIO = ['Primero', 'Segundo', 'Tercero', 'Otro'];

    protected $table = 'sse_estudiantes';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Perfil SSE del usuario que inició sesión en la aplicación anfitriona.
     * Si todavía no existe, lo crea con el carnet, el nombre y el correo del usuario.
     */
    public static function delUsuario($usuario): ?self
    {
        $carnet = $usuario->{config('horas-sociales.campo_carnet')} ?? null;
        if (! $carnet) {
            return null;
        }

        return static::firstOrCreate(
            ['carnet' => (string) $carnet],
            [
                'user_id' => $usuario->getKey(),
                'nombre' => $usuario->name ?? (string) $carnet,
                'correo' => $usuario->email ?? null,
            ],
        );
    }

    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'estudiante_id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class, 'estudiante_id');
    }

    /** Horas que pide su carrera: 300 o 500. null si aún no indicó el tipo de carrera. */
    public function meta(): ?int
    {
        return config("horas-sociales.metas.{$this->tipo_carrera}");
    }
}
