<?php

namespace App\Http\Controllers;

use App\Models\Ausencia;
use Illuminate\Http\Request;

class AusenciaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'empleado_id' => ['sometimes', 'nullable', 'integer', 'exists:empleados,id'],
        ]);

        $consulta = Ausencia::with('empleado.user:id,name,email,rol,estado')
            ->where('fecha_inicio', '<', $filtros['fecha_fin'])
            ->where('fecha_fin', '>', $filtros['fecha_inicio']);

        if (!empty($filtros['empleado_id'])) {
            $consulta->where('empleado_id', $filtros['empleado_id']);
        }

        $ausencias = $consulta
            ->orderBy('fecha_inicio')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Ausencias consultadas correctamente.',
            'data' => $ausencias,
        ], 200);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $ausencia = Ausencia::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Ausencia registrada correctamente.',
            'data' => $ausencia->load('empleado.user:id,name,email,rol,estado'),
        ], 201);
    }
}
