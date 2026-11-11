<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Animal extends Model
{
    use HasFactory;

    protected $table = 'animales';

    protected $fillable = [
        'nombre',
        'fotografia',
        'especie',
        'raza',
        'fecha_nacimiento',
        'sexo',
        'tamano',
        'peso',
        'fecha_entrada',
        'motivo',
        'estado',
        'caracter',
        'con_ninos',
        'con_otros_animales',
        'con_desconocidos',
        'necesidades_especiales',
        'observaciones',
        'user_id',
    ];

    // USUARIO DEL PERSONAL QUE REGISTRO AL ANIMAL
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}