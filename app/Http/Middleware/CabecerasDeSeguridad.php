<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad en todas las respuestas: impiden incrustar el sistema en sitios ajenos
 * (clickjacking), que el navegador adivine tipos de archivo y que se filtre la URL a otros sitios.
 */
class CabecerasDeSeguridad
{
    /** @var array<string, string> */
    private const CABECERAS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::CABECERAS as $nombre => $valor) {
            $response->headers->set($nombre, $valor, false);
        }

        // HSTS solo tiene efecto sobre HTTPS; en desarrollo (http://localhost) no se envía.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000', false);
        }

        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
