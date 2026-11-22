<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

// T65: lista, detalle y creación de conversaciones
class ConversacionController extends Controller
{

// CUENTAS CON LAS QUE SE PUEDE INICIAR UNA CONVERSACIÓN
public function contactos(Request $request)
{
    $usuario = $request->user();

    if (!Conversacion::cuentaPuedeConversar($usuario)) {
        return $this->sinFichaActiva();
    }

    $contactos = User::query()
        ->whereIn('rol', ['personal', 'veterinario'])
        ->where('estado', 'activo')
        ->where('id', '!=', $usuario->id)
        ->whereHas('empleado', function ($ficha) {
            $ficha->where('estado_laboral', 'activo');
        })
        ->orderBy('name')
        ->get(['id', 'name', 'rol']);

    return response()->json([
        'success' => true,
        'message' => 'Contactos consultados correctamente.',
        'data' => $contactos
    ], 200);
}

public function index(Request $request)
{
    $usuario = $request->user();

    if (!Conversacion::cuentaPuedeConversar($usuario)) {
        return $this->sinFichaActiva();
    }

    $conversaciones = Conversacion::query()
        ->delUsuario($usuario->id)
        ->with([
            'participantes:users.id,users.name,users.rol',
            'animal:id,nombre,especie,estado',
            'ultimoMensaje.emisor:id,name,rol'
        ])
        ->orderByDesc('ultimo_mensaje_at')
        ->orderByDesc('id')
        ->get();

    return response()->json([
        'success' => true,
        'message' => 'Conversaciones consultadas correctamente.',
        'data' => $conversaciones->map(fn ($c) => $this->datosConversacion($c))->values()
    ], 200);
}

public function show(Request $request, int $id)
{
    $conversacion = Conversacion::find($id);

    if (!$conversacion) {
        return response()->json([
            'success' => false,
            'message' => 'No se encontró la conversación.'
        ], 404);
    }

    if (Gate::forUser($request->user())->denies('ver', $conversacion)) {
        return $this->sinAcceso();
    }

    $conversacion->load([
        'participantes:users.id,users.name,users.rol',
        'animal:id,nombre,especie,estado',
        'ultimoMensaje.emisor:id,name,rol'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Conversación consultada correctamente.',
        'data' => $this->datosConversacion($conversacion)
    ], 200);
}

public function store(Request $request)
{
    $usuario = $request->user();

    if (!Conversacion::cuentaPuedeConversar($usuario)) {
        return $this->sinFichaActiva();
    }

    $datos = $request->validate([
        'asunto' => 'nullable|string|max:120',
        'animal_id' => 'nullable|integer|exists:animales,id,activo,1',
        'participantes' => 'required|array|min:1|max:9',
        'participantes.*' => [
            'integer',
            'distinct',
            'exists:users,id',
            Rule::notIn([$usuario->id])
        ],
        'mensaje' => 'nullable|string|max:2000'
    ], [
        'asunto.max' => 'El asunto no puede superar los 120 caracteres.',
        'animal_id.integer' => 'El identificador del animal no es válido.',
        'animal_id.exists' => 'El animal no existe o está dado de baja.',
        'participantes.required' => 'Debes indicar al menos un participante.',
        'participantes.array' => 'Los participantes no tienen un formato válido.',
        'participantes.min' => 'Debes indicar al menos un participante.',
        'participantes.max' => 'Una conversación admite como máximo 9 participantes además de ti.',
        'participantes.*.integer' => 'Un participante no es válido.',
        'participantes.*.distinct' => 'Hay participantes repetidos.',
        'participantes.*.exists' => 'Un participante no existe.',
        'participantes.*.not_in' => 'No hace falta que te incluyas: participas automáticamente.',
        'mensaje.max' => 'El mensaje no puede superar los 2000 caracteres.'
    ]);

    // SOLO SE PUEDE INVITAR A PERSONAL O VETERINARIOS CON CUENTA Y FICHA ACTIVAS
    $invitados = User::with('empleado')
        ->whereIn('id', $datos['participantes'])
        ->get();

    foreach ($invitados as $invitado) {
        if (!Conversacion::cuentaPuedeConversar($invitado)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes invitar a personal o veterinarios con cuenta y ficha laboral activas.'
            ], 422);
        }
    }

    $conversacion = DB::transaction(function () use ($usuario, $datos) {
        $conversacion = Conversacion::create([
            'tipo' => 'conversacion',
            'asunto' => $datos['asunto'] ?? null,
            'animal_id' => $datos['animal_id'] ?? null,
            'creada_por_user_id' => $usuario->id,
            'ultimo_mensaje_at' => now()
        ]);

        $conversacion->participantes()->attach(
            array_merge([$usuario->id], $datos['participantes'])
        );

        // MENSAJE INICIAL OPCIONAL
        if (!empty($datos['mensaje'])) {
            Mensaje::create([
                'conversacion_id' => $conversacion->id,
                'user_id' => $usuario->id,
                'texto' => $datos['mensaje']
            ]);
        }

        return $conversacion;
    });

    $conversacion->load([
        'participantes:users.id,users.name,users.rol',
        'animal:id,nombre,especie,estado',
        'ultimoMensaje.emisor:id,name,rol'
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Conversación creada correctamente.',
        'data' => $this->datosConversacion($conversacion)
    ], 201);
}

// FORMATO ÚNICO DE LA CONVERSACIÓN EN LAS RESPUESTAS DE LA API
private function datosConversacion(Conversacion $conversacion): array
{
    $ultimo = $conversacion->ultimoMensaje;

    return [
        'id' => $conversacion->id,
        'tipo' => $conversacion->tipo,
        'asunto' => $conversacion->asunto,
        'animal' => $conversacion->animal ? [
            'id' => $conversacion->animal->id,
            'nombre' => $conversacion->animal->nombre,
            'especie' => $conversacion->animal->especie,
            'estado' => $conversacion->animal->estado,
        ] : null,
        'creada_por_user_id' => $conversacion->creada_por_user_id,
        'participantes' => $conversacion->participantes->map(fn ($participante) => [
            'id' => $participante->id,
            'name' => $participante->name,
            'rol' => $participante->rol,
        ])->values(),
        'ultimo_mensaje' => $ultimo ? $ultimo->aRespuesta() : null,
        'ultimo_mensaje_at' => $conversacion->ultimo_mensaje_at?->toIso8601String(),
        'created_at' => $conversacion->created_at?->toIso8601String(),
    ];
}

private function sinAcceso()
{
    return response()->json([
        'success' => false,
        'message' => 'No participas en esta conversación.'
    ], 403);
}

private function sinFichaActiva()
{
    return response()->json([
        'success' => false,
        'message' => 'Necesitas una cuenta activa y una ficha laboral activa para usar las conversaciones.'
    ], 403);
}

}
