<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Models\Animal;

class AnimalController extends Controller
{
    // LISTAR TODOS LOS ANIMALES
    public function index()
    {
        return response()->json(Animal::all(), 200);
    }

    // DAR DE ALTA UN ANIMAL
    public function store(StoreAnimalRequest $request)
    {
        // DATOS YA VALIDADOS POR EL REQUEST
        $datos = $request->validated();

        // GUARDAR LA FOTOGRAFIA SI SE HA ENVIADO
        if ($request->hasFile('fotografia')) {
            $datos['fotografia'] = $request->file('fotografia')->store('animales', 'public');
        }

        // GUARDAR QUIEN DA DE ALTA AL ANIMAL
        $datos['user_id'] = $request->user()->id;

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
}