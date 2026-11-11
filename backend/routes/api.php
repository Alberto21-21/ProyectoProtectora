<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EmpleadoController;

use App\Http\Middleware\CuentaActiva;
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
})->middleware(['auth:sanctum', CuentaActiva::class]);


// REGISTRAR USUARIOS
Route::post('/register', [AuthController::class, 'register']);


// INICIAR SESION
Route::post('/login', [AuthController::class, 'login']);


// CERRAR SESION
Route::post('/logout', [AuthController::class, 'cerrarSesion'])
    ->middleware('auth:sanctum');


// ACTUALIZAR EL PERFIL DEL USUARIO AUTENTICADO
Route::put('/user', [AuthController::class, 'actualizarPerfil'])
    ->middleware(['auth:sanctum', CuentaActiva::class]);


// ACTUALIZAR LA FOTOGRAFÍA DEL USUARIO AUTENTICADO
Route::post('/user/fotografia', [AuthController::class, 'actualizarFotografia'])
    ->middleware(['auth:sanctum', CuentaActiva::class]);


// CAMBIAR LA CONTRASEÑA DEL USUARIO AUTENTICADO
Route::put('/user/password', [AuthController::class, 'cambiarContrasena'])
    ->middleware(['auth:sanctum', CuentaActiva::class]);


// ==========================================================================
// ADMINISTRACIÓN DE CUENTAS
// ==========================================================================

// CONSULTAR Y BUSCAR USUARIOS
Route::get('/usuarios', [UsuarioController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// CONSULTAR EL DETALLE DE UN USUARIO
Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// CREAR CUENTAS INTERNAS
Route::post('/usuarios', [UsuarioController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);

// EDITAR LOS DATOS DE UNA CUENTA
Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);

// DESACTIVAR UNA CUENTA
Route::patch('/usuarios/{id}/desactivar', [UsuarioController::class, 'desactivar'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// ==========================================================================
// FICHAS LABORALES
// ==========================================================================

// CREAR FICHA LABORAL
Route::post('/empleados', [EmpleadoController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// CONSULTAR FICHA POR CUENTA
Route::get('/empleados/usuario/{userId}', [EmpleadoController::class, 'mostrarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// EDITAR FICHA POR CUENTA
Route::put('/empleados/usuario/{userId}', [EmpleadoController::class, 'actualizarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class
    ]);


// ==========================================================================
// RUTAS DE ANIMALES
// ==========================================================================

// LISTAR TODOS LOS ANIMALES
Route::get('/animales', [AnimalController::class, 'index']);


// CONSULTAR EL DETALLE DE UN ANIMAL
Route::get('/animales/{id}', [AnimalController::class, 'show'])
    ->whereNumber('id');


// DAR DE ALTA UN ANIMAL
Route::post('/animales', [AnimalController::class, 'store'])
    ->middleware(['auth:sanctum', CuentaActiva::class]);


// EDITAR LOS DATOS GENERALES DE UN ANIMAL
Route::put('/animales/{id}', [AnimalController::class, 'update'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class
    ]);


// CAMBIAR LA FOTOGRAFÍA DE UN ANIMAL
Route::post('/animales/{id}/imagen', [AnimalController::class, 'actualizarImagen'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class
    ]);


// CONSULTAR LOS DATOS CLÍNICOS
Route::get('/animales/{id}/clinico', [AnimalController::class, 'mostrarDatosClinicos'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsVeterinario::class
    ]);


// EDITAR LOS DATOS CLÍNICOS
Route::put('/animales/{id}/clinico', [AnimalController::class, 'actualizarDatosClinicos'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsVeterinario::class
    ]);