<?php

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
use App\Http\Controllers\AnimalAsignacionController;
use App\Http\Middleware\VeterinarioAutorizadoParaAnimal;
use App\Http\Controllers\HogarController;


// AUTENTICACIÓN Y PERFIL

// REGISTRAR USUARIOS
Route::post('/register', [AuthController::class, 'register']);

// INICIAR SESIÓN
Route::post('/login', [AuthController::class, 'login']);

// CERRAR SESIÓN
Route::post('/logout', [AuthController::class, 'cerrarSesion'])
    ->middleware('auth:sanctum');

// PERFIL DEL USUARIO AUTENTICADO
Route::prefix('user')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR EL PERFIL
        Route::get('/', [AuthController::class, 'mostrarPerfil'])
            ->middleware('abilities:perfil:consultar');

        // ACTUALIZAR LOS DATOS
        Route::put('/', [AuthController::class, 'actualizarPerfil'])
            ->middleware('abilities:perfil:editar');

        // ACTUALIZAR LA FOTOGRAFÍA
        Route::post('/fotografia', [AuthController::class, 'actualizarFotografia'])
            ->middleware('abilities:perfil:editar');

        // CAMBIAR LA CONTRASEÑA
        Route::put('/password', [AuthController::class, 'cambiarContrasena'])
            ->middleware('abilities:password:cambiar');

    });

// HOGAR DEL ADOPTANTE
Route::prefix('hogar')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR EL HOGAR PROPIO
        Route::get('/', [HogarController::class, 'mostrar'])
            ->middleware('abilities:hogar:consultar');

        // GUARDAR O ACTUALIZAR EL HOGAR PROPIO
        Route::put('/', [HogarController::class, 'guardar'])
            ->middleware('abilities:hogar:editar');

    });

// ADMINISTRACIÓN DE USUARIOS
Route::prefix('usuarios')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR Y BUSCAR USUARIOS
        Route::get('/', [UsuarioController::class, 'index'])
            ->middleware('abilities:usuarios:consultar');

        // CONSULTAR EL DETALLE
        Route::get('/{id}', [UsuarioController::class, 'show'])
            ->whereNumber('id')
            ->middleware('abilities:usuarios:consultar');

        // CREAR UNA CUENTA INTERNA
        Route::post('/', [UsuarioController::class, 'store'])
            ->middleware('abilities:usuarios:crear');

        // EDITAR LOS DATOS
        Route::put('/{id}', [UsuarioController::class, 'update'])
            ->whereNumber('id')
            ->middleware('abilities:usuarios:editar');

        // CAMBIAR EL ROL
        Route::patch('/{id}/rol', [UsuarioController::class, 'cambiarRol'])
            ->whereNumber('id')
            ->middleware('abilities:usuarios:roles');

        // DESACTIVAR LA CUENTA
        Route::patch('/{id}/desactivar', [UsuarioController::class, 'desactivar'])
            ->whereNumber('id')
            ->middleware('abilities:usuarios:desactivar');

    });

// FICHAS LABORALES
Route::prefix('empleados')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:personal:gestionar'
    ])
    ->group(function () {

        Route::post('/', [EmpleadoController::class, 'store']);

        Route::get('/usuario/{userId}', [
            EmpleadoController::class,
            'mostrarPorUsuario'
        ])->whereNumber('userId');

        Route::put('/usuario/{userId}', [
            EmpleadoController::class,
            'actualizarPorUsuario'
        ])->whereNumber('userId');

    });

// PERFILES VETERINARIOS
Route::prefix('veterinarios')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:personal:gestionar'
    ])
    ->group(function () {

        Route::post('/', [VeterinarioController::class, 'store']);

        Route::get('/usuario/{userId}', [
            VeterinarioController::class,
            'mostrarPorUsuario'
        ])->whereNumber('userId');

        Route::put('/usuario/{userId}', [
            VeterinarioController::class,
            'actualizarPorUsuario'
        ])->whereNumber('userId');

    });
    
// DISPONIBILIDAD
Route::prefix('disponibilidades')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR EMPLEADOS
        Route::get('/empleados', [
            DisponibilidadController::class,
            'empleados'
        ])->middleware('abilities:disponibilidad:gestionar');

        // CONSULTAR DISPONIBILIDAD
        Route::get('/', [DisponibilidadController::class, 'index'])
            ->middleware('abilities:disponibilidad:consultar');

        // REGISTRAR DISPONIBILIDAD
        Route::post('/', [DisponibilidadController::class, 'store'])
            ->middleware('abilities:disponibilidad:gestionar');

    });

// AUSENCIAS
Route::prefix('ausencias')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR AUSENCIAS
        Route::get('/', [AusenciaController::class, 'index'])
            ->middleware('abilities:disponibilidad:consultar');

        // REGISTRAR AUSENCIAS
        Route::post('/', [AusenciaController::class, 'store'])
            ->middleware('abilities:disponibilidad:gestionar');

    });

// ANIMALES
Route::prefix('animales')->group(function () {

    // CATÁLOGO Y FICHA PÚBLICOS
    Route::get('/', [AnimalController::class, 'index']);

    Route::get('/{id}', [AnimalController::class, 'show'])
        ->whereNumber('id');

    // GESTIÓN DE ANIMALES
    Route::middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:animales:gestionar'
    ])->group(function () {

        Route::post('/', [AnimalController::class, 'store']);

        Route::put('/{id}', [AnimalController::class, 'update'])
            ->whereNumber('id');

        Route::post('/{id}/imagen', [
            AnimalController::class,
            'actualizarImagen'
        ])->whereNumber('id');

        Route::patch('/{id}/estado', [
            AnimalController::class,
            'actualizarEstado'
        ])->whereNumber('id');

        Route::patch('/{id}/baja', [
            AnimalController::class,
            'darDeBaja'
        ])->whereNumber('id');

        Route::delete('/{id}', [AnimalController::class, 'destroy'])
            ->whereNumber('id');

    });

    // ASIGNAR CUIDADOR
    Route::put('/{id}/asignacion', [
        AnimalController::class,
        'asignarCuidador'
    ])->whereNumber('id')->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:animales:asignar'
    ]);

    // INFORMACIÓN CLÍNICA
    Route::middleware([
        'auth:sanctum',
        CuentaActiva::class
    ])->group(function () {

        Route::get('/{id}/clinico', [
            AnimalController::class,
            'mostrarDatosClinicos'
        ])->whereNumber('id')->middleware([
            'abilities:clinica:consultar',
            VeterinarioAutorizadoParaAnimal::class
        ]);

        Route::put('/{id}/clinico', [
            AnimalController::class,
            'actualizarDatosClinicos'
        ])->whereNumber('id')->middleware([
            'abilities:clinica:gestionar',
            VeterinarioAutorizadoParaAnimal::class
        ]);

    });

});

// CONSULTAR LOS ANIMALES ASIGNADOS
Route::get('/mis-animales', [AnimalController::class, 'misAnimales'])
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:animales:gestionar'
    ]);

// ASIGNACIONES DE ANIMALES
Route::prefix('animal-asignaciones')
    ->middleware(['auth:sanctum', CuentaActiva::class])
    ->group(function () {

        // CONSULTAR ASIGNACIONES
        Route::get('/', [AnimalAsignacionController::class, 'index'])
            ->middleware('abilities:asignaciones:consultar');

        // CONSULTAR VETERINARIOS DISPONIBLES
        Route::get('/veterinarios', [
            AnimalAsignacionController::class,
            'veterinariosDisponibles'
        ])->middleware('abilities:asignaciones:gestionar');

        // CREAR UNA ASIGNACIÓN
        Route::post('/', [AnimalAsignacionController::class, 'store'])
            ->middleware('abilities:asignaciones:gestionar');

        // RETIRAR UNA ASIGNACIÓN
        Route::delete('/{id}', [
            AnimalAsignacionController::class,
            'retirarasignaciones'
        ])->whereNumber('id')
            ->middleware('abilities:asignaciones:gestionar');

    });

// HISTORIAL DE ANIMALES
Route::prefix('animales')
    ->middleware([
        'auth:sanctum',
        CuentaActiva::class,
        'abilities:animales:gestionar'
    ])
    ->group(function () {

        // CONSULTAR EL HISTORIAL
        Route::get('/{id}/historial', [
            HistorialController::class,
            'index'
        ])->whereNumber('id');

        // REGISTRAR UNA OBSERVACIÓN GENERAL
        Route::post('/{id}/observaciones', [
            HistorialController::class,
            'guardarObservacion'
        ])->whereNumber('id');

    });

// INSTALACIONES: ZONAS Y HABITÁCULOS (T23 GESTIÓN, T24 CONSULTA)
Route::prefix('zonas')->middleware(['auth:sanctum', 'activa'])->group(function () {

    // CONSULTA: ADMINISTRADOR, PERSONAL Y VETERINARIOS (T24)
    Route::get('/', [ZonaController::class, 'index'])->middleware('alguna:instalaciones:consultar');

    // GESTIÓN: SOLO ADMINISTRADOR (T23)
    Route::post('/', [ZonaController::class, 'store'])->middleware('alguna:instalaciones:gestionar');
    Route::get('/{id}', [ZonaController::class, 'show'])->whereNumber('id')->middleware('alguna:instalaciones:gestionar');
    Route::put('/{id}', [ZonaController::class, 'update'])->whereNumber('id')->middleware('alguna:instalaciones:gestionar');
    Route::patch('/{id}/activo', [ZonaController::class, 'toggleActivo'])->whereNumber('id')->middleware('alguna:instalaciones:gestionar');
});

Route::prefix('habitaculos')->middleware(['auth:sanctum', 'activa'])->group(function () {

    // CONSULTA: ADMINISTRADOR, PERSONAL Y VETERINARIOS (T24)
    Route::get('/', [HabitaculoController::class, 'index'])->middleware('alguna:instalaciones:consultar');
    Route::get('/{id}', [HabitaculoController::class, 'show'])->whereNumber('id')->middleware('alguna:instalaciones:consultar');
    Route::get('/{id}/ficha', [HabitaculoController::class, 'ficha'])->whereNumber('id')->middleware('alguna:instalaciones:consultar');

    // ALOJAR Y TRASLADAR ANIMALES + HISTORIAL (T25)
    Route::get('/alojamientos', [HabitaculoController::class, 'historialAlojamientos'])->middleware('alguna:instalaciones:consultar');
    Route::post('/alojar', [HabitaculoController::class, 'alojarAnimal'])->middleware('alguna:instalaciones:consultar');

    // GESTIÓN: SOLO ADMINISTRADOR (T23)
    Route::post('/', [HabitaculoController::class, 'store'])->middleware('alguna:instalaciones:gestionar');
    Route::put('/{id}', [HabitaculoController::class, 'update'])->whereNumber('id')->middleware('alguna:instalaciones:gestionar');
    Route::patch('/{id}/activo', [HabitaculoController::class, 'toggleActivo'])->whereNumber('id')->middleware('alguna:instalaciones:gestionar');
});