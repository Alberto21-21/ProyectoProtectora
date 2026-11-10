<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;

class AnimalController extends Controller
{
    /**
     * Obtener el listado de todos los animales.
     */
    public function index()
    {
        return response()->json(Animal::all(), 200);
    }

    /**
     * Registrar un nuevo animal.
     */
    public function store(StoreAnimalRequest $request)
    {
        // Obtiene los datos ya validados y autorizados por el Request
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