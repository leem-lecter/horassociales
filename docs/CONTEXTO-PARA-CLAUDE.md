# Contexto del proyecto: módulo de horas sociales ITCA-FEPADE

Archivo de exportación para continuar el proyecto en otro chat de Claude.
Actualizado: 26 de noviembre de 2026 (tras M6, antes del ajuste visual). Léelo completo antes de escribir código.

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
| Cierre del registro (R13, M5) | Con la hoja de finalización enviada o aprobada no se agregan ni se borran registros. Se abre otra vez si el profesor la devuelve (`rechazado`) |
| Hoja de finalización (M5) | Fecha de fin rellena con el último registro no rechazado; se rechaza si no coincide (R8). Horas en revisión: solo aviso. El estudiante no la reabre; solo el profesor al devolverla. Se finaliza el proyecto más reciente |
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
| M5 · Finalización (FBSSE-003) y pantalla de Reglas | Hecho y probado en el navegador (25 nov), rama `m5-finalizacion`. 69 pruebas en total |
| M6 · Vista del profesor | Hecho (26 nov): 87 pruebas pasan y probado en el navegador. Commit `05ee807` en la rama `m6-profesor`, subida a GitHub; **falta unirla a `main`**. Es una pantalla **solo para pruebas**: el profesor aprueba y lleva el registro desde su propia aplicación |
| MV · Ajuste visual (extra, antes de M7) | **Siguiente.** Se hace en otro chat; ver sección 7e y el pre-coding |
| M7 · Pruebas finales con MySQL, guía de instalación y README | Pendiente (después de MV) |

## 6. Qué hay en el módulo (`packages/itca/horas-sociales/`)

```
config/horas-sociales.php      Metas, % ambiental, máx. horas/día, plazo, columna del carnet, roles, plantilla
routes/web.php                 Grupo /horas-sociales, nombres sse.*. Incluye registro.php y proyecto.php
routes/proyecto.php            Rutas de M3 y M5 (proyecto, finalización y reglas)
routes/registro.php            Rutas de M4
routes/revision.php            Rutas de M6 (vista del profesor, con can:sse-revisar)
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
  finalizacion.blade.php       Hoja de finalización (M5)
  reglas.blade.php             Reglas y calendario de días no hábiles (M5)
  revision.blade.php           Bandeja del profesor y tabla de avance (M6)
  revision-estudiante.blade.php Ficha completa de un estudiante (M6)
  partials/resolver.blade.php  Comentario + Aprobar / No aprobar (M6); su JS en partials/resolver-script
  partials/aviso-fecha.blade.php     Aviso inmediato de fechas (R5), común a M3 y M4: sseAvisoFecha({...})
  partials/dias-no-habiles.blade.php "Ver días no hábiles" bajo cada campo de fecha
  ingreso.blade.php, sin-perfil.blade.php
src/
  HorasSocialesServiceProvider.php
  Models/                      Estudiante, Proyecto, ProyectoObjetivo, ProyectoHorario, Registro, Evidencia, Finalizacion, PeriodoNoHabil
  Http/Controllers/            IngresoPruebaController, PanelController, ProyectoController, RegistroController, FinalizacionController, ReglasController, RevisionController
  Http/Middleware/SoloEstudiante.php  Alias sse.estudiante: manda al profesor a Revisión desde las pantallas GET del estudiante
  Http/Requests/               ProyectoRequest, RegistroRequest, FinalizacionRequest, ResolucionRequest
  Rules/DiaHabil.php           R5: rechaza asuetos y vacaciones (reutilizable en M4)
  Support/                     CalendarioSalvadoreno, Resumen (cifras del panel), Fechas (hoy en El Salvador), HojaFila
tests/Feature/RegistroDiarioTest.php   Pruebas de M4 (php artisan test)
tests/Feature/FinalizacionTest.php     Pruebas de M5
tests/Feature/RevisionTest.php         Pruebas de M6
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
- `$proyecto->registroCerrado()` (R13) y `$proyecto->ultimoDiaRegistrado()` (R8, AAAA-MM-DD o null).
- `$finalizacion->editable()`, `$finalizacion->fechaLimiteEntrega()` y `Finalizacion::limitePara($fecha)` (R9, +6 meses sin desborde).
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

## 7c. Cómo funciona M5 (Finalización y Reglas)

- Rutas: `sse.finalizacion` (GET), `sse.finalizacion.guardar` (POST con `accion=borrador|enviar`), `sse.reglas` (GET, estudiante y profesor).
- R1 en `FinalizacionRequest::prepareForValidation`; R8 en el controlador, dentro de una transacción que bloquea al estudiante (el mismo bloqueo que R2).
- R13 en `RegistroRequest` (antes de validar), otra vez dentro de la transacción de `RegistroController::guardar`, y en `borrar`.
- La pantalla avisa al momento si la fecha de fin no coincide y recalcula la fecha límite.
- Para M6: al devolver la hoja, poner `estado = rechazado` y `comentario_coordinador`; eso reabre el registro diario.
- Hueco conocido: "Editar y volver a enviar" del proyecto (M3) todavía funciona con la hoja de finalización enviada. Se arregla en el chat de M3 con un control en `ProyectoController::reabrir`.
- Para devolver la hoja en pruebas (mientras no exista M6):
  `php artisan tinker --execute="Itca\HorasSociales\Models\Finalizacion::query()->update(['estado' => 'rechazado']);"`

## 7d. Cómo funciona M6 (Vista del profesor)

**Alcance (26 nov):** esta vista es solo para nuestras pruebas. El profesor ya aprueba y lleva
el registro de horas en su propia aplicación; lo que se fusiona con su proyecto son las pantallas
del estudiante. Pendiente para M7: cómo se apaga esta vista al instalar el módulo y cómo llegan
los datos del estudiante a la aplicación del profesor.

- Rutas (`routes/revision.php`, todas con `can:sse-revisar`; un estudiante recibe 403):
  `sse.revision` (GET, `?buscar=`), `sse.revision.estudiante` (GET, ficha, `?pestana=`),
  `sse.revision.proyecto`, `sse.revision.registro`, `sse.revision.finalizacion` (POST con `decision=aprobar|rechazar` y `comentario`),
  `sse.revision.registros` (POST con `ids[]`, aprobar varias).
- Cada decisión guarda `estado`, `comentario_coordinador`, `resuelto_at` y `resuelto_por` y vuelve con `back()`.
- Decisiones (26 nov): comentario obligatorio al no aprobar o devolver; la hoja de finalización no se aprueba
  con horas pendientes ni si su fecha ya no es el último día con horas (R8); un registro ya resuelto se puede cambiar
  mientras el registro diario no esté cerrado (R13), y al volver a aprobar uno no aprobado se revisa R2; aprobar en lote con casillas.
- Solo se resuelve lo que espera (proyecto `enviado`, registro `pendiente`, hoja `enviada`); si no, "Esto ya fue revisado".
- El profesor que abre Mi avance, Proyecto, Registro o Finalización va a Revisión (middleware `sse.estudiante`).
  Las dos pruebas viejas que esperaban un aviso para el profesor ahora esperan esa redirección.

## 7e. Cómo está hecho el diseño visual hoy (para el ajuste visual)

Datos del 26 nov, para que el chat del ajuste visual no tenga que descubrirlos.

- **Un solo CSS:** `resources/views/partials/estilos.blade.php` (170 líneas, dentro de una etiqueta `<style>`).
  Cada pantalla lo incluye con `@include('horas-sociales::partials.estilos')` al final.
- **Variables en `:root`:** colores (`--papel`, `--hoja`, `--tinta`, `--gris`, `--linea`, `--azul`, `--azul-suave`,
  `--sobre-azul`, `--ok`, `--aviso`, `--mal` y sus versiones `-suave`) y tipografías (`--display`, `--texto`, `--mono`).
  Modo oscuro: definido dos veces (por `prefers-color-scheme` y por `data-theme="dark"`); nada en el módulo pone `data-theme`.
- **Tipografías:** Archivo, IBM Plex Sans e IBM Plex Mono, cargadas desde Google Fonts en `layouts/modulo.blade.php`
  (solo cuando se usa la plantilla propia del módulo).
- **Idea de diseño actual:** "cuaderno de control": papel frío, tinta azul como único acento, cada pantalla rotulada con
  el código de su formulario (`FBSSE-00X`) en la clase `.codigo`.
- **Estructura:** `partials/marco.blade.php` (menú lateral + contenido, clases `.marco`, `.lateral`, `.nav`, `.principal`)
  y, dentro, `.pila` con secciones `.hoja`. Piezas repetidas: `.cabecera`, `.bajada`, `.nota` (`.aviso`, `.mal`, `.ok`),
  `.pastilla`, `.boton` (`.suave`, `.plano`, `.chico`), `.campo`, `.rejilla`, `.desliza` (tabla con scroll), `.cifras`,
  `.barra`, `.pestanas`, `.dos` (formulario + tabla), `.resolver` y `.datos` (M6).
- **Responsive:** un solo punto de corte, `max-width:860px` (menú arriba, `.dos` en una columna).
- **Estilos en línea:** 78 atributos `style="..."` repartidos en las vistas (los más cargados: `revision-estudiante` 26,
  `proyecto` 14, `revision` 12, `panel` 8, `registro` 8, `reglas` 5, `finalizacion` 2, `ingreso` 2, `resolver` 1).
- **Scripts que dependen de clases, ids o `data-*`:** `partials/aviso-fecha`, `registro`, `proyecto`, `finalizacion`
  y `partials/resolver-script`. Si se renombra una clase o un `data-*`, hay que cambiarlo también en su script.
- **Pruebas que miran textos de la pantalla:** unas 45 aserciones `assertSee` / `assertDontSee` en `tests/Feature`.
  Un cambio de texto visible puede romper una prueba: se actualiza en el mismo cambio.
- **Riesgo para la fusión con la app del profesor:** `estilos.blade.php` define reglas globales (`html, body`, `h1, h2, h3`,
  `a`, `input, select, textarea`, `table`, `p`) que, con la plantilla del profesor, afectarían a toda su página. Conviene
  decidir en el ajuste visual si se aíslan bajo un contenedor propio del módulo.

## 8. Estado de Git (26 nov)

- `main`: `d6f5f9c` "M4: registro diario", ya subido a GitHub (M3 y M4 completos).
- M5 ya está en `main` (`0746eea`). M6 está en la rama `m6-profesor` (commit `05ee807`, subida a GitHub), **todavía sin unir a `main`**.
- Antes del ajuste visual, en Git Bash dentro de `sse-host`:
  `git checkout main`, `git pull`, `git merge m6-profesor`, `php artisan test`, `git push`,
  y después `git checkout -b ajuste-visual`.
- Si `composer update` modifica `composer.lock` solo por el nombre de la rama,
  se descarta con `git restore composer.lock`.
- Limpieza del 26 nov: ya no quedan archivos sueltos de Claude (se borró `storage/app/claude-copia.tgz`,
  y las carpetas `Claude outputs/` y `modulo-horas-sociales/` ya no existían). También se quitó
  `packages/itca/horas-sociales/pruebas/calendario.php`, un script de prueba de la primera versión
  que corría sin Laravel; queda en el historial de Git.

## 9. Problemas conocidos y su solución

| Síntoma | Causa | Solución |
| --- | --- | --- |
| `include(...vendor/composer/../itca/horas-sociales/src/HorasSocialesServiceProvider.php): Failed to open stream` | El acceso directo `vendor/itca/horas-sociales` se rompió al copiar o mover el proyecto | `rm -rf vendor/itca/horas-sociales` y `composer update itca/horas-sociales` |
| `Route [login] not defined` | Falta `SSE_LOGIN_PRUEBA=true` en `.env` | Agregarlo y `php artisan config:clear` |
| Un cambio en `.env` no se nota | Configuración en caché | `php artisan config:clear` |
| Git abre una pantalla negra con `~` | Editor Vim | `Esc`, `:wq`, `Enter` (o `git config --global core.editor "nano"`) |
| `(main\|MERGING)` en el indicador | Unión a medias | Resolver el conflicto, `git add`, `git commit` antes de crear ramas |

## 10. Qué sigue

**MV · Ajuste visual (extra, antes de M7).** Se cambia el diseño visual de todo el módulo, en otro chat, en la rama
`ajuste-visual`. Solo cambia la apariencia: no cambian reglas, rutas, nombres de campos ni lógica. Los cambios concretos los
da el usuario en ese chat. Su pre-coding está en el documento de pre-coding ("Pre-coding del ajuste visual").
**M7** Recorrido completo con MySQL, revisión en celular, guía de instalación para el profesor y README.
Pendiente para M7: cómo la app del profesor lee y aprueba lo que guarda el módulo, y poder apagar la vista de revisión
(M6) al instalar el módulo.

Antes de cada módulo se hace su pre-coding (sección nueva en el documento de
pre-coding), se confirman las decisiones y después se programa.

## 11. Mensajes para abrir un chat nuevo

**Ajuste visual (el siguiente):**

```
Vamos a hacer el ajuste visual de todo el proyecto de horas sociales (entre M6 y M7).
Lee primero el archivo de contexto: sse-host/docs/CONTEXTO-PARA-CLAUDE.md (en especial las secciones 7e y 10)
y el pre-coding, sección "Pre-coding del ajuste visual": https://claude.ai/artifact/7b3B7f4BKwuwZg9887mLoP
El proyecto está en C:\Users\eemma\Documents\Horas sociales proyecto\sse-host

Esto es solo apariencia: no se cambian reglas, rutas, nombres de campos ni lógica.
No toques nada todavía. Cuando termines de leer, dime en pocas líneas qué entendiste y
espera: voy a darte los cambios visuales que quiero. Con ellos, haz primero el pre-coding
del ajuste (qué archivos cambian y en qué orden), anótalo en el documento de pre-coding,
y pídeme confirmar las decisiones antes de programar. Trabaja en la rama ajuste-visual
(sale de main con M6 ya unido) y corre php artisan test al terminar cada grupo de pantallas.
```

**M7 (después del ajuste visual):**

```
Vamos a continuar el proyecto de horas sociales con el M7 · Pruebas finales y entrega.
Lee primero el archivo de contexto: sse-host/docs/CONTEXTO-PARA-CLAUDE.md
y el pre-coding: https://claude.ai/artifact/7b3B7f4BKwuwZg9887mLoP
El proyecto está en C:\Users\eemma\Documents\Horas sociales proyecto\sse-host
```

Abrir cada chat en la aplicación de escritorio, en esta computadora, y conectar
la carpeta `sse-host`.
