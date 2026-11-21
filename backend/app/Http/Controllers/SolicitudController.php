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

    // CONSULTAR LAS SOLICITUDES DEL ADOPTANTE AUTENTICADO
    public function index(Request $request)
    {
        $solicitudes = $request->user()->solicitudes()
            ->select(
                'id',
                'animal_id',
                'estado',
                'created_at',
                'updated_at'
            )
            ->with('animal:id,nombre')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Solicitudes consultadas correctamente.',
            'data' => $solicitudes
        ], 200);
    }

    // CANCELAR UNA SOLICITUD PROPIA
    public function cancelar(Request $request, $id)
    {
        $solicitud = $request->user()->solicitudes()
            ->where('id', $id)
            ->first();

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud no existe o no te pertenece.'
            ], 404);
        }

        if (!in_array($solicitud->estado, ['pendiente', 'en_revision'])) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes cancelar solicitudes pendientes o en revisión.'
            ], 422);
        }

        $solicitud->estado = 'cancelada';
        $solicitud->save();

        return response()->json([
            'success' => true,
            'message' => 'Solicitud cancelada correctamente.',
            'data' => [
                'id' => $solicitud->id,
                'estado' => $solicitud->estado,
                'updated_at' => $solicitud->updated_at
            ]
        ], 200);
    }

    // LISTAR SOLICITUDES PARA SU REVISIÓN
    public function listarRevision(Request $request)
    {
        $filtros = $request->validate([
            'buscar' => 'nullable|string|max:100',
            'estado' => 'nullable|in:pendiente,en_revision,aprobada,rechazada,cancelada'
        ]);

        $consulta = Solicitud::with([
            'usuario:id,name,email',
            'animal:id,nombre'
        ]);

        if (!empty($filtros['buscar'])) {
            $buscar = $filtros['buscar'];

            $consulta->whereHas('usuario', function ($consultaUsuario) use ($buscar) {
                $consultaUsuario->where('name', 'like', '%' . $buscar . '%')
                    ->orWhere('email', 'like', '%' . $buscar . '%');
            });
        }

        if (!empty($filtros['estado'])) {
            $consulta->where('estado', $filtros['estado']);
        }

        $solicitudes = $consulta
            ->select('id', 'user_id', 'animal_id', 'estado', 'created_at', 'updated_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Solicitudes consultadas correctamente.',
            'data' => $solicitudes
        ], 200);
    }

    // CONSULTAR UNA SOLICITUD PARA SU REVISIÓN
    public function mostrarRevision($id)
    {
        $solicitud = Solicitud::with([
            'usuario:id,name,email,fecha_nacimiento',
            'usuario.hogar',
            'animal:id,nombre,especie,raza,estado'
        ])
            ->select(
                'id',
                'user_id',
                'animal_id',
                'estado',
                'observaciones',
                'observaciones_internas',
                'created_at',
                'updated_at'
            )
            ->find($id);

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud consultada correctamente.',
            'data' => $solicitud
        ], 200);
    }

    // GUARDAR LAS OBSERVACIONES INTERNAS
    public function guardarObservacionesInternas(Request $request, $id)
    {
        $datos = $request->validate([
            'observaciones_internas' => 'present|nullable|string|max:2000'
        ], [
            'observaciones_internas.present' => 'Debes enviar el campo de observaciones internas.',
            'observaciones_internas.string' => 'Las observaciones internas deben ser un texto.',
            'observaciones_internas.max' => 'Las observaciones internas no pueden superar los 2000 caracteres.'
        ]);

        $solicitud = Solicitud::find($id);

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud no existe.'
            ], 404);
        }

        $solicitud->observaciones_internas = $datos['observaciones_internas'];
        $solicitud->save();

        return response()->json([
            'success' => true,
            'message' => 'Observaciones internas guardadas correctamente.',
            'data' => [
                'id' => $solicitud->id,
                'observaciones_internas' => $solicitud->observaciones_internas,
                'updated_at' => $solicitud->updated_at
            ]
        ], 200);
    }
}