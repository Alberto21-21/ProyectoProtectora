<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Veterinario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class VeterinarioController extends Controller
{

public function store(Request $request)
{
    $datos = $request->validate([
        'user_id' => [
            'required',
            'integer',
            Rule::exists('users', 'id')
                ->where('rol', 'veterinario')
                ->where('estado', 'activo')
        ],
        'telefono' => 'nullable|string|max:30',
        'especialidad' => 'required|string|max:100'
    ], [
        'user_id.required' => 'Debes seleccionar una cuenta.',
        'user_id.integer' => 'El identificador de la cuenta no es válido.',
        'user_id.exists' => 'Selecciona una cuenta activa de veterinario.',
        'telefono.string' => 'El teléfono debe ser un texto.',
        'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
        'especialidad.required' => 'La especialidad es obligatoria.',
        'especialidad.string' => 'La especialidad debe ser un texto.',
        'especialidad.max' => 'La especialidad no puede superar los 100 caracteres.'
    ]);

    $usuario = User::findOrFail($datos['user_id']);
    $empleado = $usuario->empleado;

    // COMPROBAR EL PERFIL MEDIANTE LA RELACIÓN
    if ($empleado && $empleado->veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta ya tiene un perfil veterinario.'
        ], 422);
    }

    // GUARDAR LA FICHA LABORAL Y EL PERFIL EN UNA TRANSACCIÓN
    $veterinario = DB::transaction(function () use ($usuario, $datos) {
        $empleado = $usuario->empleado;

        if (!$empleado) {
            $empleado = new Empleado();
            $empleado->puesto = 'Veterinario';
            $empleado->estado_laboral = 'activo';

            $usuario->empleado()->save($empleado);
        }

        if (array_key_exists('telefono', $datos)) {
            $empleado->telefono = $datos['telefono'];
            $empleado->save();
        }

        $veterinario = new Veterinario();
        $veterinario->especialidad = $datos['especialidad'];

        $empleado->veterinario()->save($veterinario);

        return $veterinario;
    });

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario creado correctamente.',
        'data' => $veterinario->load('empleado.user:id,name,email,rol,estado')
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

    $empleado = $usuario->empleado;

    if (!$empleado || $usuario->rol !== 'veterinario') {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta no tiene un perfil veterinario disponible.'
        ], 404);
    }

    $veterinario = $empleado->veterinario;

    if (!$veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene perfil veterinario.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario consultado correctamente.',
        'data' => $veterinario->load('empleado.user:id,name,email,rol,estado')
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

    if (!$empleado || $usuario->rol !== 'veterinario') {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta no tiene un perfil veterinario disponible.'
        ], 404);
    }

    $veterinario = $empleado->veterinario;

    if (!$veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene perfil veterinario.'
        ], 404);
    }

    $datos = $request->validate([
        'especialidad' => 'sometimes|required|string|max:100',
        'telefono' => 'sometimes|nullable|string|max:30'
    ], [
        'especialidad.required' => 'La especialidad no puede estar vacía.',
        'especialidad.string' => 'La especialidad debe ser un texto.',
        'especialidad.max' => 'La especialidad no puede superar los 100 caracteres.',
        'telefono.string' => 'El teléfono debe ser un texto.',
        'telefono.max' => 'El teléfono no puede superar los 30 caracteres.'
    ]);

    if (empty($datos)) {
        return response()->json([
            'success' => false,
            'message' => 'Debes enviar algún dato para modificar.'
        ], 422);
    }

    // GUARDAR AMBOS CAMBIOS EN UNA TRANSACCIÓN
    DB::transaction(function () use ($datos, $empleado, $veterinario) {
        if (array_key_exists('especialidad', $datos)) {
            $veterinario->especialidad = $datos['especialidad'];
            $veterinario->save();
        }

        if (array_key_exists('telefono', $datos)) {
            $empleado->telefono = $datos['telefono'];
            $empleado->save();
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario actualizado correctamente.',
        'data' => $veterinario->load('empleado.user:id,name,email,rol,estado')
    ], 200);
}

}
