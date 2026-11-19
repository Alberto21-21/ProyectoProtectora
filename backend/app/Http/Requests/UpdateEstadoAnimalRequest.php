<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateEstadoAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $usuario = $this->user();

        if (!$usuario || $usuario->estado !== 'activo') {
            return false;
        }

        return $usuario->tokenCan('animales:gestionar');
    }

    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                'string',
                'in:En acogida,En tratamiento,Disponible,Reservado,Adoptado',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debes indicar el nuevo estado del animal.',
            'estado.string'   => 'El estado debe ser un texto válido.',
            'estado.in'       => 'El estado seleccionado no es válido.',
        ];
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'No tienes permisos para actualizar el estado de un animal.',
            ], 403)
        );
    }
}