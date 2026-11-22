<?php

namespace App\Models;

use App\Models\Animal;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnimalAsignacion extends Model
{
    protected $table = 'animal_asignaciones';

    protected $fillable = [
        'animal_id',
        'empleado_id',
        'tipo_asignacion',
        'asignado_por_user_id',
    ];

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por_user_id');
    }
}
