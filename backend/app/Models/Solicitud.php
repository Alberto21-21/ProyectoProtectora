<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'observaciones'
    ];

    // ADOPTANTE QUE REALIZA LA SOLICITUD
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ANIMAL QUE SE SOLICITA ADOPTAR
    public function animal()
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }
}