<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CentroMandoController;
use App\Http\Controllers\EstablecimientoController;
use App\Http\Controllers\IaapsMetasController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\IndicadorController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/', function () {
        $credentials = request()->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

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
    })->middleware('throttle:6,1')->name('login.post');

    Route::get('/password/request', function () {
        return view('auth.login'); // TODO: vista de recuperación de contraseña
    })->name('password.request');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas protegidas - Usuario regular y Admin (Admin tiene acceso a todo)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:Admin|Usuario'])->group(function () {
    Route::get('/centro-mando', [CentroMandoController::class, 'index'])->name('centro-mando');

    // Módulo 1 · Catálogo de Indicadores
    Route::get('/indicadores', [IndicadorController::class, 'index'])->name('indicadores.index');
    Route::get('/indicadores/exportar', [IndicadorController::class, 'exportar'])->name('indicadores.exportar');
    Route::post('/indicadores', [IndicadorController::class, 'store'])->name('indicadores.store');
    Route::put('/indicadores/{indicador}', [IndicadorController::class, 'update'])->name('indicadores.update')->whereNumber('indicador');
    Route::patch('/indicadores/{indicador}/estado', [IndicadorController::class, 'cambiarEstado'])->name('indicadores.estado')->whereNumber('indicador');

    // Módulo 2 · IAAPS y Metas Sanitarias
    Route::get('/iaaps-metas', [IaapsMetasController::class, 'index'])->name('iaaps-metas.index');
    Route::post('/iaaps-metas', [IaapsMetasController::class, 'store'])->name('iaaps-metas.store');
    Route::put('/iaaps-metas/{indicador}', [IaapsMetasController::class, 'update'])->name('iaaps-metas.update')->whereNumber('indicador');
    Route::delete('/iaaps-metas/{indicador}', [IaapsMetasController::class, 'destroy'])->name('iaaps-metas.destroy')->whereNumber('indicador');

    // Módulo Establecimientos de Salud
    Route::get('/establecimientos', [EstablecimientoController::class, 'index'])->name('establecimientos.index');
    Route::post('/establecimientos', [EstablecimientoController::class, 'store'])->name('establecimientos.store');
    Route::put('/establecimientos/{establecimiento}', [EstablecimientoController::class, 'update'])->name('establecimientos.update')->whereNumber('establecimiento');
    Route::delete('/establecimientos/{establecimiento}', [EstablecimientoController::class, 'destroy'])->name('establecimientos.destroy')->whereNumber('establecimiento');
    Route::patch('/establecimientos/{establecimiento}/estado', [EstablecimientoController::class, 'cambiarEstado'])->name('establecimientos.estado')->whereNumber('establecimiento');

    // Módulo de Importación REM (Metas Sanitarias 2026)
    Route::get('/importacion', [ImportacionController::class, 'index'])->name('importacion');
    Route::post('/importacion/previsualizar', [ImportacionController::class, 'previsualizar'])->name('importacion.previsualizar');
    Route::post('/importacion/guardar', [ImportacionController::class, 'guardar'])->name('importacion.guardar');
    Route::post('/importacion/procesar', [ImportacionController::class, 'previsualizar'])->name('importacion.procesar');
    Route::post('/importacion/confirmar', [ImportacionController::class, 'guardar'])->name('importacion.confirmar');

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
        return view('en-construccion', ['titulo' => 'Configuración']); // TODO: vista de Configuración
    })->name('configuracion');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas - Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin_only'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');

    // Sección 1 · Usuarios
    Route::get('/users/create', [AdminController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');

    // Sección 2 · Metas Sanitarias e IAAPS
    Route::post('/indicadores', [AdminController::class, 'storeIndicador'])->name('indicadores.store');
    Route::put('/indicadores/{indicador}', [AdminController::class, 'updateIndicador'])->name('indicadores.update')->whereNumber('indicador');
    Route::delete('/indicadores/{indicador}', [AdminController::class, 'destroyIndicador'])->name('indicadores.destroy')->whereNumber('indicador');
    Route::patch('/indicadores/{indicador}/estado', [AdminController::class, 'cambiarEstadoIndicador'])->name('indicadores.estado')->whereNumber('indicador');

    // Sección 3 · Mapeo de Celdas REM
    Route::post('/rem-mappings', [AdminController::class, 'storeRemMapping'])->name('rem-mappings.store');
    Route::put('/rem-mappings/{mapping}', [AdminController::class, 'updateRemMapping'])->name('rem-mappings.update')->whereNumber('mapping');
    Route::delete('/rem-mappings/{mapping}', [AdminController::class, 'destroyRemMapping'])->name('rem-mappings.destroy')->whereNumber('mapping');
    Route::patch('/rem-mappings/{mapping}/estado', [AdminController::class, 'cambiarEstadoRemMapping'])->name('rem-mappings.estado')->whereNumber('mapping');
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
