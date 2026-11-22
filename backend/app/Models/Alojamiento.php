<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alojamiento extends Model
{
    protected $table = 'alojamientos';

    protected $fillable = [
        'animal_id',
        'habitaculo_origen_id',
        'habitaculo_destino_id',
        'user_id',
        'fecha_movimiento',
        'observaciones',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function habitaculoOrigen(): BelongsTo
    {
        return $this->belongsTo(Habitaculo::class, 'habitaculo_origen_id');
    }

    public function habitaculoDestino(): BelongsTo
    {
        return $this->belongsTo(Habitaculo::class, 'habitaculo_destino_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}