<?php

use App\Models\Conversacion;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('usuario.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// T66: canal privado de cada conversación.
// Solo entran los participantes (la misma política que usa la API).
Broadcast::channel('conversacion.{id}', function ($user, $id) {
    $conversacion = Conversacion::find($id);

    return $conversacion !== null
        && Gate::forUser($user)->allows('ver', $conversacion);
});
