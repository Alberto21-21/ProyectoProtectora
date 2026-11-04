<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    
    //METODO REGISTER PARA REGISTRAR USUARIOS
    public function register(Request $request) 
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no puede superar los 255 caracteres.',
            'email.required' => 'El email es obligatorio.',
            'email.email' => 'El email debe ser un correo válido.',
            'email.max' => 'El email no puede superar los 255 caracteres.',
            'email.unique' => 'El email ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        $usuario = new User();
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password = bcrypt($request->password);

        // El registro público siempre crea adoptantes.
        $usuario->rol = 'adoptante';
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'rol' => $usuario->rol
            ]
        ], 201);
    }

    //METODO LOGIN PARA INICIAR SESION
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
                'rol' => $usuario->rol,
                'token' => $tokenResultado->plainTextToken
            ]
        ], 200);
    }


    //CERRAR SESION
    public function cerrarSesion(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.'
        ], 200);
    }
}