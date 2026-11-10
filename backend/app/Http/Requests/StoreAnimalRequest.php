<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para realizar esta petición (Ability).
     */
    public function authorize(): bool
    {
        // Verifica si el usuario autenticado tiene la habilidad/permiso
        return $this->user() && $this->user()->tokenCan('animales:crear');
    }

    /**
     * Reglas de validación para la petición.
     */
    public function rules(): array
    {
        return [
            'nombre'      => 'required|string|max:255',
            'especie'     => 'required|string|max:100',
            'raza'        => 'nullable|string|max:100',
            'edad'        => 'nullable|integer|min:0',
            'sexo'        => 'required|in:Macho,Hembra',
            'estado'      => 'nullable|in:Disponible,Adoptado,En Tratamiento',
            'descripcion' => 'nullable|string',
            'imagen'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}