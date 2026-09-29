<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('auth.login');
})->name('login');

Route::post('/', function () {
    $credentials = request()->only('email', 'password');

    if (Auth::attempt($credentials, request()->boolean('remember'))) {
        request()->session()->regenerate();

        $user = Auth::user();

        if ($user->hasRole('Admin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('centro-mando'));
    }

    return back()->withErrors([
        'email' => 'Las credenciales no son correctas.',
    ])->onlyInput('email');
})->name('login.post');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::get('/password/request', function () {
    return view('auth.login'); // TODO: vista de recuperación de contraseña
})->name('password.request');

/*
|--------------------------------------------------------------------------
| Rutas protegidas - Usuario regular y Admin (Admin tiene acceso a todo)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Admin|Usuario'])->group(function () {
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

/*
|--------------------------------------------------------------------------
| Rutas protegidas - Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Fallback: redirigir según rol si accede a ruta no autorizada
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->get('/home', function () {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('centro-mando');
})->name('home');
