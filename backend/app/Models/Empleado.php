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

    // ASIGNACIONES DE ANIMALES DEL EMPLEADO
    public function asignaciones()
    {
        return $this->hasMany(AnimalAsignacion::class, 'empleado_id');
    }

    // PERFIL VETERINARIO ASOCIADO A LA FICHA LABORAL
    public function veterinario()
    {
        return $this->hasOne(Veterinario::class, 'empleado_id');
    }
}
