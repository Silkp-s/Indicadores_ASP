<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Módulo 6 · Motor de Alertas: evalúa el mes en curso cada mañana.
Schedule::command('alertas:evaluar')->dailyAt('06:00');
