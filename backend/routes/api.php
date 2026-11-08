<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// CONSULTAR EL USUARIO AUTENTICADO
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// REGISTRAR USUARIOS
Route::post('/register', [AuthController::class, 'register']);

// INICIAR SESION
Route::post('/login', [AuthController::class, 'login']);

// CERRAR SESION
Route::post('/logout', [AuthController::class, 'cerrarSesion'])->middleware('auth:sanctum');