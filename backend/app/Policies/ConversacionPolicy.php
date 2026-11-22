<?php

namespace App\Policies;

use App\Models\Conversacion;
use App\Models\User;



class ConversacionPolicy
{
    public function ver(User $usuario, Conversacion $conversacion): bool
    {
        return Conversacion::cuentaPuedeConversar($usuario)
            && $conversacion->esParticipante($usuario);
    }

    public function escribir(User $usuario, Conversacion $conversacion): bool
    {
        return $this->ver($usuario, $conversacion);
    }
}
