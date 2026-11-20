<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

// T32: la jornada semanal de los empleados de tipo personal.
// La disponibilidad por fechas se ha retirado: la jornada se consulta y se edita
// solo desde los datos del empleado (usuarios.html).
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

public function jornadaEmpleado(string $userId)
{
    $empleado = Empleado::where('user_id', $userId)
        ->whereHas('user', function ($consulta) {
            $consulta->where('rol', 'personal');
        })
        ->first();

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró una ficha de personal para esa cuenta.',
        ], 404);
    }

    $jornada = $empleado->disponibilidades()
        ->whereNotNull('dia_semana')
        ->orderBy('dia_semana')
        ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fin']);

    return response()->json([
        'success' => true,
        'message' => 'Jornada consultada correctamente.',
        'data' => $jornada,
    ], 200);
}

public function actualizarJornadaEmpleado(Request $request, string $userId)
{
    $empleado = Empleado::where('user_id', $userId)
        ->whereHas('user', function ($consulta) {
            $consulta->where('rol', 'personal');
        })
        ->first();

    if (!$empleado) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró una ficha de personal para esa cuenta.',
        ], 404);
    }

    $datos = $request->validate([
        'jornada' => ['required', 'array', 'min:1'],
        'jornada.*.dia_semana' => [
            'required',
            'integer',
            'between:1,7',
            'distinct',
        ],
        'jornada.*.hora_inicio' => [
            'required',
            'date_format:H:i',
        ],
        'jornada.*.hora_fin' => [
            'required',
            'date_format:H:i',
            'after:jornada.*.hora_inicio',
        ],
    ]);

    $jornada = DB::transaction(function () use ($empleado, $datos) {
        $empleado->disponibilidades()
            ->whereNotNull('dia_semana')
            ->delete();

        foreach ($datos['jornada'] as $horario) {
            $empleado->disponibilidades()->create($horario);
        }

        return $empleado->disponibilidades()
            ->whereNotNull('dia_semana')
            ->orderBy('dia_semana')
            ->get(['id', 'dia_semana', 'hora_inicio', 'hora_fin']);
    });

    return response()->json([
        'success' => true,
        'message' => 'Jornada guardada correctamente.',
        'data' => $jornada,
    ], 200);
}

}






    