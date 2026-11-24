# Horas sociales ITCA-FEPADE

Formulario web para que los estudiantes de ITCA-FEPADE registren su Servicio
Social Estudiantil (SSE) y el profesor lo revise y apruebe.

## Qué es y para qué sirve

El profesor tiene una aplicación en Laravel donde lleva el control de las horas
sociales de cada estudiante. Hoy él mismo digita todo. Este proyecto agrega un
**módulo** para que sea el estudiante quien ingrese su información, y el
profesor solo la revise.

El módulo pasa a la web los formularios en papel del SSE:

|Formulario|Qué es|Pantalla del módulo|
|-|-|-|
|FBSSE-001|Proyecto de trabajo: datos del estudiante, institución, objetivos y horario|Proyecto de trabajo|
|FBSSE-002|Control diario de actividades, general y de medio ambiente (20 %)|Registro diario|
|FBSSE-003|Hoja de finalización|Finalización|

Cómo funciona:

1. El estudiante entra con su carnet y contraseña.
2. Llena su proyecto de trabajo y lo envía.
3. El profesor lo aprueba o no. Sin proyecto aprobado no se registran horas.
4. El estudiante registra cada día que hace servicio social.
5. El profesor aprueba o no las horas. El estudiante ve cuántas lleva y cuántas le faltan.
6. Al terminar, el estudiante llena la hoja de finalización.

**Importante:** el módulo no reemplaza las hojas en papel. Las hojas oficiales
se siguen llenando a mano, con tinta azul, firma y sello. Esto es para llevar
la cuenta de forma ordenada. La carta de solicitud (FBSSE-004) quedó fuera.

## Reglas que el módulo debe hacer cumplir

* Técnicos (2 años): 300 horas. Ingenierías y carreras de 5 años: 500 horas.
* Al menos el 20 % de las horas en proyectos de medio ambiente, con fotos de evidencia.
* Máximo 8 horas por día, sumando todos los registros del estudiante en esa fecha.
* Solo horas enteras.
* No se registran horas antes de que el profesor apruebe el proyecto.
* No se registran horas en fechas futuras ni anteriores al inicio del proyecto.
* No se hacen horas en días no hábiles: asuetos nacionales y vacaciones
completas de Semana Santa (Lunes Santo a Domingo de Resurrección), agosto
(1 al 6) y fin de año (24 de diciembre al 1 de enero).
* Las fechas de inicio y fin deben coincidir en todos los formularios.
* Hay 6 meses desde la finalización para entregar la documentación.

## Cómo está armado

Este repositorio es un proyecto Laravel de pruebas (`sse-host`) que hace el
papel de la aplicación del profesor. **Nuestro trabajo vive en una sola carpeta:**

```
packages/itca/horas-sociales/     <- EL MÓDULO. Aquí se trabaja.
├── config/horas-sociales.php     Metas de horas, límites y ajustes de integración
├── routes/
│   ├── web.php                   No se edita
│   ├── registro.php              Rutas de la Persona A
│   └── proyecto.php              Rutas de la Persona B
├── database/migrations/          Tablas con prefijo sse\_
├── database/seeders/             Datos de prueba y días no hábiles
├── resources/views/              Pantallas en Blade
│   ├── partials/marco.blade.php  Menú lateral (se activa solo, ver abajo)
│   ├── partials/estilos.blade.php  CSS de todo el módulo
│   └── panel.blade.php           "Mi avance": úsala como ejemplo de pantalla
└── src/
    ├── Models/                   Estudiante, Proyecto, Registro, Finalizacion, PeriodoNoHabil...
    ├── Http/Controllers/         Controladores
    ├── Support/                  CalendarioSalvadoreno y Resumen (cálculo de horas)
    └── Console/                  Comando sse:periodos
docs/prototipo-horas-sociales.html   <- Prototipo: así debe verse y comportarse cada pantalla
```

El resto del repositorio es Laravel sin cambios. No hace falta tocarlo.

Para ver el prototipo, abre `docs/prototipo-horas-sociales.html` con doble clic
y usa los botones "Entrar como estudiante" y "Entrar como profesor".

### Tablas

|Tabla|Contenido|
|-|-|
|`sse\_estudiantes`|Datos del estudiante, enlazado al usuario por el carnet|
|`sse\_proyectos`|Institución, supervisor, fecha de inicio, estado y resolución|
|`sse\_proyecto\_objetivos`|Actividades con objetivo y duración|
|`sse\_proyecto\_horarios`|Horario AM/PM de lunes a domingo|
|`sse\_registros`|Un día de servicio: fecha, actividad, horas, tipo y estado|
|`sse\_finalizaciones`|Datos de cierre de cada proyecto|
|`sse\_periodos\_no\_habiles`|Asuetos y vacaciones|

Estados de proyectos y finalizaciones: `borrador`, `enviado`, `aprobado`, `rechazado`.
Estados de registros: `pendiente`, `aprobado`, `rechazado`.
Tipos de registro: `general`, `ambiental`.

## Instalación después de clonar

Necesitas PHP 8.3 o más nuevo, Composer y Git. Todos los comandos funcionan en Git Bash.

```bash
git clone https://github.com/leem-lecter/horassociales.git
cd horassociales
composer install
cp .env.example .env
php artisan key:generate
```

Abre el archivo `.env` y revisa que tenga esta línea (agrégala al final si no está):

```env
SSE\_LOGIN\_PRUEBA=true
```

Después crea las tablas y carga los datos de prueba:

```bash
php artisan migrate
php artisan db:seed --class="Itca\\HorasSociales\\Database\\Seeders\\DatosDePruebaSeeder"
php artisan serve
```

Si `migrate` pregunta si quieres crear la base de datos SQLite, responde `yes`.

Abre http://127.0.0.1:8000/horas-sociales

|Usuario|Contraseña|Qué verás|
|-|-|-|
|251234|demo1234|Estudiante técnico con horas aprobadas, en revisión y una no aprobada|
|250042|demo1234|Estudiante de ingeniería con el proyecto esperando aprobación|
|249876|demo1234|Estudiante a punto de terminar|
|coordinador|demo1234|Profesor|

Por ahora usamos SQLite para no instalar nada más. El profesor usa MySQL, así
que antes de entregar hay que probarlo con MySQL (paso 8).

### Comandos útiles

```bash
php artisan migrate:fresh        # borra todo y crea las tablas de nuevo
php artisan route:list --path=horas-sociales
php artisan sse:periodos 2027    # genera los días no hábiles de un año
php artisan config:clear         # si cambiaste .env y no se nota
```

## Estado del proyecto

### Hecho

* \[x] **Paso 1.** Prototipo navegable en HTML (`docs/`).
* \[x] **Paso 2.** Tecnología confirmada con el profesor: Laravel y MySQL.
* \[x] **Paso 3.** Esqueleto del módulo: paquete, configuración, rutas, ingreso de prueba y pantalla "Mi avance".
* \[x] **Paso 4.** Base de datos: migraciones, modelos, días no hábiles y datos de prueba.

### Por hacer

|Paso|Persona A: \[Ali Velasquez]|Persona B: Miguel Aquino|
|-|-|-|
|5. Reglas|Reglas del registro diario: 8 horas por día, horas enteras, días no hábiles, proyecto aprobado|Reglas de fechas: inicio y fin coherentes, plazo de 6 meses|
|6. Pantallas del estudiante|Registro diario (FBSSE-002), con sus dos pestañas y las fotos de evidencia|Proyecto de trabajo (FBSSE-001), Finalización (FBSSE-003) y pantalla de Reglas|
|7. Vista del profesor|Aprobar o no aprobar horas, y tabla de avance por estudiante|Aprobar o no aprobar proyectos y finalizaciones|
|8. Entrega|Pruebas con MySQL|Guía de instalación para el profesor|

La Persona A no tiene que esperar a la B: los datos de prueba ya traen un
proyecto aprobado para registrar horas.

En el paso 7 los dos trabajan sobre la misma pantalla del profesor. Conviene
que la Persona A cree la pantalla y la ruta `sse.revision`, la una a `main`, y
después la Persona B agregue su sección.

## Cómo trabajar

### Reglas del equipo

1. **Nadie trabaja directo en `main`.** Cada tarea va en su propia rama.
2. **A `main` solo llega lo que funciona.** Pruébalo en el navegador antes de unirlo.
3. **Cada quien en sus archivos.** La Persona A edita `routes/registro.php` y la
Persona B `routes/proyecto.php`. Nadie edita `routes/web.php`.
4. **Avisa antes de tocar un archivo compartido:** `partials/estilos.blade.php`,
`partials/marco.blade.php`, `config/horas-sociales.php` o una migración.
5. **No se editan las migraciones que ya están en `main`.** Si necesitas una
columna nueva, crea una migración nueva.

### El ciclo de cada tarea

```bash
# 1. Antes de empezar, trae lo último
git checkout main
git pull

# 2. Crea tu rama
git checkout -b registro-diario

# 3. Trabaja y guarda seguido
git add .
git commit -m "Registro diario: formulario y validación de 8 horas"

# 4. Sube tu rama
git push -u origin registro-diario
```

Cuando la tarea funcione, en GitHub abre un **Pull Request** de tu rama hacia
`main`, pídele al otro que lo revise y únelo. Después los dos actualizan:

```bash
git checkout main
git pull
```

Si al traer cambios hubo migraciones o datos nuevos:

```bash
composer install
php artisan migrate
```

### Cómo agregar una pantalla

1. Crea el controlador en `src/Http/Controllers/`.
2. Agrega la ruta en tu archivo (`routes/registro.php` o `routes/proyecto.php`).
3. Crea la vista en `resources/views/` copiando la estructura de `panel.blade.php`:
el contenido va en `@section('sse-contenido')` y al final se incluye el marco
con el nombre de la ruta actual.
4. Copia el HTML de esa pantalla desde el prototipo. Las clases CSS ya existen.

El menú lateral muestra cada opción como "pendiente" hasta que existe su ruta.
Para que se active, la ruta debe llamarse exactamente así:

|Opción del menú|Nombre de la ruta|Quién|
|-|-|-|
|Proyecto de trabajo|`sse.proyecto`|B|
|Registro diario|`sse.registro`|A|
|Finalización|`sse.finalizacion`|B|
|Reglas|`sse.reglas`|B|
|Revisión de horas|`sse.revision`|A|

Dentro de los archivos de rutas el prefijo `sse.` ya está puesto, así que se
escribe solo `->name('registro')`.

### Piezas que ya existen y se pueden usar

* `Estudiante::delUsuario($request->user())`: el estudiante que inició sesión.
* `$proyecto->permiteRegistrarHoras()` y `$proyecto->editable()`.
* `PeriodoNoHabil::para($fecha)`: devuelve el asueto o vacación de esa fecha, o `null` si es día hábil.
* `Resumen::de($estudiante)`: horas aprobadas, en revisión, faltantes y de medio ambiente.
* `config('horas-sociales.max\_horas\_por\_dia')`, `metas`, `porcentaje\_ambiental`, `plazo\_entrega\_meses`.
* `Gate::allows('sse-revisar')`: verdadero si el usuario es profesor.

## Al final: instalarlo en la aplicación del profesor

Se copia `packages/itca/horas-sociales` a su proyecto, se instala con Composer y
se ajusta `config/horas-sociales.php` (columna del carnet, roles y plantilla).
Los detalles están en `packages/itca/horas-sociales/README.md`.
