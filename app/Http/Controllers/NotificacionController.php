<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Notificaciones persistentes del usuario autenticado (campana del top-bar).
 */
class NotificacionController extends Controller
{
    /**
     * Marca como leídas todas las notificaciones no leídas del usuario.
     */
    public function leerTodas(Request $request): JsonResponse
    {
        $request->user()->notificaciones()->noLeidas()->update(['leida_en' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
