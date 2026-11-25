# Contexto del proyecto: módulo de horas sociales ITCA-FEPADE

Archivo de exportación para continuar el proyecto en otro chat de Claude.
Actualizado: 25 de noviembre de 2026. Léelo completo antes de escribir código.

---

## 1. Qué es

Un módulo de Laravel donde el estudiante de ITCA-FEPADE registra su Servicio
Social Estudiantil (SSE) y el profesor lo aprueba. Pasa a la web los formularios
en papel FBSSE-001 (proyecto de trabajo), FBSSE-002 (control diario de horas) y
FBSSE-003 (finalización). Se acopla después a la aplicación Laravel + MySQL que
ya tiene el profesor. Lo hace una sola persona (el usuario), con ayuda de Claude.
Entrega: esta misma semana (la del 23 de noviembre de 2026); el día exacto no
está confirmado.

## 2. Dónde está cada cosa

| Qué | Dónde |
| --- | --- |
| Proyecto Laravel de pruebas | `C:\Users\eemma\Documents\Horas sociales proyecto\sse-host` (computadora de la sala; la laptop anterior se averió) |
| El módulo (lo único que se entrega) | `sse-host/packages/itca/horas-sociales/` |
| Prototipo HTML (cómo se ve y se comporta cada pantalla) | `sse-host/docs/prototipo-horas-sociales.html` |
| Pre-coding (plan, reglas, módulos; se marca ahí lo terminado) | https://claude.ai/artifact/7b3B7f4BKwuwZg9887mLoP |
| Repositorio de GitHub (decidido) | https://github.com/leem-lecter/horassociales (rama `main`) |
| Este archivo | `sse-host/docs/CONTEXTO-PARA-CLAUDE.md` |

Ojo: el pre-coding dice `sse-app`, pero la carpeta real se llama `sse-host`.
El README de `sse-host` todavía habla de "Persona A / Persona B" (era un trabajo
en equipo); hoy lo hace una sola persona y ese reparto ya no aplica.

## 3. Cómo se trabaja

- Claude escribe y revisa el código en la carpeta conectada. Claude **no puede**
  ejecutar Composer ni Laravel desde su espacio (Packagist está bloqueado).
- El usuario ejecuta los comandos en **Git Bash** dentro de `sse-host`
  (`composer`, `php artisan`, `git`), prueba en el navegador y pega capturas o
  errores completos.
- Un módulo por chat. "Terminado" = probado en el navegador, no solo escrito.
- Cada módulo: rama propia o commit propio, probarlo, `git commit`, `git push`,
  y marcarlo en el pre-coding.
- Cualquier cambio de diseño o de regla se anota primero en el pre-coding.
- Las migraciones que ya están en `main` no se editan: si falta una columna, se
  crea una migración nueva.
- Si Git abre el editor Vim (pantalla negra con `~`): `Esc`, luego `:wq` y `Enter`.
- El chat donde se hizo M3 queda reservado solo para cambios a la pantalla
  "Proyecto de trabajo". Los demás módulos se hacen en otro chat.

Para arrancar la app:
```bash
php artisan serve
# http://127.0.0.1:8000/horas-sociales
```

| Usuario | Contraseña | Qué es |
| --- | --- | --- |
| 251234 | demo1234 | Estudiante técnico con proyecto aprobado y horas aprobadas, en revisión y una no aprobada |
| 250042 | demo1234 | Estudiante de ingeniería con el proyecto enviado (esperando aprobación) |
| 249876 | demo1234 | Estudiante a punto de terminar |
| coordinador | demo1234 | Profesor |

Base de datos de pruebas: SQLite (`database/database.sqlite`). El profesor usa
MySQL: hay que probarlo con MySQL en M7.

## 4. Decisiones tomadas (no volver a preguntarlas)

| Tema | Decisión |
| --- | --- |
| Forma | Paquete de Composer acoplable a la app del profesor |
| Tecnología | Laravel 13, PHP 8.3+, MySQL. Vistas en Blade con CSS propio, sin Vue, Livewire ni Vite |
| Acceso | Carnet y contraseña. Las cuentas las crea la app del profesor, no el módulo |
| Meta de horas | 300 técnicos (2 años); 500 ingenierías y carreras de 5 años |
| Medio ambiente | Al menos el 20 % de la meta, con fotos de evidencia |
| Días no hábiles | Asuetos nacionales + vacaciones completas de Semana Santa (Lunes Santo a Domingo de Resurrección), agosto (1 al 6) y fin de año (24 dic al 1 ene). Se pueden agregar periodos manuales |
| Aprobación | El profesor aprueba el proyecto antes de las horas, y después cada registro |
| Firmas y sellos | Fuera del módulo: siguen a mano en papel |
| Carta de solicitud (FBSSE-004) | Fuera del módulo |
| Dispositivos | Pensado para computadora, usable en celular |
| Carnet en el formulario | **Bloqueado** para el estudiante, también al editar. Es la llave que une la cuenta con sus horas: si cambiara, el módulo no encontraría su perfil. Si hay que corregirlo, lo hace el profesor |
| Campos numéricos | Teléfonos (8 cifras), año académico (4) y duración de objetivos (horas) solo aceptan números. "Tiempo estimado" queda como texto libre |
| Horario del proyecto | Formato fijo `08:00 a 12:00`; el estudiante teclea solo números. Reloj de 12 horas en cada fila AM/PM |
| Fechas | Aviso inmediato en pantalla si la fecha es día no hábil, además de la validación del servidor |
| Fotos de evidencia (M4) | Privadas (disco `local`), solo las ven el dueño y el profesor. La pantalla las achica antes de enviar. Hasta 5 fotos de 2 MB |
| Corregir un registro (M4) | No hay botón Editar: se borra y se vuelve a crear (solo si no está aprobado) |
| Hoja · fila (M4) | Por fecha, como la hoja en papel; un día viejo registrado tarde corre las filas siguientes |
| "Hoy" | Se calcula en hora de El Salvador (`zona_horaria`), aunque la app esté en UTC |

## 5. Estado por módulo

| Módulo | Estado |
| --- | --- |
| Pre-coding | Hecho (19 nov) |
| M0 · Preparación (proyecto, paquete, ingreso de prueba) | Hecho (venía de la primera versión) |
| M1 · Base de datos (tablas, modelos, calendario, `sse:periodos`, datos de prueba) | Hecho (primera versión) |
| M2 · Plantilla y panel "Mi avance" | Hecho (primera versión) |
| M3 · Proyecto de trabajo (FBSSE-001) | Hecho, probado y subido (commit `6a64e30`) |
| M4 · Registro diario (FBSSE-002) | Hecho, probado en el navegador y revisado una segunda vez (25 nov), con 40 pruebas automáticas. Subido a `main` (commit `d6f5f9c`) |
| M5 · Finalización (FBSSE-003) y pantalla de Reglas | **Siguiente** |
| M6 · Vista del profesor | Pendiente |
| M7 · Pruebas finales con MySQL, guía de instalación y README | Pendiente |

## 6. Qué hay en el módulo (`packages/itca/horas-sociales/`)

```
config/horas-sociales.php      Metas, % ambiental, máx. horas/día, plazo, columna del carnet, roles, plantilla
routes/web.php                 Grupo /horas-sociales, nombres sse.*. Incluye registro.php y proyecto.php
routes/proyecto.php            Rutas de M3 (aquí también irán finalización y reglas)
routes/registro.php            Rutas de M4 (aquí también irá la revisión del profesor, M6)
database/migrations/           6 archivos, 8 tablas con prefijo sse_ (la 6.ª es de M4: fotos)
database/migrations-prueba/    Agrega carnet y rol a users (solo con SSE_LOGIN_PRUEBA=true)
database/seeders/              DatosDePruebaSeeder, PeriodosNoHabilesSeeder
resources/views/
  layouts/modulo.blade.php     Plantilla propia (si config 'layout' es null)
  partials/estilos.blade.php   Todo el CSS (clases del prototipo)
  partials/marco.blade.php     Menú lateral: una opción se activa sola cuando existe su ruta
  partials/campo.blade.php     Campo con etiqueta y error (parámetros: tipo, opciones, numerico, ayuda, req, dis, cls)
  panel.blade.php              "Mi avance" (M2): úsala como modelo de pantalla
  proyecto.blade.php           Proyecto de trabajo (M3)
  registro.blade.php           Registro diario (M4)
  partials/aviso-fecha.blade.php     Aviso inmediato de fechas (R5), común a M3 y M4: sseAvisoFecha({...})
  partials/dias-no-habiles.blade.php "Ver días no hábiles" bajo cada campo de fecha
  ingreso.blade.php, sin-perfil.blade.php
src/
  HorasSocialesServiceProvider.php
  Models/                      Estudiante, Proyecto, ProyectoObjetivo, ProyectoHorario, Registro, Evidencia, Finalizacion, PeriodoNoHabil
  Http/Controllers/            IngresoPruebaController, PanelController, ProyectoController, RegistroController
  Http/Requests/               ProyectoRequest, RegistroRequest
  Rules/DiaHabil.php           R5: rechaza asuetos y vacaciones (reutilizable en M4)
  Support/                     CalendarioSalvadoreno, Resumen (cifras del panel), Fechas (hoy en El Salvador), HojaFila
tests/Feature/RegistroDiarioTest.php   Pruebas de M4 (php artisan test)
  Console/                     Comando sse:periodos
```

Tablas: `sse_estudiantes`, `sse_proyectos`, `sse_proyecto_objetivos`,
`sse_proyecto_horarios`, `sse_registros`, `sse_registro_evidencias`,
`sse_finalizaciones`, `sse_periodos_no_habiles`. (`sse_registros.evidencia_ruta`
quedó sin usar: las fotos van en `sse_registro_evidencias`.)

Valores fijos:
- Estados de proyecto y finalización: `borrador`, `enviado`, `aprobado`, `rechazado`.
- Estados de registro: `pendiente`, `aprobado`, `rechazado` (un rechazado no cuenta para el total ni para el límite diario).
- Tipos de registro: `general`, `ambiental`.
- Tipo de carrera: `tecnico` (300), `ingenieria` (500).

Piezas listas para usar:
- `Estudiante::delUsuario($request->user())`: perfil del estudiante que inició sesión (lo crea si no existe).
- `$proyecto->permiteRegistrarHoras()` (R1) y `$proyecto->editable()` (R10).
- `PeriodoNoHabil::para($fecha)`: el periodo no hábil de esa fecha (el más corto si hay varios) o `null`.
- `PeriodoNoHabil::paraPantalla()`: todos los periodos como `[nombre, inicio, fin]` para las pantallas.
- `Fechas::hoy()` (AAAA-MM-DD en El Salvador, aunque la app esté en UTC) y `Fechas::larga($f)` ("18 de noviembre de 2026").
- `$registro->borrable()` (R11) y `$registro->borrarConFotos()`.
- `new \Itca\HorasSociales\Rules\DiaHabil`: regla de validación para R5.
- `Resumen::de($estudiante)`: horas aprobadas, en revisión, faltantes y de medio ambiente.
- `config('horas-sociales.max_horas_por_dia')`, `metas`, `porcentaje_ambiental`, `plazo_entrega_meses`,
  `filas_por_hoja`, `zona_horaria`, `disco_evidencias` (`local`, privado), `max_fotos` (5), `max_kb_foto` (2048).
- `Gate::allows('sse-revisar')`: verdadero si el usuario es profesor.

Para que una pantalla nueva siga el diseño: el contenido va en
`@section('sse-contenido')` y al final se incluyen `partials.estilos` y
`partials.marco` con el nombre de la ruta actual (copiar el final de `panel.blade.php`).

## 7. Cómo funciona M3 (Proyecto de trabajo)

- Rutas: `sse.proyecto` (GET, muestra), `sse.proyecto.guardar` (POST con
  `accion=borrador|enviar`), `sse.proyecto.reabrir` (POST, "Editar y volver a enviar").
- Borrador: solo revisa formatos, se puede guardar a medias. Enviar: pide todo,
  al menos una actividad completa y la declaración marcada.
- R10: enviado o aprobado no se edita (campos bloqueados y el servidor lo rechaza).
  "Editar y volver a enviar" lo regresa a borrador sin perder datos; si estaba
  aprobado pide confirmación, porque deja de permitir registrar horas.
- R5: la fecha de inicio no puede ser día no hábil, con aviso inmediato en pantalla.
- La fecha de inicio no puede quedar después de la primera hora registrada.
- La sección 1 guarda los datos en `sse_estudiantes` (agrega tipo de carrera,
  año de estudios, jornada y modalidad, que el prototipo no traía).
- Limitación conocida: la pantalla trabaja con el proyecto más reciente del
  estudiante. Varios proyectos (uno por institución, regla 19) necesitarían una lista.

## 7b. Cómo funciona M4 (Registro diario)

- Rutas: `sse.registro` (GET, `?pestana=general|ambiental`), `sse.registro.guardar` (POST),
  `sse.registro.borrar` (DELETE), `sse.registro.foto` (GET, solo dueño y profesor).
- R1 se revisa primero (en `RegistroRequest::prepareForValidation`); R3, R4, R5 y R7 en
  `RegistroRequest`; R2 en el controlador, dentro de una transacción que bloquea al estudiante.
- La tabla muestra los registros del proyecto actual; la suma de R2 incluye todos sus proyectos.
- Fotos: privadas, en `sse-evidencias/{estudiante}/{registro}/` del disco `local`
  (`storage/app/private`). La pantalla las achica a 1600 px en JPEG antes de enviarlas.
  Si el envío pasa `post_max_size`, vuelve a la pantalla con `?error=fotos` y un aviso.
- No hay botón Editar: un registro pendiente o no aprobado se borra y se vuelve a crear.
- "Hoja · fila" se calcula por fecha (20 renglones por hoja); los rechazados no ocupan renglón.
- Pruebas: `php artisan test` (42 en total, 40 de M4; `phpunit.xml` incluye la carpeta `tests` del paquete
  y `SSE_LOGIN_PRUEBA=true`).

## 8. Estado de Git (25 nov)

- `main`: `d6f5f9c` "M4: registro diario", ya subido a GitHub (M3 y M4 completos).
- Para M5: `git checkout -b m5-finalizacion` antes de empezar.
- Si `composer update` modifica `composer.lock` solo por el nombre de la rama,
  se descarta con `git restore composer.lock`.
- `storage/app/claude-copia.tgz` (vacío) lo dejó Claude al copiar el proyecto; está fuera de Git y se puede borrar.
- En `sse-host` hay dos carpetas sueltas fuera de Git: `Claude outputs/` (el zip de M4) y
  `modulo-horas-sociales/` (copia vieja del módulo). No usar `git add .` mientras existan;
  se pueden borrar.

## 9. Problemas conocidos y su solución

| Síntoma | Causa | Solución |
| --- | --- | --- |
| `include(...vendor/composer/../itca/horas-sociales/src/HorasSocialesServiceProvider.php): Failed to open stream` | El acceso directo `vendor/itca/horas-sociales` se rompió al copiar o mover el proyecto | `rm -rf vendor/itca/horas-sociales` y `composer update itca/horas-sociales` |
| `Route [login] not defined` | Falta `SSE_LOGIN_PRUEBA=true` en `.env` | Agregarlo y `php artisan config:clear` |
| Un cambio en `.env` no se nota | Configuración en caché | `php artisan config:clear` |
| Git abre una pantalla negra con `~` | Editor Vim | `Esc`, `:wq`, `Enter` (o `git config --global core.editor "nano"`) |
| `(main\|MERGING)` en el indicador | Unión a medias | Resolver el conflicto, `git add`, `git commit` antes de crear ramas |

## 10. Qué sigue

**M5** Finalización (R8 la fecha de fin coincide con el último día registrado;
R9 aviso de la fecha límite, 6 meses después) y pantalla de Reglas con el calendario
de días no hábiles del año (el enlace "Ver días no hábiles" ya existe en M3 y M4).
Pendiente de decidir en su pre-coding: una vez enviada la hoja de finalización, M4
todavía deja agregar o borrar registros, y eso puede mover el "último día registrado" (R8).
**M6** Vista del profesor (`sse.revision`): aprobar o rechazar proyectos, horas
y finalizaciones con comentario; tabla de avance por estudiante (R12). El profesor
ya puede abrir las fotos con `sse.registro.foto`.
**M7** Recorrido completo con MySQL, revisión en celular, guía de instalación
para el profesor y README.

Antes de cada módulo se hace su pre-coding (sección nueva en el documento de
pre-coding), se confirman las decisiones y después se programa.

## 11. Mensaje para abrir el chat nuevo

```
Vamos a continuar el proyecto de horas sociales con el M5 · Finalización y Reglas.
Lee primero el archivo de contexto: sse-host/docs/CONTEXTO-PARA-CLAUDE.md
y el pre-coding: https://claude.ai/artifact/7b3B7f4BKwuwZg9887mLoP
El proyecto está en C:\Users\eemma\Documents\Horas sociales proyecto\sse-host
```
Abrir ese chat en la aplicación de escritorio, en esta computadora, y conectar
la carpeta `sse-host`.
