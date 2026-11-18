<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\VeterinarioController;
use App\Http\Controllers\DisponibilidadController;
use App\Http\Controllers\AusenciaController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ZonaController;
use App\Http\Controllers\HabitaculoController;

use App\Http\Middleware\CuentaActiva;
use App\Http\Middleware\EsAdministrador;
use App\Http\Middleware\EsPersonalAutorizado;
use App\Http\Middleware\EsVeterinario;
use App\Http\Middleware\TienePermiso;





use App\Http\Controllers\AnimalAsignacionController;
use App\Http\Middleware\VeterinarioAutorizadoParaAnimal;


// AUTENTICACIÓN Y PERFIL

// REGISTRAR USUARIOS
Route::post('/register', [AuthController::class, 'register']);

// INICIAR SESIÓN
Route::post('/login', [AuthController::class, 'login']);

// CERRAR SESIÓN
Route::post('/logout', [AuthController::class, 'cerrarSesion'])
    ->middleware('auth:sanctum');

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
            ? (
                $usuario->fotografia_public_id
                    ? $usuario->fotografia
                    : asset('storage/' . $usuario->fotografia)
            )
            : null
    ]);
})->middleware([
    'auth:sanctum',
    CuentaActiva::class,
    TienePermiso::class . ':perfil:consultar'
]);

// ACTUALIZAR EL PERFIL
Route::put('/user', [AuthController::class, 'actualizarPerfil'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        TienePermiso::class . ':perfil:editar'
    ]);

// ACTUALIZAR LA FOTOGRAFÍA
Route::post('/user/fotografia', [AuthController::class, 'actualizarFotografia'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        TienePermiso::class . ':perfil:editar'
    ]);

// CAMBIAR LA CONTRASEÑA
Route::put('/user/password', [AuthController::class, 'cambiarContrasena'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        TienePermiso::class . ':password:cambiar'
    ]);


// ADMINISTRACIÓN DE CUENTAS

// CONSULTAR Y BUSCAR USUARIOS
Route::get('/usuarios', [UsuarioController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:consultar'
    ]);

// CONSULTAR EL DETALLE DE UN USUARIO
Route::get('/usuarios/{id}', [UsuarioController::class, 'show'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:consultar'
    ]);

// CREAR CUENTAS INTERNAS
Route::post('/usuarios', [UsuarioController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:crear'
    ]);

// EDITAR LOS DATOS DE UNA CUENTA
Route::put('/usuarios/{id}', [UsuarioController::class, 'update'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:editar'
    ]);

// CAMBIAR EL ROL DE UNA CUENTA
Route::patch('/usuarios/{id}/rol', [UsuarioController::class, 'cambiarRol'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:roles'
    ]);

// DESACTIVAR UNA CUENTA
Route::patch('/usuarios/{id}/desactivar', [UsuarioController::class, 'desactivar'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':usuarios:desactivar'
    ]);


// FICHAS LABORALES

// CREAR FICHA LABORAL
Route::post('/empleados', [EmpleadoController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);

// CONSULTAR FICHA POR CUENTA
Route::get('/empleados/usuario/{userId}', [EmpleadoController::class, 'mostrarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);

// EDITAR FICHA POR CUENTA
Route::put('/empleados/usuario/{userId}', [EmpleadoController::class, 'actualizarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);


// PERFILES VETERINARIOS

// CREAR PERFIL VETERINARIO
Route::post('/veterinarios', [VeterinarioController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);

// CONSULTAR PERFIL POR CUENTA
Route::get('/veterinarios/usuario/{userId}', [VeterinarioController::class, 'mostrarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);

// EDITAR PERFIL POR CUENTA
Route::put('/veterinarios/usuario/{userId}', [VeterinarioController::class, 'actualizarPorUsuario'])
    ->whereNumber('userId')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':personal:gestionar'
    ]);


// DISPONIBILIDAD Y AUSENCIAS

// CONSULTAR EMPLEADOS PARA GESTIONAR SU DISPONIBILIDAD
Route::get('/disponibilidades/empleados', [DisponibilidadController::class, 'empleados'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':disponibilidad:gestionar'
    ]);

// CONSULTAR DISPONIBILIDAD
Route::get('/disponibilidades', [DisponibilidadController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':disponibilidad:consultar'
    ]);

// REGISTRAR DISPONIBILIDAD
Route::post('/disponibilidades', [DisponibilidadController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':disponibilidad:gestionar'
    ]);

// CONSULTAR AUSENCIAS
Route::get('/ausencias', [AusenciaController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':disponibilidad:consultar'
    ]);

// REGISTRAR AUSENCIAS
Route::post('/ausencias', [AusenciaController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':disponibilidad:gestionar'
    ]);


// ANIMALES

// CONSULTAR EL CATÁLOGO PÚBLICO
Route::get('/animales', [AnimalController::class, 'index']);

// CONSULTAR LA FICHA PÚBLICA
Route::get('/animales/{id}', [AnimalController::class, 'show'])
    ->whereNumber('id');

// DAR DE ALTA UN ANIMAL
Route::post('/animales', [AnimalController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// EDITAR LOS DATOS GENERALES
Route::put('/animales/{id}', [AnimalController::class, 'update'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// CAMBIAR LA FOTOGRAFÍA
Route::post('/animales/{id}/imagen', [AnimalController::class, 'actualizarImagen'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// ACTUALIZAR EL ESTADO
Route::patch('/animales/{id}/estado', [AnimalController::class, 'actualizarEstado'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// DAR DE BAJA UN ANIMAL
Route::patch('/animales/{id}/baja', [AnimalController::class, 'darDeBaja'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// ELIMINAR UN ANIMAL SI NO TIENE REGISTROS
Route::delete('/animales/{id}', [AnimalController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:gestionar'
    ]);

// CONSULTAR LOS ANIMALES ASIGNADOS
Route::get('/mis-animales', [AnimalController::class, 'misAnimales'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
        TienePermiso::class . ':animales:consultar'
    ]);

// ASIGNAR CUIDADOR E INSTRUCCIONES
Route::put('/animales/{id}/asignacion', [AnimalController::class, 'asignarCuidador'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsAdministrador::class,
        TienePermiso::class . ':animales:asignar'
    ]);


// INFORMACIÓN CLÍNICA


// CONSULTAR LOS DATOS CLÍNICOS
Route::get('/animales/{id}/clinico', [AnimalController::class, 'mostrarDatosClinicos'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsVeterinario::class,
        TienePermiso::class . ':clinica:consultar',
        VeterinarioAutorizadoParaAnimal::class
    ]);

// EDITAR LOS DATOS CLÍNICOS
Route::put('/animales/{id}/clinico', [AnimalController::class, 'actualizarDatosClinicos'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsVeterinario::class,
        TienePermiso::class . ':clinica:gestionar',
        VeterinarioAutorizadoParaAnimal::class
    ]);

// CONSULTAR ASIGNACIONES
Route::get('/animal-asignaciones', [AnimalAsignacionController::class, 'index'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
    ]);

// CREAR ASIGNACIONES
Route::post('/animal-asignaciones', [AnimalAsignacionController::class, 'store'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
    ]);

// ELIMINAR ASIGNACIONES
Route::delete('/animal-asignaciones/{id}', [AnimalAsignacionController::class, 'retirarasignaciones'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
    ]);

// CONSULTAR VETERINARIOS DISPONIBLES
Route::get('/animal-asignaciones/veterinarios', [AnimalAsignacionController::class, 'veterinariosDisponibles'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class,
    ]);


// HISTORIAL DEL ANIMAL (T18)

// CONSULTAR EL HISTORIAL GENERAL DE UN ANIMAL
Route::get('/animales/{id}/historial', [HistorialController::class, 'index'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class
    ]);

// REGISTRAR UNA OBSERVACIÓN GENERAL
Route::post('/animales/{id}/observaciones', [HistorialController::class, 'guardarObservacion'])
    ->whereNumber('id')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        EsPersonalAutorizado::class
    ]);

// INSTALACIONES: ZONAS Y HABITÁCULOS (T23, SOLO ADMINISTRADOR)

Route::prefix('zonas')->middleware(['auth:sanctum', 'activa', 'alguna:instalaciones:gestionar'])->group(function () {
    Route::get('/', [ZonaController::class, 'index']);
    Route::post('/', [ZonaController::class, 'store']);
    Route::get('/{id}', [ZonaController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [ZonaController::class, 'update'])->whereNumber('id');
    Route::patch('/{id}/activo', [ZonaController::class, 'toggleActivo'])->whereNumber('id');
});

Route::prefix('habitaculos')->middleware(['auth:sanctum', 'activa', 'alguna:instalaciones:gestionar'])->group(function () {
    Route::get('/', [HabitaculoController::class, 'index']);
    Route::post('/', [HabitaculoController::class, 'store']);
    Route::get('/{id}', [HabitaculoController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [HabitaculoController::class, 'update'])->whereNumber('id');
    Route::patch('/{id}/activo', [HabitaculoController::class, 'toggleActivo'])->whereNumber('id');
});
