<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnimalController extends Controller
{
    /**
     * Obtener listado de animales con búsqueda y filtros.
     */
    public function index(Request $request)
    {
        $query = Animal::query();

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

        // Filtro por Estado (Disponible, En acogida, Adoptado)
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
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
            return response()->json(['message' => 'Animal no encontrado'], 404);
        }

        return response()->json($animal, 200);
    }

    /**
     * Registrar un nuevo animal (manteniendo tu método store).
     */
    public function store(StoreAnimalRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('animales', 'public');
            $validated['imagen'] = $path;
        }

        $animal = Animal::create($validated);

        return response()->json([
            'message' => 'Animal registrado correctamente',
            'animal'  => $animal
        ], 201);
    }

    // ACTUALIZAR LOS DATOS GENERALES DE UN ANIMAL
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
            'estado' => 'required|in:Disponible,En acogida,Adoptado,En Tratamiento',
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
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'peso.numeric' => 'El peso debe ser un número.',
            'peso.min' => 'El peso no puede ser negativo.',
            'peso.max' => 'El peso no puede ser mayor de 200 kg.',
            'caracter.string' => 'El carácter debe ser un texto.',
            'caracter.max' => 'El carácter no puede superar los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser un texto.'
        ]);

        // SOLO SE GUARDAN LOS DATOS GENERALES, LOS CLÍNICOS NO SE TOCAN AQUÍ
        $animal->nombre = $request->nombre;
        $animal->especie = $request->especie;
        $animal->raza = $request->raza;
        $animal->edad = $request->edad;
        $animal->sexo = $request->sexo;
        $animal->estado = $request->estado;
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

    // ACTUALIZAR LA FOTOGRAFÍA DE UN ANIMAL
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

        // GUARDAR LA NUEVA IMAGEN
        $ruta = $request->file('imagen')->store('animales', 'public');

        $animal->imagen = $ruta;
        $animal->save();

        // ELIMINAR LA IMAGEN ANTERIOR
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

    // CONSULTAR LOS DATOS CLÍNICOS DE UN ANIMAL
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

    // ACTUALIZAR LOS DATOS CLÍNICOS DE UN ANIMAL
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