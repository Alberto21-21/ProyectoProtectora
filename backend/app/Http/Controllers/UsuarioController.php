<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    // LISTAR Y BUSCAR USUARIOS
    public function index(Request $request)
    {
        $request->validate([
            'buscar' => 'nullable|string|max:255',
            'rol' => 'nullable|in:admin,adoptante,personal,veterinario',
            'estado' => 'nullable|in:activo,inactivo'
        ], [
            'buscar.string' => 'La búsqueda debe ser un texto.',
            'buscar.max' => 'La búsqueda no puede superar los 255 caracteres.',
            'rol.in' => 'El rol seleccionado no es válido.',
            'estado.in' => 'El estado seleccionado no es válido.'
        ]);

        // SELECCIONAR SOLO LOS DATOS NECESARIOS
        $consulta = User::select(
            'id',
            'name',
            'email',
            'rol',
            'estado'
        );

        // BUSCAR POR NOMBRE O CORREO
        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $consulta->where(function ($consultaBusqueda) use ($buscar) {
                $consultaBusqueda->where('name', 'like', '%' . $buscar . '%')
                    ->orWhere('email', 'like', '%' . $buscar . '%');
            });
        }

        // FILTRAR POR ROL
        if ($request->filled('rol')) {
            $consulta->where('rol', $request->rol);
        }

        // FILTRAR POR ESTADO
        if ($request->filled('estado')) {
            $consulta->where('estado', $request->estado);
        }

        $usuarios = $consulta->orderBy('name')
            ->orderBy('id')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Usuarios consultados correctamente.',
            'data' => $usuarios
        ], 200);
    }

    
    // CONSULTAR EL DETALLE DE UN USUARIO
    public function show($id)
    {
        $usuario = User::select(
            'id',
            'name',
            'email',
            'fecha_nacimiento',
            'rol',
            'estado',
            'fotografia',
            'created_at'
        )->find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no existe.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario consultado correctamente.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado,
                'fotografia_url' => $usuario->fotografia
                    ? asset('storage/' . $usuario->fotografia)
                    : null,
                'created_at' => $usuario->created_at
            ]
        ], 200);
    }    // DAR DE ALTA PERSONAL Y VETERINARIOS
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'fecha_nacimiento' => 'required|date|before_or_equal:today',
            'rol' => 'required|in:personal,veterinario',
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
            'rol.required' => 'Debes seleccionar un rol.',
            'rol.in' => 'Solo puedes crear cuentas de personal o veterinario.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser un texto.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.'
        ]);

        $usuario = new User();
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->fecha_nacimiento = $request->fecha_nacimiento;
        $usuario->rol = $request->rol;
        $usuario->estado = 'activo';
        $usuario->password = Hash::make($request->password);
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario interno creado correctamente.',
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'fecha_nacimiento' => $usuario->fecha_nacimiento,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado
            ]
        ], 201);
    }


}