<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// T66: mensaje nuevo publicado en el canal privado de su conversación.
// ShouldBroadcastNow lo envía en el momento, sin depender de queue:work.
class MensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mensaje $mensaje)
    {
    }

    // Canal privado: solo lo reciben los participantes autorizados
    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversacion.' . $this->mensaje->conversacion_id)];
    }

    // Nombre del evento en el cliente
    public function broadcastAs(): string
    {
        return 'mensaje.enviado';
    }

    // Datos que se envían al cliente
    public function broadcastWith(): array
    {
        return [
            'mensaje' => $this->mensaje->aRespuesta(),
        ];
    }
}
