<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
