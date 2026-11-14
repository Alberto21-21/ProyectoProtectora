<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateEstadoAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class AnimalController extends Controller
{
    /**
     * Obtener listado de animales con búsqueda y filtros.
     * El catálogo público muestra únicamente los animales disponibles.
     */
    public function index(Request $request)
    {
        $query = Animal::query();

        // Detectar usuario autenticado mediante token Bearer en ruta pública
        $usuario = $request->user('sanctum');
        if (!$usuario && $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($request->bearerToken());
            $usuario = $accessToken?->tokenable;
        }

        $esPersonalAutorizado = $usuario && in_array($usuario->rol, ['admin', 'personal', 'veterinario', 'empleado']);

        // Los animales dados de baja no aparecen en el listado, salvo que el personal los pida
        if (!$esPersonalAutorizado || !$request->boolean('bajas')) {
            $query->where('activo', true);
        } else {
            $query->where('activo', false);
        }

        // Si no es personal autorizado (visitante o adoptante), solo ve los disponibles
        if (!$esPersonalAutorizado) {
            $query->where('estado', 'Disponible');
        } elseif ($request->filled('estado')) {
            // El personal autorizado sí puede filtrar por cualquier estado
            $query->where('estado', $request->input('estado'));
        }

        // Filtro por término de búsqueda (nombre o raza)
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('raza', 'like', "%{$buscar}%");
            });
        }

        // Filtro por Especie (Perro, Gato, Otro)
        if ($request->filled('especie')) {
            $query->where('especie', $request->input('especie'));
        }

        // Devolver la lista ordenada por fecha de creación reciente
        return response()->json($query->orderBy('created_at', 'desc')->get(), 200);
    }

    /**
     * Obtener el detalle individual de un animal (para la ficha).
     */
    public function show($id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        return response()->json($animal, 200);
    }

    /**
     * Registrar un nuevo animal (Dar de alta).
     */
    public function store(StoreAnimalRequest $request)
    {
        // Datos validados por el FormRequest
        $datos = $request->validated();

        // Guardar la fotografía si se ha subido
        if ($request->hasFile('fotografia')) {
            $datos['fotografia'] = $request->file('fotografia')->store('animales', 'public');
        } elseif ($request->hasFile('imagen')) {
            $datos['fotografia'] = $request->file('imagen')->store('animales', 'public');
        }

        // Registrar el usuario que da de alta (si hay autenticación activa)
        if ($request->user()) {
            $datos['user_id'] = $request->user()->id;
        }

        $animal = Animal::create($datos);

        return response()->json([
            'success' => true,
            'message' => 'Animal registrado correctamente.',
            'data' => [
                'id' => $animal->id,
                'nombre' => $animal->nombre,
                'especie' => $animal->especie,
                'estado' => $animal->estado,
                'fotografia_url' => $animal->fotografia
                    ? asset('storage/' . $animal->fotografia)
                    : null
            ]
        ], 201);
    }

    /**
     * Actualizar los datos generales de un animal (sin saltar las transiciones de estado).
     */
    public function update(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:Perro,Gato,Otro',
            'raza' => 'nullable|string|max:100',
            'edad' => 'nullable|integer|min:0|max:40',
            'sexo' => 'required|in:Macho,Hembra',
            'peso' => 'nullable|numeric|min:0|max:200',
            'caracter' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'especie.required' => 'La especie es obligatoria.',
            'especie.in' => 'La especie debe ser Perro, Gato u Otro.',
            'raza.string' => 'La raza debe ser un texto.',
            'raza.max' => 'La raza no puede superar los 100 caracteres.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.min' => 'La edad no puede ser negativa.',
            'edad.max' => 'La edad no puede ser mayor de 40 años.',
            'sexo.required' => 'El sexo es obligatorio.',
            'sexo.in' => 'El sexo debe ser Macho o Hembra.',
            'peso.numeric' => 'El peso debe ser un número.',
            'peso.min' => 'El peso no puede ser negativo.',
            'peso.max' => 'El peso no puede ser mayor de 200 kg.',
            'caracter.string' => 'El carácter debe ser un texto.',
            'caracter.max' => 'El carácter no puede superar los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser un texto.'
        ]);

        $animal->nombre = $request->nombre;
        $animal->especie = $request->especie;
        $animal->raza = $request->raza;
        $animal->edad = $request->edad;
        $animal->sexo = $request->sexo;
        $animal->peso = $request->peso;
        $animal->caracter = $request->caracter;
        $animal->descripcion = $request->descripcion;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'Ficha del animal actualizada correctamente.',
            'data' => $animal
        ], 200);
    }

    /**
     * T15: Actualizar el estado de un animal aplicando solo transiciones permitidas.
     */
    public function actualizarEstado(UpdateEstadoAnimalRequest $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        // Un animal dado de baja no admite cambios de estado
        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El animal está dado de baja y no admite cambios de estado.'
            ], 422);
        }

        $estadoActual = $animal->estado;
        $nuevoEstado = $request->validated()['estado'];

        // La adopción formalizada se realiza desde su proceso específico
        if ($nuevoEstado === 'Adoptado') {
            return response()->json([
                'success' => false,
                'message' => 'La adopción formalizada debe realizarse desde su proceso específico.'
            ], 422);
        }

        if ($nuevoEstado === $estadoActual) {
            return response()->json([
                'success' => false,
                'message' => "El animal ya se encuentra en estado '{$estadoActual}'."
            ], 422);
        }

        // Definición de transiciones permitidas
        $transicionesPermitidas = [
            'En acogida'     => ['En tratamiento', 'Disponible'],
            'En tratamiento' => ['En acogida', 'Disponible'],
            'Disponible'     => ['En acogida', 'En tratamiento', 'Reservado'],
            'Reservado'      => ['Disponible', 'En acogida', 'En tratamiento'],
            'Adoptado'       => [], // Estado final: no admite cambios manuales
        ];

        $permitidos = $transicionesPermitidas[$estadoActual] ?? [];

        if (!in_array($nuevoEstado, $permitidos, true)) {
            return response()->json([
                'success' => false,
                'message' => "Transición no permitida: un animal en estado '{$estadoActual}' no puede pasar a '{$nuevoEstado}'."
            ], 422);
        }

        $animal->estado = $nuevoEstado;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => "Estado actualizado a '{$nuevoEstado}' correctamente.",
            'data' => $animal
        ], 200);
    }

    /**
     * T16: Dar de baja un animal (baja lógica).
     * El animal no se borra: se marca como inactivo y se conservan
     * su historial, solicitudes y adopciones.
     */
    public function darDeBaja(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'motivo_baja' => 'required|string|max:255'
        ], [
            'motivo_baja.required' => 'Debes indicar el motivo de la baja.',
            'motivo_baja.string' => 'El motivo debe ser un texto.',
            'motivo_baja.max' => 'El motivo no puede superar los 255 caracteres.'
        ]);

        // RECHAZO: EL ANIMAL YA ESTÁ DADO DE BAJA
        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El animal ya está dado de baja desde el ' . $animal->fecha_baja . '.'
            ], 422);
        }

        // RECHAZO: EL ANIMAL TIENE UNA RESERVA EN CURSO
        if ($animal->estado === 'Reservado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede dar de baja: el animal está reservado por un proceso de adopción en curso. Cambia primero su estado.'
            ], 422);
        }

        $animal->activo = false;
        $animal->fecha_baja = now()->toDateString();
        $animal->motivo_baja = $request->motivo_baja;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'El animal se ha dado de baja correctamente. Sus registros se conservan.',
            'data' => $animal
        ], 200);
    }

    /**
     * T16: Eliminar un animal definitivamente.
     * Solo se permite si no tiene historial, solicitudes ni adopciones.
     * Si los tiene, se rechaza para no perder su historia.
     */
    public function destroy($id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        // COMPROBAR LOS REGISTROS ASOCIADOS AL ANIMAL
        $historiales = 0;
        $solicitudes = 0;
        $adopciones = 0;

        if (Schema::hasTable('historiales')) {
            $historiales = DB::table('historiales')->where('animal_id', $animal->id)->count();
        }

        if (Schema::hasTable('solicitudes')) {
            $solicitudes = DB::table('solicitudes')->where('animal_id', $animal->id)->count();
        }

        if (Schema::hasTable('adopciones')) {
            $adopciones = DB::table('adopciones')->where('animal_id', $animal->id)->count();
        }

        // UN ANIMAL ADOPTADO SIEMPRE TIENE UNA ADOPCIÓN QUE CONSERVAR
        if ($animal->estado === 'Adoptado' && $adopciones === 0) {
            $adopciones = 1;
        }

        // RECHAZO: TIENE REGISTROS QUE NO SE PUEDEN PERDER
        if ($historiales > 0 || $solicitudes > 0 || $adopciones > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: el animal tiene registros asociados (historiales: ' . $historiales
                    . ', solicitudes: ' . $solicitudes
                    . ', adopciones: ' . $adopciones
                    . '). Usa "Dar de baja" para conservar su historia.'
            ], 422);
        }

        // RECHAZO: EL ANIMAL TIENE UNA RESERVA EN CURSO
        if ($animal->estado === 'Reservado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: el animal está reservado por un proceso de adopción en curso.'
            ], 422);
        }

        // ELIMINACIÓN DEFINITIVA
        $fotografia = $animal->fotografia;
        $imagen = $animal->imagen;

        $animal->delete();

        if ($fotografia) {
            Storage::disk('public')->delete($fotografia);
        }

        if ($imagen) {
            Storage::disk('public')->delete($imagen);
        }

        return response()->json([
            'success' => true,
            'message' => 'El animal no tenía registros asociados y se ha eliminado definitivamente.'
        ], 200);
    }

    /**
     * Actualizar la fotografía de un animal.
     */
    public function actualizarImagen(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'imagen.required' => 'Debes seleccionar una fotografía.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La fotografía debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La fotografía no puede superar los 2 MB.'
        ]);

        $imagenAnterior = $animal->imagen;

        // Guardar la nueva imagen
        $ruta = $request->file('imagen')->store('animales', 'public');

        $animal->imagen = $ruta;
        $animal->save();

        // Eliminar la imagen anterior si existía
        if ($imagenAnterior) {
            Storage::disk('public')->delete($imagenAnterior);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fotografía del animal actualizada correctamente.',
            'data' => [
                'imagen_url' => asset('storage/' . $ruta)
            ]
        ], 200);
    }

    /**
     * Consultar los datos clínicos de un animal.
     */
    public function mostrarDatosClinicos($id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $animal->id,
                'necesidades_especiales' => $animal->necesidades_especiales,
                'observaciones_clinicas' => $animal->observaciones_clinicas
            ]
        ], 200);
    }

    /**
     * Actualizar los datos clínicos de un animal.
     */
    public function actualizarDatosClinicos(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'necesidades_especiales' => 'nullable|string|max:2000',
            'observaciones_clinicas' => 'nullable|string|max:2000'
        ], [
            'necesidades_especiales.string' => 'Las necesidades especiales deben ser un texto.',
            'necesidades_especiales.max' => 'Las necesidades especiales no pueden superar los 2000 caracteres.',
            'observaciones_clinicas.string' => 'Las observaciones clínicas deben ser un texto.',
            'observaciones_clinicas.max' => 'Las observaciones clínicas no pueden superar los 2000 caracteres.'
        ]);

        $animal->necesidades_especiales = $request->necesidades_especiales;
        $animal->observaciones_clinicas = $request->observaciones_clinicas;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'Datos clínicos actualizados correctamente.',
            'data' => [
                'id' => $animal->id,
                'necesidades_especiales' => $animal->necesidades_especiales,
                'observaciones_clinicas' => $animal->observaciones_clinicas
            ]
        ], 200);
    }
}