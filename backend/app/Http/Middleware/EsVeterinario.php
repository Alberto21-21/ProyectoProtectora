<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsVeterinario
{
    // PERMITIR EL ACCESO A LOS DATOS CLÍNICOS SOLO A VETERINARIOS Y ADMINISTRADORES
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión.'
            ], 401);
        }

        if ($usuario->rol !== 'veterinario' && $usuario->rol !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para acceder a la información clínica.'
            ], 403);
        }

        return $next($request);
    }
}