<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    protected $table = 'zonas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // UNA ZONA TIENE MUCHOS HABITÁCULOS
    public function habitaculos(): HasMany
    {
        return $this->hasMany(Habitaculo::class, 'zona_id', 'id');
    }
}
