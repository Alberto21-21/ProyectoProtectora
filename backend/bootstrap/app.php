<?php

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\VeterinarioAutorizadoParaAnimal;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum', 'activa']]
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ALIAS DE LOS MIDDLEWARE PARA LAS RUTAS
        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'todas' => CheckAbilities::class,
            'alguna' => CheckForAnyAbility::class,
            'activa' => CuentaActiva::class,
            'veterinarioAnimal' => VeterinarioAutorizadoParaAnimal::class,
        ]);

        $middleware->redirectGuestsTo('/api/nologin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) =>
                $request->is('api/*') || $request->expectsJson(),
        );

        // RESPUESTA CUANDO FALTA UN PERMISO EN EL TOKEN
        $exceptions->render(function (MissingAbilityException $error) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta operación.'
            ], 403);
        });
    })->create();