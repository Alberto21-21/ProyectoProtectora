<?php

namespace App\Http\Controllers;

use App\Models\AnimalAsignacion;
use App\Models\Empleado;
use Illuminate\Http\Request;

// T33: asignación de VETERINARIOS a los animales.
// Los responsables (cuidadores) se asignan con T17 (animales.cuidador_id).
// Crear y retirar asignaciones solo lo puede hacer el administrador
// (las rutas exigen la ability asignaciones:gestionar).
class AnimalAsignacionController extends Controller
{

public function veterinariosDisponibles(Request $request)
{
    // BUSCAR EMPLEADOS CON PERFIL VETERINARIO Y CUENTA ACTIVA
    $veterinarios = Empleado::with('user:id,name,email,rol')
        ->where('estado_laboral', 'activo')
        ->whereHas('veterinario')
        ->whereHas('user', function ($consulta) {
            $consulta->where('rol', 'veterinario')
                ->where('estado', 'activo');
        })
        ->orderBy('id')
        ->get(['id', 'user_id', 'puesto']);

    return response()->json([
        'success' => true,
        'message' => 'Veterinarios disponibles consultados correctamente.',
        'data' => $veterinarios
    ], 200);
}

public function store(Request $request)
{
    $usuario = $request->user();

    // VALIDAR LOS DATOS Y EL ESTADO DE LOS REGISTROS
    $datos = $request->validate([
        'animal_id' => 'required|integer|exists:animales,id,activo,1',
        'empleado_id' => 'required|integer|exists:empleados,id,estado_laboral,activo'
    ], [
        'animal_id.required' => 'Debes seleccionar un animal.',
        'animal_id.integer' => 'El identificador del animal no es válido.',
        'animal_id.exists' => 'El animal no existe o está dado de baja.',
        'empleado_id.required' => 'Debes seleccionar un veterinario.',
        'empleado_id.integer' => 'El identificador del empleado no es válido.',
        'empleado_id.exists' => 'El empleado no existe o su ficha laboral está inactiva.'
    ]);

    $empleado = Empleado::findOrFail($datos['empleado_id']);

    // CONSULTAR LA CUENTA MEDIANTE LA RELACIÓN
    $cuenta = $empleado->user;

    if (!$cuenta || $cuenta->estado !== 'activo') {
        return response()->json([
            'success' => false,
            'message' => 'El veterinario debe tener una cuenta activa.'
        ], 422);
    }

    // SOLO SE ASIGNAN CUENTAS VETERINARIAS CON PERFIL VETERINARIO
    if ($cuenta->rol !== 'veterinario' || !$empleado->veterinario) {
        return response()->json([
            'success' => false,
            'message' => 'Debes seleccionar una cuenta con perfil veterinario.'
        ], 422);
    }

    // EVITAR ASIGNACIONES DUPLICADAS MEDIANTE LA RELACIÓN
    $yaAsignado = $empleado->asignaciones()
        ->where('animal_id', $datos['animal_id'])
        ->where('tipo_asignacion', 'veterinario')
        ->exists();

    if ($yaAsignado) {
        return response()->json([
            'success' => false,
            'message' => 'Esta asignación ya existe.'
        ], 422);
    }

    // GUARDAR LA ASIGNACIÓN DEL VETERINARIO
    $asignacion = new AnimalAsignacion();
    $asignacion->animal_id = $datos['animal_id'];
    $asignacion->tipo_asignacion = 'veterinario';
    $asignacion->asignado_por_user_id = $usuario->id;

    $empleado->asignaciones()->save($asignacion);

    return response()->json([
        'success' => true,
        'message' => 'Asignación creada correctamente.',
        'data' => $asignacion->load([
            'animal:id,nombre,especie',
            'empleado.user:id,name,email,rol',
            'asignadoPor:id,name,email'
        ])
    ], 201);
}

public function index(Request $request)
{
    $usuario = $request->user();

    $datos = $request->validate([
        'animal_id' => 'sometimes|required|integer|exists:animales,id'
    ], [
        'animal_id.required' => 'Debes indicar el animal.',
        'animal_id.integer' => 'El identificador del animal no es válido.',
        'animal_id.exists' => 'El animal no existe.'
    ]);

    $consulta = AnimalAsignacion::query()
        ->where('tipo_asignacion', 'veterinario');

    // EL ADMINISTRADOR PUEDE CONSULTAR TODAS LAS ASIGNACIONES
    if (!$usuario->tokenCan('asignaciones:administrar')) {
        $empleado = $usuario->empleado;

        if (!$empleado || $empleado->estado_laboral !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'Necesitas una ficha laboral activa.'
            ], 403);
        }

        if ($usuario->rol === 'veterinario') {
            // EL VETERINARIO SOLO CONSULTA SUS ASIGNACIONES
            $consulta = $empleado->asignaciones()
                ->where('tipo_asignacion', 'veterinario');
        } elseif (!isset($datos['animal_id'])) {
            // EL PERSONAL SOLO CONSULTA LOS VETERINARIOS DE UN ANIMAL
            return response()->json([
                'success' => false,
                'message' => 'Debes indicar el animal.'
            ], 422);
        }
    }

    // APLICAR EL FILTRO VALIDADO
    if (isset($datos['animal_id'])) {
        $consulta->where('animal_id', $datos['animal_id']);
    }

    $asignaciones = $consulta->with([
        'animal:id,nombre,especie,estado',
        'empleado:id,user_id,puesto,estado_laboral',
        'empleado.user:id,name,email,rol',
        'asignadoPor:id,name,email'
    ])->orderByDesc('created_at')->get();

    return response()->json([
        'success' => true,
        'message' => 'Asignaciones consultadas correctamente.',
        'data' => $asignaciones
    ], 200);
}

public function retirarasignaciones(Request $request, int $id)
{
    $asignacion = AnimalAsignacion::find($id);

    if (!$asignacion) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró esa asignación.'
        ], 404);
    }

    $asignacion->delete();

    return response()->json([
        'success' => true,
        'message' => 'Asignación retirada correctamente.'
    ], 200);
}

}

