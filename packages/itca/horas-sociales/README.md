# itca/horas-sociales

Módulo de Laravel para que los estudiantes de ITCA-FEPADE registren su
Servicio Social Estudiantil (SSE) y el profesor lo revise. Pasa a la web los
formularios FBSSE-001 (proyecto de trabajo), FBSSE-002 (control diario) y
FBSSE-003 (finalización). Las hojas en papel se siguen llenando a mano.

Requisitos: PHP 8.3+, Laravel 12 o 13, MySQL.

## Cómo está organizado

```
config/horas-sociales.php      Todo lo que cambia entre aplicaciones
routes/web.php                 Rutas bajo /horas-sociales, con nombres sse.*
database/migrations/           Tablas con prefijo sse_ (no tocan las del anfitrión)
database/migrations-prueba/    Solo para el proyecto de pruebas
database/seeders/              Días no hábiles y datos de prueba
resources/views/               Pantallas en Blade, con el diseño del prototipo
src/Models/                    Estudiante, Proyecto, Registro, Finalizacion, PeriodoNoHabil...
src/Support/                   CalendarioSalvadoreno y Resumen (cálculo de horas)
src/Console/                   Comando sse:periodos
```

## Tablas

| Tabla | Formulario | Contenido |
|---|---|---|
| `sse_estudiantes` | FBSSE-001 y 003 | Datos del estudiante, enlazado al usuario por el carnet |
| `sse_proyectos` | FBSSE-001 | Institución, supervisor, fecha de inicio, estado y resolución |
| `sse_proyecto_objetivos` | FBSSE-001 §3 | Actividades con objetivo y duración |
| `sse_proyecto_horarios` | FBSSE-001 §4 | Horario AM/PM de lunes a domingo |
| `sse_registros` | FBSSE-002 | Un día de servicio: fecha, actividad, horas, tipo (general o ambiental), estado |
| `sse_finalizaciones` | FBSSE-003 | Datos de cierre de cada proyecto |
| `sse_periodos_no_habiles` | Regla nueva | Asuetos y vacaciones en que no se hacen horas |

Estados de proyectos y finalizaciones: `borrador`, `enviado`, `aprobado`, `rechazado`.
Estados de registros: `pendiente`, `aprobado`, `rechazado`.

## Días no hábiles

`php artisan sse:periodos` genera, para el año actual y el siguiente:

- Asuetos del art. 190 del Código de Trabajo: 1 ene, Jueves/Viernes/Sábado Santo,
  1 may, 10 may, 17 jun, 6 ago, 15 sep, 2 nov, 25 dic.
- Vacaciones completas: Semana Santa (Lunes Santo a Domingo de Resurrección),
  agosto (1 al 6) y fin de año (24 dic al 1 ene).

Semana Santa se calcula sola. El profesor puede agregar otros periodos en la
tabla con `origen = manual`; el comando no los toca.

## Instalarlo en la aplicación del profesor

1. Copiar `packages/itca/horas-sociales` a su proyecto, agregar el repositorio
   `path` en su `composer.json` y correr `composer require "itca/horas-sociales:@dev"`.
2. `php artisan vendor:publish --tag=horas-sociales-config` y ajustar:
   - `campo_carnet`: columna de su tabla de usuarios que guarda el carnet.
   - `campo_rol` y `roles_profesor`: cómo se reconoce al profesor. Si su
     aplicación ya maneja permisos, puede definir el Gate `sse-revisar` y el
     módulo usa ese.
   - `layout` y `seccion`: su plantilla (por ejemplo `layouts.app`) y la sección
     donde imprime el contenido, para que las pantallas se vean dentro de su menú.
3. `php artisan migrate` y `php artisan sse:periodos`.
4. Dejar `SSE_LOGIN_PRUEBA` sin definir: el módulo usa su inicio de sesión.
