<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TienePermiso
{
    // COMPROBAR EL PERMISO DE LA CUENTA Y DEL TOKEN
    public function handle(
        Request $request,
        Closure $next,
        string $permiso
    ): Response {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión.'
            ], 401);
        }

        $permisosActuales = $usuario->obtenerPermisos();

        if (
            !in_array($permiso, $permisosActuales, true) ||
            !$usuario->tokenCan($permiso)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta operación.'
            ], 403);
        }

        return $next($request);
    }
}