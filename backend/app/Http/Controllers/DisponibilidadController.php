<?php

namespace App\Http\Controllers;

use App\Models\Disponibilidad;
use Illuminate\Http\Request;
use App\Models\Empleado;

class DisponibilidadController extends Controller
{







public function empleados()
{
    $empleados = Empleado::with('user:id,name,email,rol,estado')
        ->where('estado_laboral', 'activo')
        ->whereHas('user', function ($consulta) {
            $consulta->where('estado', 'activo')
                ->whereIn('rol', ['personal', 'veterinario']);
        })
        ->orderBy('puesto')
        ->orderBy('id')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Empleados activos consultados correctamente.',
        'data' => $empleados,
    ], 200);
}
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'empleado_id' => ['sometimes', 'nullable', 'integer', 'exists:empleados,id'],
        ]);

        $consulta = Disponibilidad::with('empleado.user:id,name,email,rol,estado')
            ->where('fecha_inicio', '<', $filtros['fecha_fin'])
            ->where('fecha_fin', '>', $filtros['fecha_inicio']);

        if (!empty($filtros['empleado_id'])) {
            $consulta->where('empleado_id', $filtros['empleado_id']);
        }

        $disponibilidades = $consulta
            ->orderBy('fecha_inicio')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidades consultadas correctamente.',
            'data' => $disponibilidades,
        ], 200);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
        ]);

        $haySolapamiento = Disponibilidad::where('empleado_id', $datos['empleado_id'])
            ->where('fecha_inicio', '<', $datos['fecha_fin'])
            ->where('fecha_fin', '>', $datos['fecha_inicio'])
            ->exists();

        if ($haySolapamiento) {
            return response()->json([
                'success' => false,
                'message' => 'Ese empleado ya tiene una disponibilidad en ese horario.',
            ], 422);
        }

        $disponibilidad = Disponibilidad::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Disponibilidad registrada correctamente.',
            'data' => $disponibilidad->load('empleado.user:id,name,email,rol,estado'),
        ], 201);
    }
}
