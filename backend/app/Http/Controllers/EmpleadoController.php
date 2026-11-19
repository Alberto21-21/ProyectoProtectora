<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;

class EmpleadoController extends Controller
{
   
public function store(Request $request)
{
    $datos = $request->validate([
        'user_id' => [
            'required',
            'integer',
            Rule::exists('users', 'id')
                ->whereIn('rol', ['personal', 'veterinario'])
                ->where('estado', 'activo'),
            Rule::unique('empleados', 'user_id')
        ],
        'telefono' => 'nullable|string|max:30',
        'puesto' => 'required|string|max:100'
    ], [
        'user_id.required' => 'Debes seleccionar una cuenta.',
        'user_id.integer' => 'El identificador de la cuenta no es válido.',
        'user_id.exists' => 'Selecciona una cuenta activa de personal o veterinario.',
        'user_id.unique' => 'Esta cuenta ya tiene una ficha laboral.',
        'telefono.string' => 'El teléfono debe ser un texto.',
        'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
        'puesto.required' => 'El puesto es obligatorio.',
        'puesto.string' => 'El puesto debe ser un texto.',
        'puesto.max' => 'El puesto no puede superar los 100 caracteres.'
    ]);

    $empleado = Empleado::create($datos);

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral creada correctamente.',
        'data' => $empleado->load('user:id,name,email,rol')
    ], 201);
}

public function mostrarPorUsuario(string $userId)
{
    $usuario = User::find($userId);

    if (!$usuario) {
        return response()->json([
            'success' => false,
            'message' => 'La cuenta no existe.'
        ], 404);
    }

    // OBTENER LA FICHA MEDIANTE LA RELACIÓN
    $empleado = $usuario->empleado;

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene ficha laboral.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral consultada correctamente.',
        'data' => $empleado->load('user:id,name,email,rol')
    ], 200);
}

public function actualizarPorUsuario(Request $request, string $userId)
{
    $usuario = User::find($userId);

    if (!$usuario) {
        return response()->json([
            'success' => false,
            'message' => 'La cuenta no existe.'
        ], 404);
    }

    $empleado = $usuario->empleado;

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene ficha laboral.'
        ], 404);
    }

    $datos = $request->validate([
        'telefono' => 'sometimes|nullable|string|max:30',
        'puesto' => 'sometimes|required|string|max:100',
        'estado_laboral' => 'sometimes|required|in:activo,inactivo'
    ], [
        'telefono.string' => 'El teléfono debe ser un texto.',
        'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
        'puesto.required' => 'El puesto no puede estar vacío.',
        'puesto.string' => 'El puesto debe ser un texto.',
        'puesto.max' => 'El puesto no puede superar los 100 caracteres.',
        'estado_laboral.required' => 'Debes indicar el estado laboral.',
        'estado_laboral.in' => 'El estado laboral debe ser activo o inactivo.'
    ]);

    if (empty($datos)) {
        return response()->json([
            'success' => false,
            'message' => 'Debes enviar algún dato de la ficha para modificar.'
        ], 422);
    }

    $empleado->update($datos);

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral actualizada correctamente.',
        'data' => $empleado->load('user:id,name,email,rol')
    ], 200);
}
}
