<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsPersonalAutorizado
{
    // PERMITIR EL ACCESO SOLO A ADMINISTRADORES, PERSONAL Y VETERINARIOS
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión.'
            ], 401);
        }

        if ($usuario->rol !== 'admin' && $usuario->rol !== 'personal' && $usuario->rol !== 'veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar animales.'
            ], 403);
        }

        return $next($request);
    }
}