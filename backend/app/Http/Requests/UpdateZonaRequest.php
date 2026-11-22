<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZonaRequest extends FormRequest
{
    // La ruta aplica los permisos y aquí se comprueban los datos recibidos.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
         // Al editar se ignora el id actual para poder mantener el mismo nombre.
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('zonas', 'nombre')->ignore($this->route('id')),
            ],
            'descripcion' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la zona es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe otra zona con ese nombre.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.',
        ];
    }
}
