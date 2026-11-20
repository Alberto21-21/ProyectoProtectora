<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudController extends Controller
{
    // SOLICITAR LA ADOPCIÓN DE UN ANIMAL
    public function store(Request $request)
    {
        $datos = $request->validate([
            'animal_id' => 'required|integer|exists:animales,id',
            'observaciones' => 'nullable|string|max:2000',
            'user_id' => 'prohibited',
            'estado' => 'prohibited'
        ], [
            'animal_id.required' => 'Debes indicar el animal.',
            'animal_id.integer' => 'El identificador del animal no es válido.',
            'animal_id.exists' => 'El animal no existe.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 2000 caracteres.',
            'user_id.prohibited' => 'El usuario se obtiene de la sesión.',
            'estado.prohibited' => 'El estado lo establece el servidor.'
        ]);
        return DB::transaction(function () use ($request, $datos) {

            $animal = Animal::where('id', $datos['animal_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$animal->activo || $animal->estado !== 'Disponible') {
                return response()->json([
                    'success' => false,
                    'message' => 'El animal no está disponible para adopción.'
                ], 422);
            }

            $usuario = $request->user();

            $solicitudActiva = $usuario->solicitudes()
                ->where('animal_id', $animal->id)
                ->whereIn('estado', [
                    'pendiente',
                    'en_revision',
                    'aprobada'
                ])
                ->exists();

            if ($solicitudActiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una solicitud activa para este animal.'
                ], 422);
            }

            $solicitud = new Solicitud();
            $solicitud->observaciones = $datos['observaciones'] ?? null;
            $solicitud->estado = 'pendiente';

            $solicitud->usuario()->associate($usuario);
            $animal->solicitudes()->save($solicitud);

            return response()->json([
                'success' => true,
                'message' => 'Solicitud de adopción enviada correctamente.',
                'data' => [
                    'id' => $solicitud->id,
                    'animal_id' => $solicitud->animal_id,
                    'estado' => $solicitud->estado,
                    'observaciones' => $solicitud->observaciones,
                    'created_at' => $solicitud->created_at
                ]
            ], 201);
        });
    }
}