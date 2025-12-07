<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Этот путь используется для "домашней" страницы после логина
     */
    public const HOME = '/home';

    /**
     * Зарегистрировать маршруты приложения.
     */
    public function boot(): void
    {
        $this->routes(function () {
            // API маршруты
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            // Web маршруты
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
