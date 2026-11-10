<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\EsAdministrador;
use App\Http\Middleware\EsPersonalAutorizado;
use App\Http\Middleware\EsVeterinario;

// CONSULTAR EL PERFIL DEL USUARIO AUTENTICADO
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
Route::post('/logout', [AuthController::class, 'cerrarSesion'])
    ->middleware('auth:sanctum');

// ACTUALIZAR EL PERFIL DEL USUARIO AUTENTICADO
Route::put('/user', [AuthController::class, 'actualizarPerfil'])
    ->middleware('auth:sanctum');

// ACTUALIZAR LA FOTOGRAFÍA DEL USUARIO AUTENTICADO
Route::post('/user/fotografia', [AuthController::class, 'actualizarFotografia'])
    ->middleware('auth:sanctum');

// CAMBIAR LA CONTRASEÑA DEL USUARIO AUTENTICADO
Route::put('/user/password', [AuthController::class, 'cambiarContrasena'])
    ->middleware('auth:sanctum');

// CONSULTAR Y BUSCAR USUARIOS: SOLO ADMINISTRADORES
Route::get('/usuarios', [UsuarioController::class, 'index'])
    ->middleware(['auth:sanctum', EsAdministrador::class]);

// CONSULTAR EL DETALLE DE UN USUARIO: SOLO ADMINISTRADORES
Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])->whereNumber('id')
    ->middleware(['auth:sanctum', EsAdministrador::class]);

// RUTAS DE ANIMALES
Route::get('/animales', [AnimalController::class, 'index']);
Route::get('/animales/{id}', [AnimalController::class, 'show'])->whereNumber('id');
Route::post('/animales', [AnimalController::class, 'store']);

// EDITAR LOS DATOS GENERALES DE UN ANIMAL: SOLO PERSONAL AUTORIZADO
Route::put('/animales/{id}', [AnimalController::class, 'update'])->whereNumber('id')
    ->middleware(['auth:sanctum', EsPersonalAutorizado::class]);

// CAMBIAR LA FOTOGRAFÍA DE UN ANIMAL: SOLO PERSONAL AUTORIZADO
Route::post('/animales/{id}/imagen', [AnimalController::class, 'actualizarImagen'])->whereNumber('id')
    ->middleware(['auth:sanctum', EsPersonalAutorizado::class]);

// CONSULTAR LOS DATOS CLÍNICOS: SOLO VETERINARIOS Y ADMINISTRADORES
Route::get('/animales/{id}/clinico', [AnimalController::class, 'mostrarDatosClinicos'])->whereNumber('id')
    ->middleware(['auth:sanctum', EsVeterinario::class]);

// EDITAR LOS DATOS CLÍNICOS: SOLO VETERINARIOS Y ADMINISTRADORES
Route::put('/animales/{id}/clinico', [AnimalController::class, 'actualizarDatosClinicos'])->whereNumber('id')
    ->middleware(['auth:sanctum', EsVeterinario::class]);