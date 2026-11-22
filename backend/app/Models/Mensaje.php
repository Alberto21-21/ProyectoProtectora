<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id',
        'user_id',
        'texto',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // FORMATO ÚNICO DEL MENSAJE EN LAS RESPUESTAS DE LA API
    public function aRespuesta(): array
    {
        return [
            'id' => $this->id,
            'conversacion_id' => $this->conversacion_id,
            'texto' => $this->texto,
            'emisor' => [
                'id' => $this->emisor->id,
                'name' => $this->emisor->name,
                'rol' => $this->emisor->rol,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
