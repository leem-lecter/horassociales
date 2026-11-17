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

    // Disco donde se guardan las fotos de evidencia de medio ambiente.
    'disco_evidencias' => env('SSE_DISCO_EVIDENCIAS', 'public'),

    /*
    | Solo para el proyecto de pruebas
    | Activa una pantalla de ingreso con carnet y contraseña, y agrega las
    | columnas carnet y rol a la tabla users. Debe quedar en false al
    | instalar el módulo en la aplicación del profesor.
    */
    'login_de_prueba' => (bool) env('SSE_LOGIN_PRUEBA', false),

];
