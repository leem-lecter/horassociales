<?php

namespace Itca\HorasSociales;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Itca\HorasSociales\Console\GenerarPeriodosNoHabiles;

/**
 * Punto de entrada del módulo. Laravel lo carga solo gracias a
 * "extra.laravel.providers" del composer.json del paquete.
 */
class HorasSocialesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/horas-sociales.php', 'horas-sociales');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'horas-sociales');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('horas-sociales.login_de_prueba')) {
            // Agrega carnet y rol a la tabla users del proyecto de pruebas.
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations-prueba');
            // Quien no ha iniciado sesión va a la pantalla de ingreso de prueba.
            Authenticate::redirectUsing(fn () => route('sse.ingreso'));
        }

        // Permiso para la pantalla de revisión del profesor.
        // La aplicación anfitriona puede definir el suyo y este no se registra.
        if (! Gate::has('sse-revisar')) {
            Gate::define('sse-revisar', function ($usuario) {
                $rol = $usuario->{config('horas-sociales.campo_rol')} ?? null;

                return in_array($rol, config('horas-sociales.roles_profesor'), true);
            });
        }

        if ($this->app->runningInConsole()) {
            $this->commands([GenerarPeriodosNoHabiles::class]);

            $this->publishes([
                __DIR__.'/../config/horas-sociales.php' => config_path('horas-sociales.php'),
            ], 'horas-sociales-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/horas-sociales'),
            ], 'horas-sociales-vistas');
        }
    }
}
