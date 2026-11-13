<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $fillable = [
        'user_id',
        'telefono',
        'puesto',
        'estado_laboral',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function disponibilidades(): HasMany
{
    return $this->hasMany(Disponibilidad::class);
}

public function ausencias(): HasMany
{
    return $this->hasMany(Ausencia::class);
}
}
