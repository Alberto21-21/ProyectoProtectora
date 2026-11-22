<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Conversacion extends Model
{
    protected $table = 'conversaciones';

    protected $fillable = [
        'tipo',
        'asunto',
        'animal_id',
        'creada_por_user_id',
        'ultimo_mensaje_at',
    ];

    protected $casts = [
        'ultimo_mensaje_at' => 'datetime',
    ];

    // CUENTAS QUE PUEDEN PARTICIPAR EN CONVERSACIONES:
    // personal o veterinario, con cuenta activa y ficha laboral activa
    public static function cuentaPuedeConversar(User $usuario): bool
    {
        if (!in_array($usuario->rol, ['personal', 'veterinario'], true)) {
            return false;
        }

        if ($usuario->estado !== 'activo') {
            return false;
        }

        $ficha = $usuario->empleado;

        return $ficha !== null && $ficha->estado_laboral === 'activo';
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creada_por_user_id');
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversacion_participantes',
            'conversacion_id',
            'user_id'
        )->withTimestamps();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'conversacion_id');
    }

    public function ultimoMensaje(): HasOne
    {
        return $this->hasOne(Mensaje::class, 'conversacion_id')->latestOfMany();
    }

    // CONVERSACIONES EN LAS QUE PARTICIPA UNA CUENTA
    public function scopeDelUsuario(Builder $consulta, int $userId): Builder
    {
        return $consulta->whereHas('participantes', function ($participantes) use ($userId) {
            $participantes->where('users.id', $userId);
        });
    }

    public function esParticipante(User $usuario): bool
    {
        return $this->participantes()
            ->where('users.id', $usuario->id)
            ->exists();
    }
}
