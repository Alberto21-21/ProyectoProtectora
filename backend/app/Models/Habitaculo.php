<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Habitaculo extends Model
{
    protected $table = 'habitaculos';

    protected $fillable = [
        'identificador',
        'zona_id',
        'capacidad',
        'estado_limpieza',
        'estado_mantenimiento',
        'fecha_ultima_limpieza',
        'fecha_proxima_limpieza',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'capacidad' => 'integer',
        'activo' => 'boolean',
        'fecha_ultima_limpieza' => 'date:Y-m-d',
        'fecha_proxima_limpieza' => 'date:Y-m-d',
    ];

    // UN HABITÁCULO PERTENECE A UNA ZONA
    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id', 'id');
    }
}
