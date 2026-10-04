<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    return redirect()->route('iaaps-metas.index');
});

Route::post('/logout', function () {
    return redirect()->route('login');
})->name('logout');

Route::get('/password/request', function () {
    return view('auth.login');
})->name('password.request');

Route::middleware([])->group(function () {

    Route::get('/indicadores', function () {
        return view('welcome');
    })->name('indicadores.index');

    Route::get('/iaaps-metas', function () {
        return view('iaaps-metas.index');
    })->name('iaaps-metas.index');

    Route::post('/iaaps-metas', function () {
        return redirect()->route('iaaps-metas.index');
    })->name('iaaps-metas.store');

    Route::get('/configuracion', function () {
        return view('welcome');
    })->name('configuracion');
});
