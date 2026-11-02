<?php

use App\Services\DeisActualizacionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Inteligencia DEIS
|--------------------------------------------------------------------------
|
| Actualización semanal de los tres datasets DEIS (el DEIS publica urgencias
| los lunes y defunciones los martes, por lo que se actualiza el miércoles de
| madrugada) y carga inicial automática de los que aún no tienen datos. Ambas
| tareas solo encolan importaciones; las procesa el worker de colas.
|
*/

Schedule::call(fn (DeisActualizacionService $actualizacion) => $actualizacion->encolar())
    ->name('deis-actualizacion-semanal')
    ->description('Actualización semanal de los datasets DEIS')
    ->weeklyOn(3, '03:00')
    ->timezone('America/Santiago');

Schedule::call(fn (DeisActualizacionService $actualizacion) => $actualizacion->cargarFaltantes())
    ->name('deis-carga-inicial')
    ->description('Carga inicial de los datasets DEIS sin datos')
    ->hourly();
