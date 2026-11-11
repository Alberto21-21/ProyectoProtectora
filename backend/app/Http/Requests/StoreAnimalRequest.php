<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreAnimalRequest extends FormRequest
{
    // SOLO EL PERSONAL AUTORIZADO PUEDE DAR DE ALTA ANIMALES
    public function authorize(): bool
    {
        $usuario = $this->user();

        if (!$usuario) {
            return false;
        }

        return in_array($usuario->rol, ['admin', 'personal', 'veterinario']);
    }

    // REGLAS DE VALIDACION
    public function rules(): array
    {
        $reglasFechaEntrada = 'required|date|before_or_equal:today';

        // LA ENTRADA NO PUEDE SER ANTERIOR AL NACIMIENTO (SI SE CONOCE)
        if ($this->filled('fecha_nacimiento')) {
            $reglasFechaEntrada = $reglasFechaEntrada . '|after_or_equal:fecha_nacimiento';
        }

        return [
            'nombre' => 'required|string|max:255',
            'fotografia' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'especie' => 'required|in:Perro,Gato,Otro',
            'raza' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'sexo' => 'required|in:Macho,Hembra',
            'tamano' => 'required|in:Pequeño,Mediano,Grande',
            'peso' => 'nullable|numeric|min:0|max:999',
            'fecha_entrada' => $reglasFechaEntrada,
            'motivo' => 'required|string|max:255',
            'estado' => 'required|in:En acogida,En tratamiento,Disponible,Reservado,Adoptado',
            'caracter' => 'nullable|string|max:1000',
            'con_ninos' => 'required|in:Compatible,No compatible,Desconocido',
            'con_otros_animales' => 'required|in:Compatible,No compatible,Desconocido',
            'con_desconocidos' => 'required|in:Compatible,No compatible,Desconocido',
            'necesidades_especiales' => 'nullable|string|max:1000',
            'observaciones' => 'nullable|string|max:1000'
        ];
    }

    // MENSAJES DE ERROR EN ESPAÑOL
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'fotografia.image' => 'El archivo debe ser una imagen.',
            'fotografia.mimes' => 'La fotografía debe ser JPG, PNG o WEBP.',
            'fotografia.max' => 'La fotografía no puede superar los 2 MB.',
            'especie.required' => 'La especie es obligatoria.',
            'especie.in' => 'La especie seleccionada no es válida.',
            'raza.string' => 'La raza debe ser un texto.',
            'raza.max' => 'La raza no puede superar los 255 caracteres.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser posterior a hoy.',
            'sexo.required' => 'El sexo es obligatorio.',
            'sexo.in' => 'El sexo seleccionado no es válido.',
            'tamano.required' => 'El tamaño es obligatorio.',
            'tamano.in' => 'El tamaño seleccionado no es válido.',
            'peso.numeric' => 'El peso debe ser un número.',
            'peso.min' => 'El peso no puede ser negativo.',
            'peso.max' => 'El peso no puede superar los 999 kg.',
            'fecha_entrada.required' => 'La fecha de entrada es obligatoria.',
            'fecha_entrada.date' => 'La fecha de entrada debe ser una fecha válida.',
            'fecha_entrada.before_or_equal' => 'La fecha de entrada no puede ser posterior a hoy.',
            'fecha_entrada.after_or_equal' => 'La fecha de entrada no puede ser anterior al nacimiento.',
            'motivo.required' => 'El motivo de entrada es obligatorio.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado seleccionado no es válido.',
            'caracter.max' => 'El carácter no puede superar los 1000 caracteres.',
            'con_ninos.required' => 'Indica la compatibilidad con niños.',
            'con_ninos.in' => 'La compatibilidad con niños no es válida.',
            'con_otros_animales.required' => 'Indica la compatibilidad con otros animales.',
            'con_otros_animales.in' => 'La compatibilidad con otros animales no es válida.',
            'con_desconocidos.required' => 'Indica la compatibilidad con desconocidos.',
            'con_desconocidos.in' => 'La compatibilidad con desconocidos no es válida.',
            'necesidades_especiales.max' => 'Las necesidades especiales no pueden superar los 1000 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.'
        ];
    }

    // RESPUESTA CUANDO EL USUARIO NO TIENE PERMISOS
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'No tienes permisos para dar de alta animales.'
            ], 403)
        );
    }
}