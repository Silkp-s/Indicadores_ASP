<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordRequest;
use App\Http\Requests\PerfilRequest;
use App\Models\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Configuración: datos personales y contraseña (todos) y parámetros del sistema (admin).
 */
class ConfiguracionController extends Controller
{
    public function index(): View
    {
        return view('configuracion.index', [
            'umbralCritico' => Configuracion::umbralCritico(),
            'anoTrabajo' => Configuracion::anoTrabajo(),
        ]);
    }

    /**
     * Actualiza los datos personales del usuario autenticado.
     */
    public function updatePerfil(PerfilRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return back()->with('status', 'Perfil actualizado correctamente.');
    }

    /**
     * Cambia la contraseña del usuario autenticado (valida la contraseña actual).
     */
    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'Contraseña actualizada correctamente.');
    }

    /**
     * Guarda los parámetros del sistema (solo administradores).
     */
    public function updateSistema(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'umbral_critico' => ['required', 'numeric', 'min:50', 'max:100'],
            'ano_trabajo' => ['required', 'integer', 'min:2000', 'max:2100'],
        ], [
            'umbral_critico.required' => 'Indique el umbral crítico.',
            'umbral_critico.numeric' => 'El umbral crítico debe ser un número.',
            'umbral_critico.min' => 'El umbral crítico no puede ser menor a 50 %.',
            'umbral_critico.max' => 'El umbral crítico no puede superar el 100 %.',
            'ano_trabajo.required' => 'Indique el año de trabajo.',
            'ano_trabajo.integer' => 'El año de trabajo debe ser un número entero.',
            'ano_trabajo.min' => 'El año de trabajo no es válido.',
            'ano_trabajo.max' => 'El año de trabajo no es válido.',
        ]);

        Configuracion::guardar('umbral_critico', $datos['umbral_critico']);
        Configuracion::guardar('ano_trabajo', $datos['ano_trabajo']);

        return back()->with('status', 'Parámetros del sistema actualizados correctamente.');
    }
}
