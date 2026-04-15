<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {

        $router = $this->app['router'];
        $router->aliasMiddleware('api.auth', \App\Http\Middleware\ApiAuthenticate::class);

        $this->routes(function () {
            // Web routes - with session, CSRF, etc.
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // API routes - stateless
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));
        });
    }
}
