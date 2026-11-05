<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // METODO REGISTER PARA REGISTRAR USUARIOS
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'password' => 'required|string|min:8|confirmed'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser un correo válido.',
            'email.max' => 'El email no puede superar los 255 caracteres.',
            'email.unique' => 'El email ya está registrado.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        $usuario = new User();
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->fecha_nacimiento = $request->fecha_nacimiento;
        $usuario->password = Hash::make($request->password);

        // EL REGISTRO PUBLICO SIEMPRE CREA ADOPTANTES
        $usuario->rol = 'adoptante';
        $usuario->save();

        // CREAR TOKEN CON LOS PERMISOS DEL USUARIO
        $tokenResultado = $usuario->createToken(
            'LaravelSanctumAuth',
            [$usuario->rol]
        );

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'rol' => $usuario->rol,
                'token' => $tokenResultado->plainTextToken
            ]
        ], 201);
    }

    // METODO LOGIN PARA INICIAR SESION
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ], [
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.'
        ]);

        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Email o contraseña incorrectos.'
            ], 401);
        }

        $usuario = Auth::user();

        // CREAR TOKEN CON LOS PERMISOS DEL USUARIO
        $tokenResultado = $usuario->createToken(
            'LaravelSanctumAuth',
            [$usuario->rol]
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada correctamente.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'rol' => $usuario->rol,
                'token' => $tokenResultado->plainTextToken
            ]
        ], 200);
    }
}