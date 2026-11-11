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
        'imagen',
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
        'descripcion',
        'con_ninos',
        'con_otros_animales',
        'con_desconocidos',
        'necesidades_especiales',
        'observaciones',
        'observaciones_clinicas',
        'user_id',
    ];

    // Ocultar atributos sensibles/clínicos por defecto en serialización JSON
    protected $hidden = [
        'necesidades_especiales',
        'observaciones_clinicas',
    ];

    // Relación con el usuario del personal que registró al animal
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}