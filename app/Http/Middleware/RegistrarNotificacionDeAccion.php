<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persiste una notificación para el usuario cuando una acción (POST/PUT/PATCH/DELETE)
 * redirige con un mensaje flash, de modo que la campana conserve el historial.
 */
class RegistrarNotificacionDeAccion
{
    /** @var array<string, string> clave de flash => tipo de notificación. */
    private const TIPOS = [
        'status' => 'success',
        'success' => 'success',
        'error' => 'error',
        'warning' => 'warning',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') || ! $response instanceof RedirectResponse || ! $request->user()) {
            return $response;
        }

        foreach (self::TIPOS as $clave => $tipo) {
            $mensaje = $request->session()->get($clave);

            if (filled($mensaje)) {
                $request->user()->notificaciones()->create([
                    'tipo' => $tipo,
                    'mensaje' => (string) $mensaje,
                ]);
            }
        }

        return $response;
    }
}
