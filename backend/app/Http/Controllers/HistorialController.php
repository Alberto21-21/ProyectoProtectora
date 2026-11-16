<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Historial;
use Illuminate\Http\Request;

class HistorialController extends Controller
{
    /**
     * T18: Consultar el historial general de un animal (orden cronológico, lo más reciente primero).
     */
    public function index($id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $registros = Historial::with('autor:id,name,rol')
            ->where('animal_id', $animal->id)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Historial consultado correctamente.',
            'data' => [
                'animal' => [
                    'id' => $animal->id,
                    'nombre' => $animal->nombre,
                    'especie' => $animal->especie,
                    'estado' => $animal->estado,
                    'activo' => $animal->activo
                ],
                'registros' => $registros
            ]
        ], 200);
    }

    /**
     * T18: Registrar una observación general sobre el animal.
     * El animal, la fecha y el autor los pone el servidor; el usuario solo escribe la descripción.
     */
    public function guardarObservacion(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        // RECHAZO: NO SE AÑADEN OBSERVACIONES A UN ANIMAL DADO DE BAJA
        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'No se pueden registrar observaciones de un animal dado de baja.'
            ], 422);
        }

        $request->validate([
            'descripcion' => 'required|string|min:5|max:2000'
        ], [
            'descripcion.required' => 'Debes escribir la observación.',
            'descripcion.string' => 'La observación debe ser un texto.',
            'descripcion.min' => 'La observación debe tener al menos 5 caracteres.',
            'descripcion.max' => 'La observación no puede superar los 2000 caracteres.'
        ]);

        $registro = new Historial();
        $registro->animal_id = $animal->id;
        $registro->user_id = $request->user()->id;
        $registro->tipo = 'observacion';
        $registro->descripcion = $request->descripcion;
        $registro->fecha = now();
        $registro->save();

        // Cargar el autor para devolverlo junto al registro
        $registro->load('autor:id,name,rol');

        return response()->json([
            'success' => true,
            'message' => 'Observación registrada correctamente en el historial.',
            'data' => $registro
        ], 201);
    }
}
