<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Rutas placeholder para las vistas maquetadas
|--------------------------------------------------------------------------
| Solo para poder navegar el maquetado (route() y el sidebar las necesitan).
| El login real, el middleware de acceso y los controladores/CRUD de cada
| módulo se conectan aparte; estas quedan reemplazadas cuando eso esté listo.
*/

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
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

    Route::get('/indicadores', function () {
        return view('welcome'); // TODO: Módulo 1 · Catálogo de Indicadores APS
    })->name('indicadores.index');

    Route::get('/iaaps-metas', function () {
        return view('iaaps-metas.index');
    })->name('iaaps-metas.index');

    Route::post('/iaaps-metas', function () {
        return redirect()->route('iaaps-metas.index');
    })->name('iaaps-metas.store');

    Route::get('/configuracion', function () {
        return view('welcome'); // TODO: vista de Configuración
    })->name('configuracion');
});
