<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoSolicitud extends Model
{
    protected $table = 'seguimientos_solicitudes';

    // SOLICITUD A LA QUE PERTENECE EL SEGUIMIENTO
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'solicitud_id');
    }

    // USUARIO QUE REGISTRA LA ACTUACIÓN
    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}