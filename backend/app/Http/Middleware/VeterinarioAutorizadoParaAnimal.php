<?php

namespace App\Http\Middleware;

use App\Models\AnimalAsignacion;
use App\Models\Empleado;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VeterinarioAutorizadoParaAnimal
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión.',
            ], 401);
        }

        if ($usuario->rol === 'admin') {
            return $next($request);
        }

        if ($usuario->rol !== 'veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para consultar esta ficha clínica.',
            ], 403);
        }

        $empleadoId = Empleado::where('user_id', $usuario->id)
            ->where('estado_laboral', 'activo')
            ->value('id');

        if (!$empleadoId) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta no tiene una ficha laboral activa.',
            ], 403);
        }

        $animalId = $request->route('id');

        $estaAutorizado = AnimalAsignacion::where('animal_id', $animalId)
            ->where('empleado_id', $empleadoId)
            ->where('tipo_asignacion', 'veterinario')
            ->exists();

        if (!$estaAutorizado) {
            return response()->json([
                'success' => false,
                'message' => 'No estás autorizado para consultar la ficha clínica de este animal.',
            ], 403);
        }

        return $next($request);
    }
}
