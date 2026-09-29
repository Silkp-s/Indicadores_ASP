<?php

use App\Http\Controllers\IndicadorController;
use Illuminate\Support\Facades\Route;

##Route::get('/', function () {
#    return view('welcome');
#});

/*
|--------------------------------------------------------------------------
| Rutas placeholder para las vistas maquetadas
|--------------------------------------------------------------------------
| Solo para poder navegar el maquetado (route() y el sidebar las necesitan).
| El login real, el middleware de acceso y los controladores/CRUD de cada
| módulo se conectan aparte; estas quedan reemplazadas cuando eso esté listo.
*/

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/', function () {
    return redirect()->route('centro-mando');
});

Route::post('/logout', function () {
    return redirect()->route('login');
})->name('logout');

Route::get('/password/request', function () {
    return view('auth.login'); // TODO: vista de recuperación de contraseña
})->name('password.request');

Route::middleware([/* auth, control de acceso */])->group(function () {

    Route::get('/centro-mando', function () {
        return view('centro-mando.index');
    })->name('centro-mando');

    // Módulo 1 · Catálogo de Indicadores
    Route::get('/indicadores', [IndicadorController::class, 'index'])->name('indicadores.index');
    Route::get('/indicadores/exportar', [IndicadorController::class, 'exportar'])->name('indicadores.exportar');
    Route::post('/indicadores', [IndicadorController::class, 'store'])->name('indicadores.store');
    Route::put('/indicadores/{indicador}', [IndicadorController::class, 'update'])->name('indicadores.update')->whereNumber('indicador');
    Route::patch('/indicadores/{indicador}/estado', [IndicadorController::class, 'cambiarEstado'])->name('indicadores.estado')->whereNumber('indicador');

    Route::get('/iaaps-metas', function () {
        return view('iaaps-metas.index');
    })->name('iaaps-metas.index');

    Route::post('/iaaps-metas', function () {
        return redirect()->route('iaaps-metas.index');
    })->name('iaaps-metas.store');

    Route::get('/compromisos', function () {
        return view('en-construccion', ['titulo' => 'Compromisos']); // TODO: Módulo · Compromisos de Gestión
    })->name('compromisos.index');

    Route::get('/inteligencia-deis', function () {
        return view('en-construccion', ['titulo' => 'Inteligencia DEIS']); // TODO: Módulo · Inteligencia DEIS
    })->name('inteligencia-deis.index');

    Route::get('/alertas', function () {
        return view('en-construccion', ['titulo' => 'Alertas']); // TODO: Módulo · Alertas
    })->name('alertas.index');

    Route::get('/configuracion', function () {
        return view('welcome'); // TODO: vista de Configuración
    })->name('configuracion');
});
