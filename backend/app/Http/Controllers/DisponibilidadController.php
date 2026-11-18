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
        'empleado_id' => 'required|integer|exists:empleados,id,estado_laboral,activo',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after:fecha_inicio'
    ], [
        'empleado_id.required' => 'Debes seleccionar un empleado.',
        'empleado_id.integer' => 'El identificador del empleado no es válido.',
        'empleado_id.exists' => 'El empleado no existe o su ficha laboral está inactiva.',
        'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
        'fecha_inicio.date' => 'La fecha de inicio no es válida.',
        'fecha_fin.required' => 'La fecha de fin es obligatoria.',
        'fecha_fin.date' => 'La fecha de fin no es válida.',
        'fecha_fin.after' => 'La fecha de fin debe ser posterior al inicio.'
    ]);

    $empleado = Empleado::findOrFail($datos['empleado_id']);
    $cuenta = $empleado->user;

    if (!$cuenta || $cuenta->estado !== 'activo') {
        return response()->json([
            'success' => false,
            'message' => 'El empleado debe tener una cuenta activa.'
        ], 422);
    }

    if (!in_array($cuenta->rol, ['personal', 'veterinario'])) {
        return response()->json([
            'success' => false,
            'message' => 'La cuenta debe ser de personal o veterinario.'
        ], 422);
    }

    // COMPROBAR LOS HORARIOS MEDIANTE LA RELACIÓN
    $haySolapamiento = $empleado->disponibilidades()
        ->where('fecha_inicio', '<', $datos['fecha_fin'])
        ->where('fecha_fin', '>', $datos['fecha_inicio'])
        ->exists();

    if ($haySolapamiento) {
        return response()->json([
            'success' => false,
            'message' => 'Ese empleado ya tiene una disponibilidad en ese horario.'
        ], 422);
    }

    $disponibilidad = new Disponibilidad();
    $disponibilidad->fecha_inicio = $datos['fecha_inicio'];
    $disponibilidad->fecha_fin = $datos['fecha_fin'];

    $empleado->disponibilidades()->save($disponibilidad);

    return response()->json([
        'success' => true,
        'message' => 'Disponibilidad registrada correctamente.',
        'data' => $disponibilidad->load('empleado.user:id,name,email,rol,estado')
    ], 201);
}

}
