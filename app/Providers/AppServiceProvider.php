<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- Añade esta línea

class AppServiceProvider extends ServiceProvider
{
    public function register(): void { /* ... */ }

    public function boot(): void
    {
        // Forzar HTTPS si estamos en el entorno de Azure
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
