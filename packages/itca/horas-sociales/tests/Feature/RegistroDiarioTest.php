<?php

namespace Itca\HorasSociales\Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Itca\HorasSociales\Http\Requests\RegistroRequest;
use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Evidencia;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Models\Proyecto;
use Itca\HorasSociales\Models\Registro;
use Itca\HorasSociales\Support\HojaFila;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * M4 · Registro diario (FBSSE-002).
 * Cada regla rechaza su caso con su mensaje y acepta el caso válido.
 *
 *   php artisan test --filter=RegistroDiarioTest
 *
 * "Hoy" es el viernes 20 de noviembre de 2026, 9:00 a. m. en El Salvador.
 * El proyecto de prueba empezó el 1 de septiembre de 2026 y está aprobado.
 */
class RegistroDiarioTest extends TestCase
{
    use RefreshDatabase;

    private const HORAS = 'Escribe horas enteras, de 1 a 8.';

    private const SIN_FOTOS = 'Sube al menos una foto de evidencia: es obligatoria para las horas de medio ambiente.';

    private const FOTO_MALA = 'Cada foto debe ser JPG, PNG o WEBP de hasta 2 MB.';

    private $usuario;

    private Estudiante $estudiante;

    private Proyecto $proyecto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ahora('2026-11-20 15:00:00');   // 9:00 a. m. en El Salvador
        Storage::fake(config('horas-sociales.disco_evidencias'));

        $this->usuario = $this->crearUsuario('251234');
        $this->estudiante = Estudiante::delUsuario($this->usuario);
        $this->estudiante->update(['tipo_carrera' => Estudiante::TECNICO]);
        $this->proyecto = $this->crearProyecto($this->estudiante);

        PeriodoNoHabil::create(['nombre' => 'Día de los Difuntos', 'tipo' => PeriodoNoHabil::ASUETO,
            'fecha_inicio' => '2026-11-02', 'fecha_fin' => '2026-11-02']);
        PeriodoNoHabil::create(['nombre' => 'Vacaciones de prueba', 'tipo' => PeriodoNoHabil::VACACIONES,
            'fecha_inicio' => '2026-10-05', 'fecha_fin' => '2026-10-09']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /* ---------- R1: sin proyecto aprobado no se registran horas ---------- */

    public function test_r1_rechaza_si_el_proyecto_no_esta_aprobado(): void
    {
        $this->proyecto->update(['estado' => Proyecto::ENVIADO]);

        $this->enviar()->assertSessionHasErrors(['proyecto' => RegistroRequest::MENSAJE_R1]);
        $this->assertDatabaseCount('sse_registros', 0);
    }

    public function test_r1_se_revisa_antes_que_los_demas_campos(): void
    {
        $this->proyecto->update(['estado' => Proyecto::BORRADOR]);

        $respuesta = $this->enviar(['horas' => '9', 'fecha' => '2026-11-02']);

        $respuesta->assertSessionHasErrors(['proyecto' => RegistroRequest::MENSAJE_R1]);
        $respuesta->assertSessionDoesntHaveErrors(['horas', 'fecha']);
    }

    public function test_r1_acepta_con_proyecto_aprobado(): void
    {
        $this->enviar()
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sse.registro', ['pestana' => 'general']))
            ->assertSessionHas('sse_aviso', 'Registro enviado a revisión.');

        $registro = Registro::sole();
        $this->assertSame(Registro::PENDIENTE, $registro->estado);
        $this->assertSame(4, $registro->horas);
        $this->assertSame('2026-11-18', $registro->fecha->format('Y-m-d'));
        $this->assertSame($this->proyecto->id, $registro->proyecto_id);
    }

    /* ---------- R2: máximo 8 horas por día ---------- */

    public function test_r2_rechaza_si_el_dia_pasa_de_8_horas(): void
    {
        $this->registro(['horas' => 6]);

        $this->enviar(['horas' => '3'])->assertSessionHasErrors([
            'horas' => 'El 18 de noviembre de 2026 ya tiene 6 h registradas. Puedes agregar hasta 2 h (máximo 8 por día).',
        ]);
        $this->assertDatabaseCount('sse_registros', 1);
    }

    public function test_r2_acepta_hasta_completar_8_horas(): void
    {
        $this->registro(['horas' => 6]);

        $this->enviar(['horas' => '2'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sse_registros', 2);
    }

    public function test_r2_dia_lleno_da_su_propio_mensaje(): void
    {
        $this->registro(['horas' => 8]);

        $this->enviar(['horas' => '1'])->assertSessionHasErrors([
            'horas' => 'El 18 de noviembre de 2026 ya tiene 8 h registradas: es el máximo de 8 por día.',
        ]);
    }

    public function test_r2_un_registro_rechazado_no_cuenta(): void
    {
        $this->registro(['horas' => 8, 'estado' => Registro::RECHAZADO]);

        $this->enviar(['horas' => '8'])->assertSessionHasNoErrors();
    }

    public function test_r2_suma_los_registros_de_todos_sus_proyectos(): void
    {
        $anterior = $this->proyecto;
        $this->registro(['horas' => 5, 'proyecto_id' => $anterior->id]);
        $this->crearProyecto($this->estudiante);   // el nuevo pasa a ser el proyecto actual

        $this->enviar(['horas' => '4'])->assertSessionHasErrors([
            'horas' => 'El 18 de noviembre de 2026 ya tiene 5 h registradas. Puedes agregar hasta 3 h (máximo 8 por día).',
        ]);
    }

    /* ---------- R3: solo horas enteras de 1 a 8 ---------- */

    #[DataProvider('horasInvalidas')]
    public function test_r3_rechaza_horas_invalidas(string $horas): void
    {
        $this->enviar(['horas' => $horas])->assertSessionHasErrors(['horas' => self::HORAS]);
        $this->assertDatabaseCount('sse_registros', 0);
    }

    public static function horasInvalidas(): array
    {
        return ['cero' => ['0'], 'nueve' => ['9'], 'con decimales' => ['4.5'], 'letras' => ['abc'], 'vacío' => ['']];
    }

    public function test_r3_acepta_1_y_8_horas(): void
    {
        $this->enviar(['horas' => '1', 'fecha' => '2026-11-18'])->assertSessionHasNoErrors();
        $this->enviar(['horas' => '8', 'fecha' => '2026-11-19'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sse_registros', 2);
    }

    /* ---------- R4: fecha no futura ni anterior al inicio del proyecto ---------- */

    public function test_r4_rechaza_fecha_futura(): void
    {
        $this->enviar(['fecha' => '2026-11-21'])->assertSessionHasErrors(['fecha' => 'La fecha no puede ser futura.']);
    }

    public function test_r4_rechaza_fecha_anterior_al_inicio_del_proyecto(): void
    {
        $this->enviar(['fecha' => '2026-08-31'])
            ->assertSessionHasErrors(['fecha' => 'Es anterior al inicio de tu proyecto (1 de septiembre de 2026).']);
    }

    public function test_r4_acepta_hoy_y_el_dia_de_inicio(): void
    {
        $this->enviar(['fecha' => '2026-11-20'])->assertSessionHasNoErrors();
        $this->enviar(['fecha' => '2026-09-01'])->assertSessionHasNoErrors();
    }

    public function test_r4_de_noche_en_el_salvador_manana_sigue_siendo_futura(): void
    {
        // 7:00 p. m. del 20 de noviembre en El Salvador; en UTC ya es 21 de noviembre.
        $this->ahora('2026-11-21 01:00:00');

        $this->enviar(['fecha' => '2026-11-21'])->assertSessionHasErrors(['fecha' => 'La fecha no puede ser futura.']);
        $this->enviar(['fecha' => '2026-11-20'])->assertSessionHasNoErrors();
    }

    /* ---------- R5: no en días no hábiles ---------- */

    public function test_r5_rechaza_un_asueto(): void
    {
        $this->enviar(['fecha' => '2026-11-02'])->assertSessionHasErrors([
            'fecha' => 'El 2 de noviembre de 2026 es día no hábil (Día de los Difuntos). Elige otra fecha.',
        ]);
    }

    public function test_r5_rechaza_un_dia_de_vacaciones(): void
    {
        $this->enviar(['fecha' => '2026-10-07'])->assertSessionHasErrors([
            'fecha' => 'El 7 de octubre de 2026 es día no hábil (Vacaciones de prueba). Elige otra fecha.',
        ]);
    }

    /* ---------- R7: medio ambiente pide fotos ---------- */

    public function test_r7_rechaza_medio_ambiente_sin_fotos(): void
    {
        $this->enviar(['tipo' => 'ambiental'])->assertSessionHasErrors(['fotos' => self::SIN_FOTOS]);
        $this->assertDatabaseCount('sse_registros', 0);
    }

    public function test_r7_acepta_medio_ambiente_con_fotos_y_las_guarda(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('jornada-1.jpg'), $this->foto('jornada-2.png', 'image/png')]])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('sse.registro', ['pestana' => 'ambiental']))
            ->assertSessionHas('sse_aviso', 'Registro enviado a revisión con 2 fotos.');

        $registro = Registro::sole();
        $this->assertSame(Registro::AMBIENTAL, $registro->tipo);
        $this->assertCount(2, $registro->evidencias);
        $this->assertSame('jornada-1.jpg', $registro->evidencias[0]->nombre_original);
        foreach ($registro->evidencias as $evidencia) {
            $this->disco()->assertExists($evidencia->ruta);
            $this->assertStringStartsWith($registro->carpetaEvidencias().'/', $evidencia->ruta);
        }
    }

    public function test_r7_rechaza_un_archivo_que_no_es_foto(): void
    {
        $pdf = UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf');

        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$pdf]])->assertSessionHasErrors(['fotos.0' => self::FOTO_MALA]);
    }

    public function test_r7_rechaza_una_foto_de_mas_de_2_mb(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('grande.jpg', 'image/jpeg', 3000)]])
            ->assertSessionHasErrors(['fotos.0' => self::FOTO_MALA]);
    }

    public function test_r7_rechaza_mas_de_5_fotos(): void
    {
        $fotos = array_map(fn ($i) => $this->foto("foto-{$i}.jpg"), range(1, 6));

        $this->enviar(['tipo' => 'ambiental', 'fotos' => $fotos])
            ->assertSessionHasErrors(['fotos' => 'Puedes subir hasta 5 fotos por registro.']);
    }

    public function test_r7_en_servicio_general_no_se_piden_ni_se_guardan_fotos(): void
    {
        $pdf = UploadedFile::fake()->create('informe.pdf', 100, 'application/pdf');

        $this->enviar(['tipo' => 'general', 'fotos' => [$pdf]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('sse_registro_evidencias', 0);
    }

    /* ---------- R11: un registro aprobado no se borra ---------- */

    public function test_r11_no_borra_un_registro_aprobado(): void
    {
        $registro = $this->registro(['estado' => Registro::APROBADO]);

        $this->actingAs($this->usuario)->delete(route('sse.registro.borrar', $registro))
            ->assertRedirect(route('sse.registro', ['pestana' => 'general']))
            ->assertSessionHas('sse_error', 'Este registro ya fue aprobado y no se puede borrar.');
        $this->assertModelExists($registro);
    }

    public function test_r11_borra_un_registro_pendiente_con_sus_fotos(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('jornada.jpg')]]);
        $registro = Registro::sole();
        $ruta = $registro->evidencias[0]->ruta;

        $this->actingAs($this->usuario)->delete(route('sse.registro.borrar', $registro))
            ->assertSessionHas('sse_aviso', 'Registro del 18 de noviembre de 2026 eliminado.');

        $this->assertModelMissing($registro);
        $this->assertDatabaseCount('sse_registro_evidencias', 0);
        $this->disco()->assertMissing($ruta);
    }

    public function test_r11_borra_un_registro_no_aprobado(): void
    {
        $registro = $this->registro(['estado' => Registro::RECHAZADO]);

        $this->actingAs($this->usuario)->delete(route('sse.registro.borrar', $registro))->assertSessionHas('sse_aviso');
        $this->assertModelMissing($registro);
    }

    /* ---------- Registros y fotos de otro estudiante ---------- */

    public function test_no_se_puede_borrar_el_registro_de_otro_estudiante(): void
    {
        $registro = $this->registro();
        $otro = $this->crearUsuario('250042');

        $this->actingAs($otro)->delete(route('sse.registro.borrar', $registro))->assertNotFound();
        $this->assertModelExists($registro);
    }

    public function test_la_foto_solo_la_ven_el_dueno_y_el_profesor(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('jornada.jpg')]]);
        $registro = Registro::sole();
        $ruta = route('sse.registro.foto', [$registro, $registro->evidencias[0]]);

        $this->actingAs($this->usuario)->get($ruta)->assertOk();
        $this->actingAs($this->crearUsuario('coordinador', 'profesor'))->get($ruta)->assertOk();
        $this->actingAs($this->crearUsuario('250042'))->get($ruta)->assertNotFound();
    }

    public function test_la_foto_se_envia_como_imagen_sin_adivinar_el_tipo(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('jornada.jpg')]]);
        $registro = Registro::sole();

        $this->actingAs($this->usuario)->get(route('sse.registro.foto', [$registro, $registro->evidencias[0]]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_el_profesor_no_puede_registrar_horas(): void
    {
        $this->actingAs($this->crearUsuario('coordinador', 'profesor'))
            ->post(route('sse.registro.guardar'), ['fecha' => '2026-11-18', 'tipo' => 'general', 'actividad' => 'Prueba de profesor', 'horas' => '4'])
            ->assertForbidden();
        $this->assertDatabaseCount('sse_registros', 0);
    }

    public function test_un_envio_demasiado_pesado_vuelve_con_aviso(): void
    {
        // Simula un envío más grande que post_max_size de PHP.
        $this->actingAs($this->usuario)
            ->call('POST', route('sse.registro.guardar'), [], [], [], ['CONTENT_LENGTH' => 1024 * 1024 * 1024])
            ->assertRedirect(route('sse.registro', ['pestana' => 'ambiental', 'error' => 'fotos']));

        $this->actingAs($this->usuario)->get(route('sse.registro', ['pestana' => 'ambiental', 'error' => 'fotos']))
            ->assertSee('Las fotos pesaban demasiado');
    }

    public function test_la_foto_debe_ser_de_ese_registro(): void
    {
        $this->enviar(['tipo' => 'ambiental', 'fotos' => [$this->foto('jornada.jpg')]]);
        $evidencia = Evidencia::sole();
        $otroRegistro = $this->registro(['fecha' => '2026-11-19']);

        $this->actingAs($this->usuario)->get(route('sse.registro.foto', [$otroRegistro, $evidencia]))->assertNotFound();
    }

    /* ---------- Pantalla ---------- */

    public function test_la_pantalla_muestra_los_registros_por_pestana(): void
    {
        $this->registro(['actividad' => 'Digitación del inventario de la biblioteca']);
        $this->registro(['actividad' => 'Campaña de limpieza en la zona verde', 'tipo' => Registro::AMBIENTAL, 'fecha' => '2026-11-19']);

        $this->actingAs($this->usuario)->get(route('sse.registro'))
            ->assertOk()
            ->assertSee('Nuevo registro')
            ->assertSee('Digitación del inventario de la biblioteca')
            ->assertDontSee('Campaña de limpieza en la zona verde')
            ->assertSee('H1 · 1');

        $this->actingAs($this->usuario)->get(route('sse.registro', ['pestana' => 'ambiental']))
            ->assertOk()
            ->assertSee('Campaña de limpieza en la zona verde')
            ->assertSee('que pide tu carrera');
    }

    public function test_la_pantalla_lista_los_dias_no_habiles(): void
    {
        $this->actingAs($this->usuario)->get(route('sse.registro'))
            ->assertOk()
            ->assertSee('Ver días no hábiles')
            ->assertSee('Día de los Difuntos')
            ->assertSee('Vacaciones de prueba');
    }

    public function test_la_pantalla_avisa_si_no_hay_proyecto_aprobado(): void
    {
        $this->proyecto->update(['estado' => Proyecto::ENVIADO]);

        $this->actingAs($this->usuario)->get(route('sse.registro'))
            ->assertOk()
            ->assertSee('Aún no puedes registrar horas.');
    }

    public function test_el_profesor_no_tiene_registro_diario(): void
    {
        // M6: el profesor va a su pantalla de Revisión.
        $this->actingAs($this->crearUsuario('coordinador', 'profesor'))->get(route('sse.registro'))
            ->assertRedirect(route('sse.revision'));
    }

    /* ---------- Hoja · fila ---------- */

    public function test_hoja_fila_salta_los_rechazados_y_cambia_de_hoja_cada_20(): void
    {
        $registros = collect(range(1, 22))->map(fn ($i) => (new Registro)->forceFill([
            'id' => $i,
            'estado' => $i === 3 ? Registro::RECHAZADO : Registro::APROBADO,
        ]));

        $filas = HojaFila::para($registros, 20);

        $this->assertSame('H1 · 1', $filas[1]);
        $this->assertNull($filas[3]);
        $this->assertSame('H1 · 3', $filas[4]);
        $this->assertSame('H1 · 20', $filas[21]);
        $this->assertSame('H2 · 1', $filas[22]);
    }

    /* ---------- Ayudas ---------- */

    private function enviar(array $datos = [])
    {
        return $this->actingAs($this->usuario)
            ->from(route('sse.registro'))
            ->post(route('sse.registro.guardar'), $datos + [
                'fecha' => '2026-11-18',
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

    private function crearProyecto(Estudiante $estudiante): Proyecto
    {
        return Proyecto::create([
            'estudiante_id' => $estudiante->id,
            'estado' => Proyecto::APROBADO,
            'institucion' => 'Centro Escolar de prueba',
            'fecha_inicio' => '2026-09-01',
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

    private function foto(string $nombre, string $tipo = 'image/jpeg', int $kb = 300): UploadedFile
    {
        return UploadedFile::fake()->create($nombre, $kb, $tipo);
    }

    private function disco()
    {
        return Storage::disk(config('horas-sociales.disco_evidencias'));
    }

    /** Fija la hora del reloj (en UTC). */
    private function ahora(string $utc): void
    {
        $momento = Carbon::parse($utc, 'UTC');
        Carbon::setTestNow($momento);
        CarbonImmutable::setTestNow($momento);
    }
}
