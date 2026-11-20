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
        'activo',
        'fecha_baja',
        'motivo_baja',
        'cuidador_id',
        'instrucciones_cuidado',
        'historia_publica',
        'cuidados_publicos',
        'fotografia_public_id',
        'imagen_public_id',
        'habitaculo_id',
        'minutos_paseos',
        'minutos_cuidados',
        'tiene_necesidades_especiales',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'minutos_paseos' => 'integer',
        'minutos_cuidados' => 'integer',
        'tiene_necesidades_especiales' => 'boolean',
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

    // Relación con el trabajador asignado para cuidar al animal (T17)
    public function cuidador()
    {
        return $this->belongsTo(User::class, 'cuidador_id');
    }

    // HABITÁCULO DONDE ESTÁ ALOJADO EL ANIMAL (T24)
    public function habitaculo()
    {
        return $this->belongsTo(Habitaculo::class, 'habitaculo_id', 'id');
    }

    // REGISTROS DEL HISTORIAL DEL ANIMAL
    public function historiales()
    {
        return $this->hasMany(Historial::class, 'animal_id');
    }
    // HISTORIAL DE ALOJAMIENTOS Y TRASLADOS (T25)
    public function alojamientos()
    {
        return $this->hasMany(Alojamiento::class, 'animal_id')->orderByDesc('fecha_movimiento');
    }

        // SOLICITUDES DE ADOPCIÓN DEL ANIMAL
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'animal_id');
    }
}