<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// CONSULTAR EL USUARIO AUTENTICADO
Route::get('/user', function (Request $request) {
    $usuario = $request->user();

    return response()->json([
        'id' => $usuario->id,
        'name' => $usuario->name,
        'email' => $usuario->email,
        'fecha_nacimiento' => $usuario->fecha_nacimiento,
        'rol' => $usuario->rol,
        'fotografia_url' => $usuario->fotografia
            ? asset('storage/' . $usuario->fotografia)
            : null
    ]);
})->middleware('auth:sanctum');

// REGISTRAR USUARIOS
Route::post('/register', [AuthController::class, 'register']);

// INICIAR SESION
Route::post('/login', [AuthController::class, 'login']);

// CERRAR SESION
Route::post('/logout', [AuthController::class, 'cerrarSesion'])->middleware('auth:sanctum');

// ACTUALIZAR EL PERFIL DEL USUARIO AUTENTICADO
Route::put('/user', [AuthController::class, 'actualizarPerfil'])->middleware('auth:sanctum');

// ACTUALIZAR LA FOTOGRAFÍA DEL USUARIO AUTENTICADO
Route::post('/user/fotografia', [AuthController::class, 'actualizarFotografia'])->middleware('auth:sanctum');