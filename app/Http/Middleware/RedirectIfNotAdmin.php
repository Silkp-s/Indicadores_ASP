<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->hasRole('Admin')) {
            if ($request->expectsJson()) {
                abort(403, 'Acceso denegado: Se requieren permisos de Administrador.');
            }

            return redirect()->route('centro-mando')
                ->with('error', 'Acceso denegado');
        }

        return $next($request);
    }
}
