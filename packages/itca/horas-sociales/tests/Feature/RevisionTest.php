<?php

namespace Itca\HorasSociales\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Itca\HorasSociales\Http\Controllers\RevisionController;
use Itca\HorasSociales\Http\Requests\ResolucionRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Finalizacion;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\Resumen;
use Tests\TestCase;

/**
 * M6 · Vista del profesor (R12): aprobar o no aprobar proyectos, horas y hojas de finalización.
 *
 *   php artisan test --filter=RevisionTest
 *
 * "Hoy" es el viernes 20 de noviembre de 2026, 9:00 a. m. en El Salvador.
 * El proyecto de prueba empieza el 1 de septiembre de 2026 y está enviado (esperando aprobación).
 */
class RevisionTest extends TestCase
{
    use RefreshDatabase;

    private $profesor;

    private $usuario;

    private Estudiante $estudiante;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $momento = Carbon::parse('2026-11-20 15:00:00', 'UTC');   // 9:00 a. m. en El Salvador
        Carbon::setTestNow($momento);
        CarbonImmutable::setTestNow($momento);

        $this->profesor = $this->crearUsuario('coordinador', 'profesor');
        $this->usuario = $this->crearUsuario('251234');
        $this->estudiante = Estudiante::delUsuario($this->usuario);
        $this->estudiante->update(['tipo_carrera' => Estudiante::TECNICO, 'nombre' => 'Ana Prueba']);
        $this->proyecto = Proyecto::create([
            'estudiante_id' => $this->estudiante->id,
            'estado' => Proyecto::ENVIADO,
            'institucion' => 'Centro Escolar de prueba',
            'supervisor_nombre' => 'Supervisora de prueba',
            'fecha_inicio' => '2026-09-01',
            'enviado_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /* ---------- R12: solo el profesor ---------- */

    public function test_un_estudiante_no_entra_a_la_revision(): void
    {
        $registro = $this->registro();
        $finalizacion = $this->finalizacion();
        $this->actingAs($this->usuario);

        $this->get(route('sse.revision'))->assertForbidden();
        $this->get(route('sse.revision.estudiante', $this->estudiante))->assertForbidden();
        $this->post(route('sse.revision.proyecto', $this->proyecto), ['decision' => 'aprobar'])->assertForbidden();
        $this->post(route('sse.revision.registro', $registro), ['decision' => 'aprobar'])->assertForbidden();
        $this->post(route('sse.revision.registros'), ['ids' => [$registro->id]])->assertForbidden();
        $this->post(route('sse.revision.finalizacion', $finalizacion), ['decision' => 'aprobar'])->assertForbidden();

        $this->assertSame(Proyecto::ENVIADO, $this->proyecto->fresh()->estado);
        $this->assertSame(Registro::PENDIENTE, $registro->fresh()->estado);
    }

    public function test_el_profesor_ve_la_bandeja_y_la_ficha(): void
    {
        $this->registro(['actividad' => 'Inventario de la biblioteca']);

        $this->actingAs($this->profesor)->get(route('sse.revision'))
            ->assertOk()
            ->assertSee('Proyectos de trabajo por aprobar')
            ->assertSee('Ana Prueba')
            ->assertSee('Inventario de la biblioteca');

        $this->actingAs($this->profesor)->get(route('sse.revision.estudiante', $this->estudiante))
            ->assertOk()
            ->assertSee('Centro Escolar de prueba')
            ->assertSee('Supervisora de prueba');
    }

    public function test_la_busqueda_filtra_estudiantes(): void
    {
        // Sin nada pendiente, la ficha de Ana solo aparecería en la tabla de estudiantes.
        $this->proyecto->update(['estado' => Proyecto::BORRADOR]);
        $otro = Estudiante::create(['carnet' => '259999', 'nombre' => 'Bruno Otro']);

        $this->actingAs($this->profesor)->get(route('sse.revision', ['buscar' => '2599']))
            ->assertOk()
            ->assertSee(route('sse.revision.estudiante', $otro))
            ->assertDontSee(route('sse.revision.estudiante', $this->estudiante));
    }

    public function test_el_profesor_va_a_revision_desde_las_pantallas_del_estudiante(): void
    {
        foreach (['sse.panel', 'sse.proyecto', 'sse.registro', 'sse.finalizacion'] as $ruta) {
            $this->actingAs($this->profesor)->get(route($ruta))->assertRedirect(route('sse.revision'));
        }
        // No se le crea un perfil de estudiante.
        $this->assertDatabaseCount('sse_estudiantes', 1);
    }

    /* ---------- Proyectos ---------- */

    public function test_aprobar_el_proyecto_permite_registrar_horas(): void
    {
        $this->resolver('sse.revision.proyecto', $this->proyecto, 'aprobar')
            ->assertRedirect(route('sse.revision'))
            ->assertSessionHas('sse_aviso');

        $proyecto = $this->proyecto->fresh();
        $this->assertSame(Proyecto::APROBADO, $proyecto->estado);
        $this->assertTrue($proyecto->permiteRegistrarHoras());
        $this->assertSame($this->profesor->getKey(), (int) $proyecto->resuelto_por);
        $this->assertNotNull($proyecto->resuelto_at);

        // R1: el estudiante ya puede registrar horas.
        $this->actingAs($this->usuario)->from(route('sse.registro'))
            ->post(route('sse.registro.guardar'), [
                'fecha' => '2026-11-18', 'tipo' => Registro::GENERAL, 'actividad' => 'Apoyo en la biblioteca', 'horas' => '4',
            ])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sse_registros', 1);
    }

    public function test_no_aprobar_el_proyecto_pide_comentario(): void
    {
        $this->resolver('sse.revision.proyecto', $this->proyecto, 'rechazar', '   ')
            ->assertSessionHasErrors(['comentario' => ResolucionRequest::MENSAJE_COMENTARIO]);
        $this->assertSame(Proyecto::ENVIADO, $this->proyecto->fresh()->estado);

        $this->resolver('sse.revision.proyecto', $this->proyecto, 'rechazar', 'Falta el horario de los sábados.')
            ->assertSessionHasNoErrors();
        $proyecto = $this->proyecto->fresh();
        $this->assertSame(Proyecto::RECHAZADO, $proyecto->estado);
        $this->assertSame('Falta el horario de los sábados.', $proyecto->comentario_coordinador);

        // El estudiante ve el comentario en su pantalla.
        $this->actingAs($this->usuario)->get(route('sse.proyecto'))->assertSee('Falta el horario de los sábados.');
    }

    public function test_un_proyecto_ya_resuelto_no_cambia(): void
    {
        $this->proyecto->update(['estado' => Proyecto::APROBADO]);

        $this->resolver('sse.revision.proyecto', $this->proyecto, 'rechazar', 'Otra opinión')
            ->assertSessionHas('sse_error', RevisionController::YA_REVISADO);

        $proyecto = $this->proyecto->fresh();
        $this->assertSame(Proyecto::APROBADO, $proyecto->estado);
        $this->assertNull($proyecto->comentario_coordinador);
    }

    /* ---------- Registros ---------- */

    public function test_aprobar_un_registro_cambia_el_panel_del_estudiante(): void
    {
        $this->aprobarProyecto();
        $registro = $this->registro(['horas' => 4]);
        $this->assertSame(0, Resumen::de($this->estudiante)->aprobadas);

        $this->resolver('sse.revision.registro', $registro, 'aprobar')->assertSessionHas('sse_aviso');

        $this->assertSame(Registro::APROBADO, $registro->fresh()->estado);
        $resumen = Resumen::de($this->estudiante);
        $this->assertSame(4, $resumen->aprobadas);
        $this->assertSame(0, $resumen->pendientes);
        $this->assertSame(296, $resumen->faltan);
    }

    public function test_no_aprobar_un_registro_lo_saca_del_total(): void
    {
        $this->aprobarProyecto();
        $registro = $this->registro(['horas' => 4]);

        $this->resolver('sse.revision.registro', $registro, 'rechazar')
            ->assertSessionHasErrors(['comentario' => ResolucionRequest::MENSAJE_COMENTARIO]);
        $this->resolver('sse.revision.registro', $registro, 'rechazar', 'Esa actividad no es del proyecto.');

        $registro->refresh();
        $this->assertSame(Registro::RECHAZADO, $registro->estado);
        $this->assertSame('Esa actividad no es del proyecto.', $registro->comentario_coordinador);
        $resumen = Resumen::de($this->estudiante);
        $this->assertSame(0, $resumen->aprobadas);
        $this->assertSame(0, $resumen->pendientes);
    }

    public function test_aprobar_varios_registros_de_una_vez(): void
    {
        $this->aprobarProyecto();
        $a = $this->registro(['fecha' => '2026-11-16']);
        $b = $this->registro(['fecha' => '2026-11-17']);
        $c = $this->registro(['fecha' => '2026-11-18', 'estado' => Registro::RECHAZADO]);

        $this->actingAs($this->profesor)->from(route('sse.revision'))
            ->post(route('sse.revision.registros'), ['ids' => [$a->id, $b->id, $c->id]])
            ->assertRedirect(route('sse.revision'))
            ->assertSessionHas('sse_aviso', 'Se aprobaron 2 registros. 1 ya no estaba en revisión y no cambió.');

        $this->assertSame(Registro::APROBADO, $a->fresh()->estado);
        $this->assertSame(Registro::APROBADO, $b->fresh()->estado);
        $this->assertSame(Registro::RECHAZADO, $c->fresh()->estado);
        $this->assertSame($this->profesor->getKey(), (int) $a->fresh()->resuelto_por);
    }

    public function test_aprobar_en_lote_sin_marcar_nada(): void
    {
        $this->actingAs($this->profesor)->from(route('sse.revision'))
            ->post(route('sse.revision.registros'), [])
            ->assertSessionHasErrors(['ids' => 'Marca al menos un registro para aprobar.']);
    }

    public function test_cambiar_la_decision_de_un_registro(): void
    {
        $this->aprobarProyecto();
        $registro = $this->registro(['estado' => Registro::APROBADO]);

        $this->resolver('sse.revision.registro', $registro, 'rechazar', 'Me equivoqué: faltan las fotos.')->assertSessionHas('sse_aviso');
        $this->assertSame(Registro::RECHAZADO, $registro->fresh()->estado);

        $this->resolver('sse.revision.registro', $registro, 'aprobar')->assertSessionHas('sse_aviso');
        $this->assertSame(Registro::APROBADO, $registro->fresh()->estado);

        // Repetir la misma decisión no cambia nada.
        $this->resolver('sse.revision.registro', $registro, 'aprobar')->assertSessionHas('sse_error', RevisionController::YA_REVISADO);
    }

    public function test_con_la_hoja_enviada_no_se_cambian_decisiones_pero_si_se_revisan_pendientes(): void
    {
        $this->aprobarProyecto();
        $aprobado = $this->registro(['fecha' => '2026-11-17', 'estado' => Registro::APROBADO]);
        $pendiente = $this->registro(['fecha' => '2026-11-18']);
        $this->finalizacion(Finalizacion::ENVIADO, '2026-11-18');

        $this->resolver('sse.revision.registro', $aprobado, 'rechazar', 'Cambio de opinión')
            ->assertSessionHas('sse_error', RevisionController::REGISTRO_CERRADO);
        $this->assertSame(Registro::APROBADO, $aprobado->fresh()->estado);

        $this->resolver('sse.revision.registro', $pendiente, 'aprobar')->assertSessionHas('sse_aviso');
        $this->assertSame(Registro::APROBADO, $pendiente->fresh()->estado);
    }

    public function test_volver_a_aprobar_respeta_el_limite_de_horas_por_dia(): void
    {
        $this->aprobarProyecto();
        $rechazado = $this->registro(['horas' => 8, 'estado' => Registro::RECHAZADO]);
        // Mientras estaba no aprobado, el estudiante registró otras horas ese mismo día.
        $this->registro(['horas' => 2]);

        $this->resolver('sse.revision.registro', $rechazado, 'aprobar')
            ->assertSessionHas('sse_error', RevisionController::mensajeR2($rechazado, 2, 8));
        $this->assertSame(Registro::RECHAZADO, $rechazado->fresh()->estado);
    }

    /* ---------- Hojas de finalización ---------- */

    public function test_no_se_aprueba_la_hoja_con_horas_en_revision(): void
    {
        $this->aprobarProyecto();
        $registro = $this->registro(['fecha' => '2026-11-18']);
        $finalizacion = $this->finalizacion(Finalizacion::ENVIADO, '2026-11-18');

        $this->resolver('sse.revision.finalizacion', $finalizacion, 'aprobar')
            ->assertSessionHas('sse_error', RevisionController::mensajePendientes(1));
        $this->assertSame(Finalizacion::ENVIADO, $finalizacion->fresh()->estado);

        $this->resolver('sse.revision.registro', $registro, 'aprobar');
        $this->resolver('sse.revision.finalizacion', $finalizacion, 'aprobar')->assertSessionHas('sse_aviso');

        $finalizacion->refresh();
        $this->assertSame(Finalizacion::APROBADO, $finalizacion->estado);
        $this->assertSame($this->profesor->getKey(), (int) $finalizacion->resuelto_por);
        $this->assertNotNull($finalizacion->resuelto_at);
    }

    public function test_no_se_aprueba_la_hoja_si_la_fecha_ya_no_coincide(): void
    {
        $this->aprobarProyecto();
        $this->registro(['fecha' => '2026-11-10', 'estado' => Registro::APROBADO]);
        $this->registro(['fecha' => '2026-11-18', 'estado' => Registro::RECHAZADO]);
        $finalizacion = $this->finalizacion(Finalizacion::ENVIADO, '2026-11-18');

        $this->resolver('sse.revision.finalizacion', $finalizacion, 'aprobar')
            ->assertSessionHas('sse_error', RevisionController::mensajeR8('2026-11-18', '2026-11-10'));
        $this->assertSame(Finalizacion::ENVIADO, $finalizacion->fresh()->estado);
    }

    public function test_devolver_la_hoja_reabre_el_registro_diario(): void
    {
        $this->aprobarProyecto();
        $this->registro(['fecha' => '2026-11-18', 'estado' => Registro::APROBADO]);
        $finalizacion = $this->finalizacion(Finalizacion::ENVIADO, '2026-11-18');
        $this->assertTrue($this->proyecto->registroCerrado());

        $this->resolver('sse.revision.finalizacion', $finalizacion, 'rechazar')
            ->assertSessionHasErrors(['comentario' => ResolucionRequest::MENSAJE_COMENTARIO]);

        $this->resolver('sse.revision.finalizacion', $finalizacion, 'rechazar', 'Falta la dirección completa.')
            ->assertSessionHas('sse_aviso');

        $finalizacion->refresh();
        $this->assertSame(Finalizacion::RECHAZADO, $finalizacion->estado);
        $this->assertSame('Falta la dirección completa.', $finalizacion->comentario_coordinador);
        $this->assertFalse($this->proyecto->registroCerrado());
        $this->assertTrue($finalizacion->editable());
    }

    public function test_una_hoja_ya_aprobada_no_cambia(): void
    {
        $this->aprobarProyecto();
        $finalizacion = $this->finalizacion(Finalizacion::APROBADO);

        $this->resolver('sse.revision.finalizacion', $finalizacion, 'rechazar', 'Otra opinión')
            ->assertSessionHas('sse_error', RevisionController::YA_REVISADO);
        $this->assertSame(Finalizacion::APROBADO, $finalizacion->fresh()->estado);
    }

    /* ---------- Ayudantes ---------- */

    private function resolver(string $ruta, $modelo, string $decision, ?string $comentario = null)
    {
        return $this->actingAs($this->profesor)
            ->from(route('sse.revision'))
            ->post(route($ruta, $modelo), array_filter(['decision' => $decision, 'comentario' => $comentario], fn ($v) => $v !== null));
    }

    private function aprobarProyecto(): void
    {
        $this->proyecto->update(['estado' => Proyecto::APROBADO]);
    }

    private function registro(array $datos = []): Registro
    {
        return Registro::create($datos + [
            'proyecto_id' => $this->proyecto->id,
            'estudiante_id' => $this->estudiante->id,
            'fecha' => '2026-11-18',
            'actividad' => 'Registro de prueba',
            'horas' => 4,
            'tipo' => Registro::GENERAL,
            'estado' => Registro::PENDIENTE,
        ]);
    }

    private function finalizacion(string $estado = Finalizacion::ENVIADO, string $fecha = '2026-11-18'): Finalizacion
    {
        return Finalizacion::create([
            'proyecto_id' => $this->proyecto->id,
            'estado' => $estado,
            'fecha_finalizacion' => $fecha,
            'enviado_at' => now(),
        ]);
    }

    private function crearUsuario(string $carnet, string $rol = 'estudiante')
    {
        $modelo = config('horas-sociales.modelo_usuario');

        return $modelo::forceCreate([
            'name' => "Usuario {$carnet}",
            'email' => "{$carnet}@ejemplo.test",
            'password' => bcrypt('demo1234'),
            config('horas-sociales.campo_carnet') => $rol === 'estudiante' ? $carnet : null,
            config('horas-sociales.campo_rol') => $rol,
        ]);
    }
}
