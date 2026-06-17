<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
           $middleware->redirectGuestsTo('/login');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Permet d'écrire ->middleware('role:admin,receptionist') sur une route.
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Sans ça, les routes /api/* sont "stateless" : elles ignorent
        // complètement le cookie de session Laravel et n'acceptent qu'un
        // token Bearer explicite. Cette middleware reconnaît les requêtes
        // venant de notre propre frontend (domaines listés dans
        // config/sanctum.php 'stateful') et les traite comme authentifiées
        // via la session — c'est ce qui permet au login Fortify (session)
        // ET aux appels /api/* de partager la même connexion.
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
