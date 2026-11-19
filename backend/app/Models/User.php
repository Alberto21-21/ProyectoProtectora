<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;



#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // OBTENER LAS ABILITIES SEGÚN EL ROL ACTUAL
    public function obtenerPermisos(): array
    {
        $permisosRol = config('permisos.roles.' . $this->rol);

        // UN ROL NO RECONOCIDO NO RECIBE PERMISOS
        if (!is_array($permisosRol)) {
            return [];
        }

        $permisosComunes = config('permisos.comunes', []);

        return array_values(
            array_unique(
                array_merge($permisosComunes, $permisosRol)
            )
        );
    }

    // FICHA LABORAL ASOCIADA A LA CUENTA
    public function empleado()
    {
        return $this->hasOne(Empleado::class, 'user_id');
    }

    // ANIMALES ASIGNADOS COMO CUIDADOR
    public function animalesACargo()
    {
        return $this->hasMany(Animal::class, 'cuidador_id');
    }

    // HOGAR DEL ADOPTANTE
    public function hogar()
    {
        return $this->hasOne(Hogar::class, 'user_id');
    }
}
