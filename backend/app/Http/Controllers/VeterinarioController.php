<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Veterinario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class VeterinarioController extends Controller
{
   public function store(Request $request)
{
    $datos = $request->validate([
        'user_id' => [
            'required',
            'integer',
            Rule::exists('users', 'id')->where('rol', 'veterinario'),
        ],
        'telefono' => ['nullable', 'string', 'max:30'],
        'especialidad' => ['required', 'string', 'max:100'],
    ]);

    $empleadoExistente = Empleado::where('user_id', $datos['user_id'])->first();

    if (
        $empleadoExistente
        && Veterinario::where('empleado_id', $empleadoExistente->id)->exists()
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta ya tiene un perfil veterinario.',
        ], 422);
    }

    $veterinario = DB::transaction(function () use ($datos) {
    $empleado = Empleado::firstOrCreate(
        ['user_id' => $datos['user_id']],
        [
            'telefono' => $datos['telefono'] ?? null,
            'puesto' => 'Veterinario',
        ]
    );

   
    if (array_key_exists('telefono', $datos)) {
        $empleado->update([
            'telefono' => $datos['telefono'],
        ]);
    }

    return Veterinario::create([
        'empleado_id' => $empleado->id,
        'especialidad' => $datos['especialidad'],
    ]);
});

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario creado correctamente.',
        'data' => $veterinario->load('empleado.user:id,name,email,rol,estado'),
    ], 201);
}

public function mostrarPorUsuario(string $userId)
{
    $veterinario = Veterinario::with('empleado.user:id,name,email,rol,estado')
        ->whereHas('empleado', function ($consulta) use ($userId) {
            $consulta->where('user_id', $userId);
        })
        ->whereHas('empleado.user', function ($consulta) {
            $consulta->where('rol', 'veterinario');
        })
        ->first();

    if (!$veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene perfil veterinario.',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario consultado correctamente.',
        'data' => $veterinario,
    ], 200);
}


public function actualizarPorUsuario(Request $request, string $userId)
{
    $veterinario = Veterinario::whereHas('empleado', function ($consulta) use ($userId) {
        $consulta->where('user_id', $userId);
    })
        ->whereHas('empleado.user', function ($consulta) {
            $consulta->where('rol', 'veterinario');
        })
        ->first();

    if (!$veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Esta cuenta todavía no tiene perfil veterinario.',
        ], 404);
    }

    $datos = $request->validate([
    'especialidad' => ['sometimes', 'required', 'string', 'max:100'],
    'telefono' => ['sometimes', 'nullable', 'string', 'max:30'],
]);

   if (array_key_exists('especialidad', $datos)) {
    $veterinario->update([
        'especialidad' => $datos['especialidad'],
    ]);
}

if (array_key_exists('telefono', $datos)) {
    $veterinario->empleado->update([
        'telefono' => $datos['telefono'],
    ]);
}

    return response()->json([
        'success' => true,
        'message' => 'Perfil veterinario actualizado correctamente.',
        'data' => $veterinario->load('empleado.user:id,name,email,rol,estado'),
    ], 200);
}
}
