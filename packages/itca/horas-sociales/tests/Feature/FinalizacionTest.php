<?php

namespace Itca\HorasSociales\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Itca\HorasSociales\Http\Controllers\FinalizacionController;
use Itca\HorasSociales\Http\Requests\FinalizacionRequest;
use Itca\HorasSociales\Http\Requests\RegistroRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Finalizacion;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * M5 · Hoja de finalización (FBSSE-003), cierre del registro (R13) y pantalla de Reglas.
 *
 *   php artisan test --filter=FinalizacionTest
 *
 * "Hoy" es el viernes 20 de noviembre de 2026, 9:00 a. m. en El Salvador.
 * El proyecto de prueba empezó el 1 de septiembre de 2026 y está aprobado.
 */
class FinalizacionTest extends TestCase
{
    use RefreshDatabase;

    private $usuario;

    private Estudiante $estudiante;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $momento = Carbon::parse('2026-11-20 15:00:00', 'UTC');   // 9:00 a. m. en El Salvador
        Carbon::setTestNow($momento);
        CarbonImmutable::setTestNow($momento);

        $this->usuario = $this->crearUsuario('251234');
        $this->estudiante = Estudiante::delUsuario($this->usuario);
        $this->estudiante->update(['tipo_carrera' => Estudiante::TECNICO]);
        $this->proyecto = Proyecto::create([
            'estudiante_id' => $this->estudiante->id,
            'estado' => Proyecto::APROBADO,
            'institucion' => 'Centro Escolar de prueba',
            'institucion_telefono' => '22345678',
            'supervisor_correo' => 'supervisora@ejemplo.test',
            'fecha_inicio' => '2026-09-01',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /* ---------- R8: la fecha de finalización es el último día registrado ---------- */

    public function test_r8_acepta_la_fecha_del_ultimo_registro(): void
    {
        $this->registro(['fecha' => '2026-11-10']);
        $this->registro(['fecha' => '2026-11-18']);

        $this->enviar()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sse.finalizacion'))
            ->assertSessionHas('sse_aviso');

        $finalizacion = Finalizacion::sole();
        $this->assertSame(Finalizacion::ENVIADO, $finalizacion->estado);
        $this->assertSame('2026-11-18', $finalizacion->fecha_finalizacion->format('Y-m-d'));
        $this->assertNotNull($finalizacion->enviado_at);
        $this->assertSame('22345678', $finalizacion->telefonos_institucion);   // sin espacios

        // Los datos del estudiante se guardan en su perfil, sin guion en el teléfono.
        $this->estudiante->refresh();
        $this->assertSame('70000001', $this->estudiante->telefono);
        $this->assertSame('Santa Tecla', $this->estudiante->sede);
    }

    public function test_r8_rechaza_una_fecha_distinta(): void
    {
        $this->registro(['fecha' => '2026-11-18']);

        $this->enviar(['fecha_finalizacion' => '2026-11-17'])->assertSessionHasErrors([
            'fecha_finalizacion' => 'La fecha de finalización debe coincidir con tu último día registrado (18 de noviembre de 2026).',
        ]);
        $this->assertDatabaseCount('sse_finalizaciones', 0);
    }

    public function test_r8_no_cuenta_los_registros_rechazados(): void
    {
        $this->registro(['fecha' => '2026-11-10']);
        $this->registro(['fecha' => '2026-11-18', 'estado' => Registro::RECHAZADO]);

        $this->enviar(['fecha_finalizacion' => '2026-11-18'])
            ->assertSessionHasErrors(['fecha_finalizacion' => FinalizacionController::mensajeR8('2026-11-10')]);

        $this->enviar(['fecha_finalizacion' => '2026-11-10'])->assertSessionHasNoErrors();
        $this->assertSame(Finalizacion::ENVIADO, Finalizacion::sole()->estado);
    }

    public function test_r8_sin_registros_no_se_puede_enviar(): void
    {
        $this->enviar()->assertSessionHasErrors(['fecha_finalizacion' => FinalizacionController::MENSAJE_SIN_REGISTROS]);
        $this->assertDatabaseCount('sse_finalizaciones', 0);
    }

    /* ---------- R9: fecha límite, 6 meses después ---------- */

    #[DataProvider('limites')]
    public function test_r9_fecha_limite_seis_meses_despues(string $fin, string $limite): void
    {
        $this->assertSame($limite, Finalizacion::limitePara($fin)->format('Y-m-d'));
    }

    public static function limites(): array
    {
        return [
            'día normal' => ['2026-11-18', '2027-05-18'],
            'fin de mes largo a febrero' => ['2026-08-31', '2027-02-28'],
            'febrero de año bisiesto' => ['2027-08-31', '2028-02-29'],
            'cambio de año' => ['2026-12-31', '2027-06-30'],
        ];
    }

    public function test_r9_la_hoja_guardada_calcula_su_fecha_limite(): void
    {
        $finalizacion = $this->finalizacion(Finalizacion::ENVIADO, '2026-08-31');

        $this->assertSame('2027-02-28', $finalizacion->fechaLimiteEntrega()->format('Y-m-d'));
    }

    /* ---------- Borrador, envío y bloqueo ---------- */

    public function test_el_borrador_se_guarda_a_medias(): void
    {
        $this->actingAs($this->usuario)->from(route('sse.finalizacion'))
            ->post(route('sse.finalizacion.guardar'), ['accion' => 'borrador', 'direccion_institucion' => 'Calle principal'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('sse_aviso', 'Borrador guardado.');

        $finalizacion = Finalizacion::sole();
        $this->assertSame(Finalizacion::BORRADOR, $finalizacion->estado);
        $this->assertSame('Calle principal', $finalizacion->direccion_institucion);
        $this->assertNull($finalizacion->fecha_finalizacion);
    }

    public function test_enviar_pide_todos_los_datos(): void
    {
        $this->registro();

        $this->enviar(['direccion_institucion' => '', 'telefonos_institucion' => '123', 'correo_institucional' => 'no-es-correo'])
            ->assertSessionHasErrors(['direccion_institucion', 'telefonos_institucion', 'correo_institucional']);
        $this->assertDatabaseCount('sse_finalizaciones', 0);
    }

    public function test_r1_sin_proyecto_aprobado_no_se_finaliza(): void
    {
        $this->registro();
        $this->proyecto->update(['estado' => Proyecto::ENVIADO]);

        $this->enviar()->assertSessionHasErrors(['proyecto' => FinalizacionRequest::MENSAJE_R1]);
        $this->assertDatabaseCount('sse_finalizaciones', 0);

        $this->actingAs($this->usuario)->get(route('sse.finalizacion'))
            ->assertOk()
            ->assertSee('Todavía no puedes llenar esta hoja.')
            ->assertDontSee('Enviar hoja de finalización');
    }

    public function test_la_hoja_enviada_no_se_cambia(): void
    {
        $this->registro();
        $this->enviar()->assertSessionHasNoErrors();

        $this->enviar(['observaciones' => 'Cambio después de enviar'])
            ->assertSessionHas('sse_error');
        $this->assertNull(Finalizacion::sole()->observaciones);
    }

    public function test_la_hoja_devuelta_se_puede_corregir_y_reenviar(): void
    {
        $this->registro();
        $this->finalizacion(Finalizacion::RECHAZADO)->update(['comentario_coordinador' => 'Falta la dirección completa.']);

        $this->actingAs($this->usuario)->get(route('sse.finalizacion'))
            ->assertOk()
            ->assertSee('Falta la dirección completa.')
            ->assertSee('Enviar hoja de finalización');

        $this->enviar()->assertSessionHasNoErrors();
        $finalizacion = Finalizacion::sole();
        $this->assertSame(Finalizacion::ENVIADO, $finalizacion->estado);
        $this->assertNull($finalizacion->comentario_coordinador);
    }

    /* ---------- Pantalla de finalización ---------- */

    public function test_la_pantalla_rellena_la_fecha_y_muestra_el_limite(): void
    {
        $this->registro(['fecha' => '2026-11-18', 'estado' => Registro::APROBADO]);

        $this->actingAs($this->usuario)->get(route('sse.finalizacion'))
            ->assertOk()
            ->assertSee('value="2026-11-18"', false)
            ->assertSee('Tu último registro es del 18 de noviembre de 2026.')
            ->assertSee('18 de mayo de 2027')
            ->assertSee('value="22345678"', false)                    // teléfono del proyecto
            ->assertSee('value="supervisora@ejemplo.test"', false)    // correo del proyecto
            ->assertDontSee('horas en revisión');
    }

    public function test_la_pantalla_avisa_las_horas_en_revision_sin_impedir_enviar(): void
    {
        $this->registro(['horas' => 4]);

        $this->actingAs($this->usuario)->get(route('sse.finalizacion'))
            ->assertOk()
            ->assertSee('4 horas en revisión')
            ->assertSee('Enviar hoja de finalización');

        $this->enviar()->assertSessionHasNoErrors();
    }

    public function test_el_profesor_no_tiene_hoja_de_finalizacion(): void
    {
        $profesor = $this->crearUsuario('coordinador', 'profesor');

        // M6: el profesor va a su pantalla de Revisión.
        $this->actingAs($profesor)->get(route('sse.finalizacion'))->assertRedirect(route('sse.revision'));

        $this->actingAs($profesor)->post(route('sse.finalizacion.guardar'), ['accion' => 'enviar'])->assertForbidden();
        $this->assertDatabaseCount('sse_finalizaciones', 0);
    }

    /* ---------- R13: con la hoja enviada se cierra el registro ---------- */

    #[DataProvider('estadosQueCierran')]
    public function test_r13_con_la_hoja_enviada_no_se_registran_horas(string $estado): void
    {
        $this->registro();
        $this->finalizacion($estado);

        $this->registrarHoras()->assertSessionHasErrors(['proyecto' => RegistroRequest::MENSAJE_R13]);
        $this->assertDatabaseCount('sse_registros', 1);
    }

    #[DataProvider('estadosQueCierran')]
    public function test_r13_con_la_hoja_enviada_no_se_borran_registros(string $estado): void
    {
        $registro = $this->registro();
        $this->finalizacion($estado);

        $this->actingAs($this->usuario)->from(route('sse.registro'))
            ->delete(route('sse.registro.borrar', $registro))
            ->assertSessionHas('sse_error', 'Ya enviaste tu hoja de finalización: los registros de este proyecto ya no se pueden borrar.');
        $this->assertModelExists($registro);
    }

    public static function estadosQueCierran(): array
    {
        return ['enviada' => [Finalizacion::ENVIADO], 'aprobada' => [Finalizacion::APROBADO]];
    }

    #[DataProvider('estadosQueNoCierran')]
    public function test_r13_borrador_o_devuelta_dejan_registrar(string $estado): void
    {
        $this->registro();
        $this->finalizacion($estado);

        $this->registrarHoras()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sse_registros', 2);
    }

    public static function estadosQueNoCierran(): array
    {
        return ['borrador' => [Finalizacion::BORRADOR], 'devuelta' => [Finalizacion::RECHAZADO]];
    }

    public function test_r13_la_pantalla_de_registro_avisa_y_quita_eliminar(): void
    {
        $this->registro();
        $this->finalizacion(Finalizacion::ENVIADO);

        $this->actingAs($this->usuario)->get(route('sse.registro'))
            ->assertOk()
            ->assertSee('Tu registro de horas quedó cerrado.')
            ->assertDontSee('Aún no puedes registrar horas.')
            ->assertDontSee('>Eliminar</button>', false);
    }

    /* ---------- Pantalla de Reglas ---------- */

    public function test_reglas_lista_los_dias_no_habiles_por_anio(): void
    {
        PeriodoNoHabil::create(['nombre' => 'Día de los Difuntos', 'tipo' => PeriodoNoHabil::ASUETO,
            'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-02']);
        PeriodoNoHabil::create(['nombre' => 'Vacaciones de fin de año', 'tipo' => PeriodoNoHabil::VACACIONES,
            'fecha_inicio' => '2026-12-24', 'fecha_fin' => '2027-01-01']);

        $this->actingAs($this->usuario)->get(route('sse.reglas'))
            ->assertOk()
            ->assertSee('Reglas del servicio social')
            ->assertSee('2026 (este año) · 2 periodos')
            ->assertSee('Día de los Difuntos')
            ->assertSee('24 de diciembre al')
            ->assertSee('1 de enero de 2027')
            ->assertSee('2027 · 1 periodo')                 // fin de año aparece también en 2027
            ->assertDontSee('No hay días no hábiles cargados');
    }

    public function test_reglas_avisa_si_falta_un_anio(): void
    {
        $this->actingAs($this->crearUsuario('coordinador', 'profesor'))->get(route('sse.reglas'))
            ->assertOk()
            ->assertSee('No hay días no hábiles cargados para 2026.')
            ->assertSee('php artisan sse:periodos 2027');
    }

    public function test_el_menu_enlaza_finalizacion_y_reglas(): void
    {
        $this->actingAs($this->usuario)->get(route('sse.panel'))
            ->assertOk()
            ->assertSee('href="'.route('sse.finalizacion').'"', false)
            ->assertSee('href="'.route('sse.reglas').'"', false);
    }

    /* ---------- Ayudas ---------- */

    private function enviar(array $datos = [])
    {
        return $this->actingAs($this->usuario)
            ->from(route('sse.finalizacion'))
            ->post(route('sse.finalizacion.guardar'), $datos + [
                'accion' => 'enviar',
                'sede' => 'Santa Tecla',
                'jornada' => 'Diurna',
                'modalidad' => 'Presencial',
                'anio_estudios' => 'Segundo',
                'telefono' => '7000-0001',
                'correo' => 'estudiante@ejemplo.test',
                'direccion_institucion' => 'Calle principal, Santa Tecla',
                'telefonos_institucion' => '2234 5678',
                'correo_institucional' => 'contacto@ejemplo.test',
                'fecha_finalizacion' => '2026-11-18',
                'observaciones' => '',
            ]);
    }

    private function registrarHoras()
    {
        return $this->actingAs($this->usuario)
            ->from(route('sse.registro'))
            ->post(route('sse.registro.guardar'), [
                'fecha' => '2026-11-19',
                'tipo' => Registro::GENERAL,
                'actividad' => 'Apoyo en el centro de cómputo',
                'horas' => '4',
            ]);
    }

    private function registro(array $datos = []): Registro
    {
        return Registro::create($datos + [
            'proyecto_id' => $this->proyecto->id,
            'estudiante_id' => $this->estudiante->id,
            'fecha' => '2026-11-18',
            'actividad' => 'Registro anterior',
            'horas' => 4,
            'tipo' => Registro::GENERAL,
            'estado' => Registro::PENDIENTE,
        ]);
    }

    private function finalizacion(string $estado, string $fecha = '2026-11-18'): Finalizacion
    {
        return Finalizacion::create([
            'proyecto_id' => $this->proyecto->id,
            'estado' => $estado,
            'fecha_finalizacion' => $fecha,
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
