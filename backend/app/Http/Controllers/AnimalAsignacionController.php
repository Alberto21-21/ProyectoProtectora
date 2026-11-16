<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalAsignacion;
use App\Models\Empleado;
use App\Models\Veterinario;
use Illuminate\Http\Request;

class AnimalAsignacionController extends Controller
{


public function veterinariosDisponibles(Request $request)
{
    $usuario = $request->user();

    if (!$usuario || !in_array($usuario->rol, ['admin', 'personal'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Solo el administrador o el personal pueden consultar veterinarios.',
        ], 403);
    }

    if ($usuario->rol === 'personal') {
        $tieneFichaActiva = Empleado::where('user_id', $usuario->id)
            ->where('estado_laboral', 'activo')
            ->exists();

        if (!$tieneFichaActiva) {
            return response()->json([
                'success' => false,
                'message' => 'Necesitas una ficha laboral activa para consultar veterinarios.',
            ], 403);
        }
    }

    $veterinarios = Empleado::query()
        ->with('user:id,name,email,rol')
        ->where('estado_laboral', 'activo')
        ->whereHas('user', function ($consulta) {
            $consulta->where('rol', 'veterinario')
                ->where('estado', 'activo');
        })
        ->whereIn('id', Veterinario::select('empleado_id'))
        ->orderBy('id')
        ->get(['id', 'user_id', 'puesto']);

    return response()->json([
        'success' => true,
        'message' => 'Veterinarios disponibles consultados correctamente.',
        'data' => $veterinarios,
    ], 200);
}


    public function store(Request $request)
    {
        $usuarioActual = $request->user();

        if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'personal'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo el administrador o un empleado pueden crear asignaciones.',
            ], 403);
        }

        if ($usuarioActual->rol === 'personal') {
        $tieneFichaActiva = Empleado::where('user_id', $usuarioActual->id)
        ->where('estado_laboral', 'activo')
        ->exists();

    if (!$tieneFichaActiva) {
        return response()->json([
            'success' => false,
            'message' => 'Necesitas una ficha laboral activa para crear asignaciones.',
        ], 403);
    }
}

        $datos = $request->validate([
            'animal_id' => ['required', 'integer', 'exists:animales,id'],
            'empleado_id' => ['required', 'integer', 'exists:empleados,id'],
            'tipo_asignacion' => ['required', 'in:responsable,veterinario'],
        ]);

        if ($usuarioActual->rol === 'personal' && $datos['tipo_asignacion'] !== 'veterinario') {
            return response()->json([
                'success' => false,
                'message' => 'Un empleado solo puede asignar veterinarios.',
            ], 403);
        }

        $animal = Animal::find($datos['animal_id']);

        if (!$animal || !$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede asignar personal a un animal dado de baja.',
            ], 422);
        }

        $empleado = Empleado::with('user')
            ->find($datos['empleado_id']);

        if (!$empleado || !$empleado->user || $empleado->estado_laboral !== 'activo' || $empleado->user->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'La ficha laboral y la cuenta deben estar activas.',
            ], 422);
        }

        if ($datos['tipo_asignacion'] === 'responsable' && $empleado->user->rol !== 'personal') {
            return response()->json([
                'success' => false,
                'message' => 'Solo una cuenta de personal puede ser responsable del animal.',
            ], 422);
        }

        if ($datos['tipo_asignacion'] === 'veterinario' && ( $empleado->user->rol !== 'veterinario' || !Veterinario::where('empleado_id', $empleado->id)->exists())
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una cuenta con perfil veterinario.',
            ], 422);
        }

        $asignacionDuplicada = AnimalAsignacion::where(
            'animal_id',
            $datos['animal_id']
        )
            ->where('empleado_id', $datos['empleado_id'])
            ->where('tipo_asignacion', $datos['tipo_asignacion'])
            ->exists();

        if ($asignacionDuplicada) {
            return response()->json([
                'success' => false,
                'message' => 'Esta asignación ya existe.',
            ], 422);
        }

        $datos['asignado_por_user_id'] = $usuarioActual->id;

        $asignacion = AnimalAsignacion::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Asignación creada correctamente.',
            'data' => $asignacion->load([
                'animal:id,nombre,especie',
                'empleado.user:id,name,email,rol',
                'asignadoPor:id,name,email',
            ]),
        ], 201);
    }





    public function index(Request $request)
{
    $usuarioActual = $request->user();

    if (!$usuarioActual || !in_array( 
         $usuarioActual->rol,
            ['admin', 'personal', 'veterinario'],
            true
        )
    ) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para consultar estas asignaciones.',
        ], 403);
    }

    $filtros = $request->validate([
        'animal_id' => ['sometimes', 'integer', 'exists:animales,id'],
    ]);

    $consulta = AnimalAsignacion::with([
        'animal:id,nombre,especie,estado',
        'empleado:id,user_id,puesto,estado_laboral',
        'empleado.user:id,name,email,rol',
        'asignadoPor:id,name,email',
    ]);

    if ($usuarioActual->rol === 'personal') {
    $empleadoId = Empleado::where('user_id', $usuarioActual->id)
        ->where('estado_laboral', 'activo')
        ->value('id');

    if (!$empleadoId) {
        return response()->json([
            'success' => false,
            'message' => 'Tu cuenta no tiene una ficha laboral activa.',
        ], 403);
    }

    if (!empty($filtros['animal_id'])) {
        $consulta->where('animal_id', $filtros['animal_id'])
            ->where('tipo_asignacion', 'veterinario');
    } else {
        $consulta->where('empleado_id', $empleadoId)
            ->where('tipo_asignacion', 'responsable');
    }
}

if ($usuarioActual->rol === 'veterinario') {
    $empleadoId = Empleado::where('user_id', $usuarioActual->id)
        ->where('estado_laboral', 'activo')
        ->value('id');

    if (!$empleadoId) {
        return response()->json([
            'success' => false,
            'message' => 'Tu cuenta no tiene una ficha laboral activa.',
        ], 403);
    }

    $consulta->where('empleado_id', $empleadoId)
        ->where('tipo_asignacion', 'veterinario');
}

    if (!empty($filtros['animal_id'])) {
        $consulta->where('animal_id', $filtros['animal_id']);
    }

    $asignaciones = $consulta
        ->orderByDesc('created_at')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Asignaciones consultadas correctamente.',
        'data' => $asignaciones,
    ], 200);
}


public function retirarasignaciones(Request $request, int $id)
{
    $usuarioActual = $request->user();

    if (!$usuarioActual || !in_array($usuarioActual->rol, ['admin', 'personal'], true)) {
        return response()->json([
            'success' => false,
            'message' => 'Solo el administrador o un empleado pueden retirar asignaciones.',
        ], 403);
    }

    if ($usuarioActual->rol === 'personal') {
        $tieneFichaActiva = Empleado::where('user_id', $usuarioActual->id)
            ->where('estado_laboral', 'activo')
            ->exists();

        if (!$tieneFichaActiva) {
            return response()->json([
                'success' => false,
                'message' => 'Necesitas una ficha laboral activa para retirar asignaciones.',
            ], 403);
        }
    }

    $asignacion = AnimalAsignacion::find($id);

    if (!$asignacion) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró esa asignación.',
        ], 404);
    }

    if (
        $usuarioActual->rol === 'personal' &&
        $asignacion->tipo_asignacion !== 'veterinario'
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Un empleado solo puede retirar asignaciones de veterinarios.',
        ], 403);
    }

    $asignacion->delete();

    return response()->json([
        'success' => true,
        'message' => 'Asignación retirada correctamente.',
    ], 200);
}

}
