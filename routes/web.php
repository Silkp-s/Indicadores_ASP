<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\IndicadorController;
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
