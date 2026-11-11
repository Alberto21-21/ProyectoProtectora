<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    public function store(Request $request)
{
    $datos = $request->validate([
        'user_id' => [
            'required',
            'integer',
            Rule::exists('users', 'id')->where('rol', 'personal'),
            Rule::unique('empleados', 'user_id'),
        ],
        'telefono' => ['nullable', 'string', 'max:30'],
        'puesto' => ['required', 'string', 'max:100'],
    ]);

    $empleado = Empleado::create($datos);

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral creada correctamente.',
        'data' => $empleado->load('user:id,name,email,rol'),
    ], 201);
}




public function mostrarPorUsuario(string $userId)
{
    $empleado = Empleado::with('user:id,name,email,rol')
        ->where('user_id', $userId)
        ->first();

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene ficha laboral.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral consultada correctamente.',
        'data' => $empleado,
    ], 200);
}



public function actualizarPorUsuario(Request $request, string $userId)
{
    $empleado = Empleado::where('user_id', $userId)->first();

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene ficha laboral.',
        ], 404);
    }

    $datos = $request->validate([
        'telefono' => ['sometimes', 'nullable', 'string', 'max:30'],
        'puesto' => ['sometimes', 'required', 'string', 'max:100'],
        'estado_laboral' => ['sometimes', 'required', 'in:activo,inactivo'],
    ]);

    $empleado->update($datos);

    return response()->json([
        'success' => true,
        'message' => 'Ficha laboral actualizada correctamente.',
        'data' => $empleado->load('user:id,name,email,rol'),
    ], 200);
}
}
