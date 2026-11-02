<?php

namespace App\Providers;

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // «composer run dev» también ejecuta el programador de tareas (actualización semanal y carga inicial DEIS).
        DevCommands::artisan('schedule:work', 'scheduler');
    }
}
