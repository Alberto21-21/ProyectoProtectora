<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VeterinarioAutorizadoParaAnimal
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        // PERMISO PARA ACCEDER A TODAS LAS FICHAS CLÍNICAS
        if ($usuario->tokenCan('clinica:consultar-todas')) {
            return $next($request);
        }

        // OBTENER LA FICHA LABORAL MEDIANTE LA RELACIÓN
        $empleado = $usuario->empleado;

        if (!$empleado || $empleado->estado_laboral !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Necesitas una ficha laboral activa.'
            ], 403);
        }

        // COMPROBAR LA ASIGNACIÓN AL ANIMAL
        $estaAsignado = $empleado->asignaciones()
            ->where('animal_id', $request->route('id'))
            ->where('tipo_asignacion', 'veterinario')
            ->exists();

        if (!$estaAsignado) {
            return response()->json([
                'success' => false,
                'message' => 'No estás asignado a este animal.'
            ], 403);
        }

        return $next($request);
    }
}