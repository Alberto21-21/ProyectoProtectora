<?php

namespace App\Http\Controllers;

use App\Events\MensajeEnviado;
use App\Models\Conversacion;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

// T65: mensajes de una conversación.
// T66: el mensaje guardado también se publica por WebSocket en un canal privado.
class MensajeController extends Controller
{

public function index(Request $request, int $id)
{
    $conversacion = Conversacion::find($id);

    if (!$conversacion) {
        return $this->noEncontrada();
    }

    if (Gate::forUser($request->user())->denies('ver', $conversacion)) {
        return $this->sinAcceso();
    }

    $datos = $request->validate([
        'despues_de' => 'nullable|integer|min:0',
        'limite' => 'nullable|integer|min:1|max:100'
    ], [
        'despues_de.integer' => 'El mensaje de referencia no es válido.',
        'limite.integer' => 'El límite no es válido.',
        'limite.max' => 'El límite no puede superar los 100 mensajes.'
    ]);

    $limite = $datos['limite'] ?? 50;

    $consulta = Mensaje::with('emisor:id,name,rol')
        ->where('conversacion_id', $conversacion->id);

    if (isset($datos['despues_de'])) {
        // MENSAJES POSTERIORES A UNO CONOCIDO (sincronización al reconectar, T66)
        $mensajes = $consulta
            ->where('id', '>', $datos['despues_de'])
            ->orderBy('id')
            ->limit($limite)
            ->get();
    } else {
        // ÚLTIMOS MENSAJES, DEL MÁS ANTIGUO AL MÁS RECIENTE
        $mensajes = $consulta
            ->orderByDesc('id')
            ->limit($limite)
            ->get()
            ->reverse()
            ->values();
    }

    return response()->json([
        'success' => true,
        'message' => 'Mensajes consultados correctamente.',
        'data' => $mensajes->map(fn ($mensaje) => $mensaje->aRespuesta())->values()
    ], 200);
}

public function store(Request $request, int $id)
{
    $conversacion = Conversacion::find($id);

    if (!$conversacion) {
        return $this->noEncontrada();
    }

    if (Gate::forUser($request->user())->denies('escribir', $conversacion)) {
        return $this->sinAcceso();
    }

    $datos = $request->validate([
        'texto' => 'required|string|max:2000'
    ], [
        'texto.required' => 'Escribe un mensaje.',
        'texto.string' => 'El mensaje no es válido.',
        'texto.max' => 'El mensaje no puede superar los 2000 caracteres.'
    ]);

    $mensaje = DB::transaction(function () use ($request, $conversacion, $datos) {
        $mensaje = Mensaje::create([
            'conversacion_id' => $conversacion->id,
            'user_id' => $request->user()->id,
            'texto' => $datos['texto']
        ]);

        $conversacion->forceFill(['ultimo_mensaje_at' => $mensaje->created_at])->save();

        return $mensaje;
    });

    $mensaje->load('emisor:id,name,rol');

    // PUBLICAR EL MENSAJE EN TIEMPO REAL.
    // Ya está guardado: si el servidor WebSocket no responde, el mensaje no se pierde
    // y los participantes lo recuperan al reconectar.
    try {
        event(new MensajeEnviado($mensaje));
    } catch (\Throwable $error) {
        report($error);
    }

    return response()->json([
        'success' => true,
        'message' => 'Mensaje enviado correctamente.',
        'data' => $mensaje->aRespuesta()
    ], 201);
}

private function noEncontrada()
{
    return response()->json([
        'success' => false,
        'message' => 'No se encontró la conversación.'
    ], 404);
}

private function sinAcceso()
{
    return response()->json([
        'success' => false,
        'message' => 'No participas en esta conversación.'
    ], 403);
}

}
