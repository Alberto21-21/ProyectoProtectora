<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdministrador
{
    // PERMITIR EL ACCESO SOLO A ADMINISTRADORES
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión.'
            ], 401);
        }

        if ($usuario->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder.'
            ], 403);
        }

        return $next($request);
    }
}