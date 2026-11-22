<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Historial extends Model
{
    protected $table = 'historiales';

    protected $fillable = [
        'animal_id',
        'user_id',
        'tipo',
        'descripcion',
        'fecha',
    ];

    // Devolver la fecha en formato estándar para poder mostrarla en el navegador
    protected $casts = [
        'fecha' => 'datetime',
    ];

    // Relación con el animal al que pertenece el registro
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    // Relación con el usuario que escribió el registro
    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
