<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;
use Illuminate\Http\Request;

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
}