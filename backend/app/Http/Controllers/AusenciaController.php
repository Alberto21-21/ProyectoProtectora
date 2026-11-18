<?php

namespace App\Http\Controllers;

use App\Models\Ausencia;
use Illuminate\Http\Request;
use App\Models\Empleado;

class AusenciaController extends Controller
{
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'empleado_id' => 'sometimes|nullable|integer|exists:empleados,id'
        ], [
            'fecha_inicio.required' => 'Debes indicar la fecha de inicio.',
            'fecha_inicio.date' => 'La fecha de inicio no es válida.',
            'fecha_fin.required' => 'Debes indicar la fecha de fin.',
            'fecha_fin.date' => 'La fecha de fin no es válida.',
            'fecha_fin.after' => 'La fecha de fin debe ser posterior al inicio.',
            'empleado_id.integer' => 'El identificador del empleado no es válido.',
            'empleado_id.exists' => 'El empleado seleccionado no existe.'
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
        'empleado_id' => 'required|integer|exists:empleados,id',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after:fecha_inicio',
        'motivo' => 'nullable|string|max:255'
    ], [
        'empleado_id.required' => 'Debes seleccionar un empleado.',
        'empleado_id.integer' => 'El identificador del empleado no es válido.',
        'empleado_id.exists' => 'El empleado seleccionado no existe.',
        'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        'fecha_inicio.date' => 'La fecha de inicio no es válida.',
        'fecha_fin.required' => 'La fecha de fin es obligatoria.',
        'fecha_fin.date' => 'La fecha de fin no es válida.',
        'fecha_fin.after' => 'La fecha de fin debe ser posterior al inicio.',
        'motivo.string' => 'El motivo debe ser un texto.',
        'motivo.max' => 'El motivo no puede superar los 255 caracteres.'
    ]);

    $empleado = Empleado::findOrFail($datos['empleado_id']);

    $ausencia = new Ausencia();
    $ausencia->fecha_inicio = $datos['fecha_inicio'];
    $ausencia->fecha_fin = $datos['fecha_fin'];
    $ausencia->motivo = $datos['motivo'] ?? null;

    $empleado->ausencias()->save($ausencia);

    return response()->json([
        'success' => true,
        'message' => 'Ausencia registrada correctamente.',
        'data' => $ausencia->load('empleado.user:id,name,email,rol,estado')
    ], 201);
}

}
