<?php

namespace Itca\HorasSociales\Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;

/**
 * SOLO PARA EL PROYECTO DE PRUEBAS.
 * Crea tres estudiantes y un profesor (contraseña: demo1234) con datos parecidos
 * a los del prototipo HTML. Usa fechas relativas a hoy y salta asuetos y vacaciones.
 *
 *   php artisan db:seed --class="Itca\HorasSociales\Database\Seeders\DatosDePruebaSeeder"
 */
class DatosDePruebaSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PeriodosNoHabilesSeeder::class);

        $this->usuario('coordinador', 'Coordinador de SSE', 'coordinador@ejemplo.edu.sv', 'profesor');

        $base = [
            'escuela' => 'Ingeniería en Computación', 'sede' => 'Santa Tecla', 'anio_academico' => (string) now()->year,
            'jornada' => 'Diurna', 'modalidad' => 'Presencial', 'coordinador' => 'Coordinador de SSE (ejemplo)',
        ];

        // Ana: técnico, proyecto aprobado, horas aprobadas, en revisión y una no aprobada.
        $ana = $this->estudiante('251234', 'Ana Lucía Martínez Rivas', 'ana.martinez@ejemplo.edu.sv', $base + [
            'carrera' => 'Técnico en Ingeniería en Desarrollo de Software', 'tipo_carrera' => Estudiante::TECNICO,
            'anio_estudios' => 'Segundo', 'telefono' => '7000-0001',
            'emergencia_nombre' => 'Rosa Rivas (madre)', 'emergencia_telefono' => '7000-0002',
        ]);
        $sabados = $this->diasHabiles(CarbonImmutable::today()->previous(CarbonImmutable::SATURDAY), 11, 7);
        $proyectoAna = $this->proyecto($ana, Proyecto::APROBADO, 'Centro Escolar San José (ejemplo)', 'Lic. Marta Elena Pérez', end($sabados));
        $actividades = [
            'Apoyo en el centro de cómputo: mantenimiento de equipos',
            'Digitación del inventario de la biblioteca',
            'Clases de refuerzo de ofimática a estudiantes',
            'Actualización del sitio web de la institución',
            'Soporte técnico al personal administrativo',
        ];
        foreach ($sabados as $i => $fecha) {
            $estado = $i <= 1 ? Registro::PENDIENTE : ($i === 4 ? Registro::RECHAZADO : Registro::APROBADO);
            $this->registro($proyectoAna, $fecha, $actividades[$i % 5], 8, Registro::GENERAL, $estado,
                $estado === Registro::RECHAZADO ? 'Ese día no aparece firmado por el supervisor en la hoja.' : null);
        }
        $ambiental = ['Campaña de limpieza en la zona verde', 'Jornada de reciclaje y clasificación de desechos', 'Siembra de árboles en el huerto escolar', 'Campaña de limpieza en los alrededores'];
        foreach ($this->diasHabiles(CarbonImmutable::today()->previous(CarbonImmutable::SUNDAY)->subWeeks(7), 4, 7) as $i => $fecha) {
            $this->registro($proyectoAna, $fecha, $ambiental[$i], $i === 3 ? 4 : 5, Registro::AMBIENTAL,
                $i === 0 ? Registro::PENDIENTE : Registro::APROBADO);
        }

        // Carlos: ingeniería, proyecto enviado esperando aprobación, sin horas.
        $carlos = $this->estudiante('250042', 'Carlos Ernesto Flores Gómez', 'carlos.flores@ejemplo.edu.sv', $base + [
            'carrera' => 'Ingeniería en Desarrollo de Software', 'tipo_carrera' => Estudiante::INGENIERIA, 'anio_estudios' => 'Primero',
        ]);
        $this->proyecto($carlos, Proyecto::ENVIADO, 'Alcaldía Municipal (ejemplo)', 'Sr. Jorge Alas', CarbonImmutable::today()->addWeek());

        // Diana: técnico, casi termina.
        $diana = $this->estudiante('249876', 'Diana Sofía Hernández López', 'diana.hernandez@ejemplo.edu.sv', $base + [
            'carrera' => 'Técnico en Ingeniería en Desarrollo de Software', 'tipo_carrera' => Estudiante::TECNICO, 'anio_estudios' => 'Segundo',
        ]);
        $dias = $this->diasHabiles(CarbonImmutable::today()->subDays(3), 36, 2);
        $proyectoDiana = $this->proyecto($diana, Proyecto::APROBADO, 'Casa Comunal Las Flores (ejemplo)', 'Sra. Elba Ramos', end($dias));
        foreach ($dias as $i => $fecha) {
            $this->registro($proyectoDiana, $fecha, $i >= 28 ? $ambiental[$i % 4] : $actividades[$i % 5], $i === 0 ? 6 : 8,
                $i >= 28 ? Registro::AMBIENTAL : Registro::GENERAL, $i === 0 ? Registro::PENDIENTE : Registro::APROBADO);
        }
    }

    private function usuario(string $carnet, string $nombre, string $correo, string $rol)
    {
        $modelo = config('horas-sociales.modelo_usuario');
        $usuario = $modelo::query()->firstOrNew(['email' => $correo]);
        $usuario->forceFill([
            'name' => $nombre,
            'password' => Hash::make('demo1234'),
            config('horas-sociales.campo_carnet') => $carnet,
            config('horas-sociales.campo_rol') => $rol,
        ])->save();

        return $usuario;
    }

    private function estudiante(string $carnet, string $nombre, string $correo, array $datos): Estudiante
    {
        $usuario = $this->usuario($carnet, $nombre, $correo, 'estudiante');

        return Estudiante::updateOrCreate(['carnet' => $carnet], ['user_id' => $usuario->getKey(), 'nombre' => $nombre, 'correo' => $correo] + $datos);
    }

    private function proyecto(Estudiante $e, string $estado, string $institucion, string $supervisor, $inicio): Proyecto
    {
        $proyecto = Proyecto::updateOrCreate(['estudiante_id' => $e->id, 'institucion' => $institucion], [
            'estado' => $estado,
            'institucion_telefono' => '2200-0000',
            'actividades_principales' => 'Soporte técnico, digitación de inventario y clases de refuerzo de ofimática.',
            'supervisor_nombre' => $supervisor,
            'supervisor_correo' => 'supervision@ejemplo.sv',
            'supervisor_telefono' => '7100-0000',
            'tiempo_estimado' => '5 meses',
            'fecha_inicio' => $inicio,
            'lugar_ejecucion' => $institucion,
            'declaracion_aceptada' => true,
            'enviado_at' => now(),
            'resuelto_at' => $estado === Proyecto::APROBADO ? now() : null,
        ]);

        $proyecto->objetivos()->delete();
        $proyecto->objetivos()->createMany([
            ['orden' => 1, 'actividad' => 'Soporte técnico y mantenimiento del centro de cómputo', 'objetivo' => 'Mantener los equipos en funcionamiento para las clases', 'duracion' => '160 horas'],
            ['orden' => 2, 'actividad' => 'Refuerzo de ofimática', 'objetivo' => 'Que los estudiantes manejen documentos y hojas de cálculo', 'duracion' => '80 horas'],
        ]);
        $proyecto->horarios()->delete();
        $proyecto->horarios()->createMany([
            ['dia' => 6, 'turno' => 'am', 'horas' => '8:00 a 12:00'],
            ['dia' => 6, 'turno' => 'pm', 'horas' => '1:00 a 5:00'],
            ['dia' => 7, 'turno' => 'am', 'horas' => '8:00 a 12:00'],
        ]);

        return $proyecto;
    }

    private function registro(Proyecto $p, $fecha, string $actividad, int $horas, string $tipo, string $estado, ?string $comentario = null): void
    {
        $registro = Registro::where('proyecto_id', $p->id)->where('tipo', $tipo)
            ->whereDate('fecha', $fecha->format('Y-m-d'))
            ->first() ?? new Registro(['proyecto_id' => $p->id, 'tipo' => $tipo, 'fecha' => $fecha->format('Y-m-d')]);

        $registro->fill(['estudiante_id' => $p->estudiante_id, 'actividad' => $actividad, 'horas' => $horas, 'estado' => $estado,
            'comentario_coordinador' => $comentario, 'resuelto_at' => $estado === Registro::PENDIENTE ? null : now()])->save();
    }

    /** $cantidad fechas hacia atrás desde $desde, cada $paso días, saltando días no hábiles. */
    private function diasHabiles(CarbonImmutable $desde, int $cantidad, int $paso): array
    {
        $fechas = [];
        for ($f = $desde; count($fechas) < $cantidad; $f = $f->subDays($paso)) {
            if (! PeriodoNoHabil::para($f)) {
                $fechas[] = $f;
            }
        }

        return $fechas;
    }
}
