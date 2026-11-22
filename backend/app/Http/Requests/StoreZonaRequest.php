<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZonaRequest extends FormRequest
{
    // Por ahora la API ya controla el acceso antes de llegar a este formulario.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // El nombre se guarda como único para no tener dos zonas iguales.
        return [
            'nombre' => 'required|string|max:100|unique:zonas,nombre',
            'descripcion' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la zona es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una zona con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
        ];
    }
}
