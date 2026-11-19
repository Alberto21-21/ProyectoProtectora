<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hogar extends Model
{
    protected $table = 'hogares';

    protected $fillable = [
        'tipo_vivienda',
        'superficie',
        'tiene_jardin',
        'tiene_terraza',
        'superficie_exterior',
        'numero_personas',
        'hay_ninos',
        'edades_ninos',
        'hay_otros_animales',
        'otros_animales',
        'experiencia'
    ];

    protected $casts = [
        'tiene_jardin' => 'boolean',
        'tiene_terraza' => 'boolean',
        'hay_ninos' => 'boolean',
        'hay_otros_animales' => 'boolean',
        'edades_ninos' => 'array'
    ];

    // CUENTA A LA QUE PERTENECE EL HOGAR
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}