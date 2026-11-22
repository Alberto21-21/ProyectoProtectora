<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // CREAR EL ADMINISTRADOR SI NO EXISTE
        $administrador = User::where(
            'email',
            'admin@salvandohuellas.test'
        )->first();

        if (!$administrador) {
            $administrador = new User();
            $administrador->name = 'Administrador';
            $administrador->email = 'admin@salvandohuellas.test';
            $administrador->fecha_nacimiento = '1990-01-01';
            $administrador->password = Hash::make('Admin12345!');
            $administrador->rol = 'admin';
            $administrador->estado = 'activo';
            $administrador->save();
        }
    }
}