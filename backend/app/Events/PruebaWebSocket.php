<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PruebaWebSocket implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $usuarioId, public string $mensaje)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('usuario.' . $this->usuarioId)];
    }

    public function broadcastAs(): string
    {
        return 'prueba';
    }
}