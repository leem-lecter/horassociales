<?php

/*
|--------------------------------------------------------------------------
| Módulo de horas sociales (SSE) de ITCA-FEPADE
|--------------------------------------------------------------------------
| Todo lo que cambia al instalar el módulo en otra aplicación está aquí.
| Para editarlo en la aplicación anfitriona:
|   php artisan vendor:publish --tag=horas-sociales-config
*/

return [

    // Dirección donde vive el módulo: https://sitio/horas-sociales
    'prefijo' => env('SSE_PREFIJO', 'horas-sociales'),

    // Middleware de las rutas. 'auth' usa el inicio de sesión de la aplicación anfitriona.
    'middleware' => ['web', 'auth'],

    // Plantilla visual. null = la plantilla propia del módulo.
    // Para usar la del profesor, por ejemplo: 'layouts.app'
    'layout' => env('SSE_LAYOUT'),

    // Nombre de la sección donde esa plantilla imprime el contenido (@yield('content')).
    'seccion' => env('SSE_SECCION', 'content'),

    /*
    | Usuarios de la aplicación anfitriona
    | El módulo no crea cuentas: lee al usuario que ya inició sesión.
    */
    'modelo_usuario' => env('SSE_MODELO_USUARIO', 'App\\Models\\User'),
    'campo_carnet' => env('SSE_CAMPO_CARNET', 'carnet'),   // columna del usuario con el carnet
    'campo_rol' => env('SSE_CAMPO_ROL', 'rol'),             // columna del usuario con el rol
    'roles_profesor' => ['profesor', 'coordinador', 'admin'],

    /*
    | Reglas del Servicio Social Estudiantil
    */
    'metas' => [
        'tecnico' => 300,      // carreras técnicas de 2 años
        'ingenieria' => 500,   // ingenierías y carreras de 5 años
    ],
    'porcentaje_ambiental' => 20,   // regla 21
    'max_horas_por_dia' => 8,       // regla 16
    'plazo_entrega_meses' => 6,     // regla 18
    'filas_por_hoja' => 20,         // renglones de la hoja FBSSE-002 en papel

    // "Hoy" se calcula en esta zona horaria, aunque la aplicación esté en UTC (R4).
    'zona_horaria' => env('SSE_ZONA_HORARIA', 'America/El_Salvador'),

    /*
    | Fotos de evidencia de medio ambiente (R7)
    | 'local' es privado: las fotos solo se ven por la ruta del módulo, y solo
    | las abren el estudiante dueño y el profesor.
    | La pantalla reduce cada foto a 1600 px antes de enviarla, así cabe en el
    | límite de PHP (upload_max_filesize = 2M, post_max_size = 8M por defecto).
    */
    'disco_evidencias' => env('SSE_DISCO_EVIDENCIAS', 'local'),
    'max_fotos' => 5,          // por registro
    'max_kb_foto' => 2048,     // 2 MB por foto

    /*
    | Solo para el proyecto de pruebas
    | Activa una pantalla de ingreso con carnet y contraseña, y agrega las
    | columnas carnet y rol a la tabla users. Debe quedar en false al
    | instalar el módulo en la aplicación del profesor.
    */
    'login_de_prueba' => (bool) env('SSE_LOGIN_PRUEBA', false),

];
