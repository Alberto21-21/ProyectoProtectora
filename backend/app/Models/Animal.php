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
        'especie',
        'raza',
        'edad',
        'sexo',
        'estado',
        'descripcion',
        'imagen',
        'peso',
        'caracter',
        'necesidades_especiales',
        'observaciones_clinicas',
    ];

    // LOS DATOS CLÍNICOS NO SE ENVÍAN EN EL LISTADO NI EN LA FICHA PÚBLICA
    protected $hidden = [
        'necesidades_especiales',
        'observaciones_clinicas',
    ];
}