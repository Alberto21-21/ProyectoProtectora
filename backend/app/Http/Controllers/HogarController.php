<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hogar;

class HogarController extends Controller
{
    // CONSULTAR EL HOGAR DEL USUARIO AUTENTICADO
    public function mostrar(Request $request)
    {
        $usuario = $request->user();

        $hogar = $usuario->hogar;

        return response()->json([
            'success' => true,
            'message' => 'Hogar consultado correctamente.',
            'data' => $hogar
        ], 200);
    }

    // GUARDAR O ACTUALIZAR EL HOGAR PROPIO
    public function guardar(Request $request)
    {
        $reglasExterior = 'nullable|numeric|min:0|max:999999.99';

        if ($request->boolean('tiene_jardin') || $request->boolean('tiene_terraza')) {
            $reglasExterior = 'required|numeric|min:0.01|max:999999.99';
        }

        $datos = $request->validate([
            'tipo_vivienda' => 'required|in:Piso,Casa,Otro',
            'superficie' => 'required|numeric|min:0.01|max:999999.99',
            'tiene_jardin' => 'required|boolean',
            'tiene_terraza' => 'required|boolean',
            'superficie_exterior' => $reglasExterior,
            'numero_personas' => 'required|integer|min:1|max:100',
            'hay_ninos' => 'required|boolean',
            'edades_ninos' => 'required_if:hay_ninos,1|nullable|array|min:1|max:100',
            'edades_ninos.*' => 'required|integer|min:0|max:17',
            'hay_otros_animales' => 'required|boolean',
            'otros_animales' => 'required_if:hay_otros_animales,1|nullable|string|max:2000',
            'experiencia' => 'required|string|max:2000',
            'minutos_paseos' => 'sometimes|required|integer|min:0|max:1440',
            'minutos_cuidados' => 'sometimes|required|integer|min:0|max:1440',
            'nivel_actividad' => 'sometimes|required|in:Bajo,Medio,Alto',
            'especie_preferida' => 'nullable|in:Perro,Gato,Otro',
            'tamano_preferido' => 'nullable|in:Pequeño,Mediano,Grande',
            'edad_minima' => 'nullable|integer|min:0|max:100',
            'edad_maxima' => 'nullable|integer|min:0|max:100',
            'acepta_necesidades_especiales' => 'sometimes|required|boolean',
            'cuidados_asumibles' => 'nullable|string|max:2000'
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'required_if' => 'Debes completar :attribute según la opción seleccionada.',
            'boolean' => 'El campo :attribute debe indicar sí o no.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'string' => 'El campo :attribute debe ser un texto.',
            'tipo_vivienda.in' => 'Selecciona Piso, Casa u Otro.',
            'superficie.min' => 'La superficie debe ser mayor que cero.',
            'superficie.max' => 'La superficie supera el máximo permitido.',
            'superficie_exterior.min' => 'La superficie exterior no es válida.',
            'superficie_exterior.max' => 'La superficie exterior supera el máximo permitido.',
            'numero_personas.min' => 'Debe vivir al menos una persona.',
            'numero_personas.max' => 'El número de personas no puede superar 100.',
            'edades_ninos.array' => 'Las edades deben enviarse como una lista.',
            'edades_ninos.min' => 'Debes indicar al menos una edad.',
            'edades_ninos.max' => 'No puedes indicar más de 100 edades.',
            'edades_ninos.*.min' => 'Las edades no pueden ser negativas.',
            'edades_ninos.*.max' => 'Las edades de los niños deben ser menores de 18 años.',
            'otros_animales.max' => 'La descripción no puede superar los 2000 caracteres.',
            'experiencia.max' => 'La experiencia no puede superar los 2000 caracteres.',
            'minutos_paseos.min' => 'El tiempo para paseos no puede ser negativo.',
            'minutos_paseos.max' => 'El tiempo para paseos no puede superar 1440 minutos diarios.',
            'minutos_cuidados.min' => 'El tiempo para cuidados no puede ser negativo.',
            'minutos_cuidados.max' => 'El tiempo para cuidados no puede superar 1440 minutos diarios.',
            'nivel_actividad.in' => 'Selecciona un nivel de actividad bajo, medio o alto.',
            'especie_preferida.in' => 'La especie seleccionada no es válida.',
            'tamano_preferido.in' => 'El tamaño seleccionado no es válido.',
            'edad_minima.min' => 'La edad mínima no puede ser negativa.',
            'edad_minima.max' => 'La edad mínima no puede superar 100 años.',
            'edad_maxima.min' => 'La edad máxima no puede ser negativa.',
            'edad_maxima.max' => 'La edad máxima no puede superar 100 años.',
            'cuidados_asumibles.max' => 'Los cuidados asumibles no pueden superar los 2000 caracteres.'
        ]);

        // LIMPIAR DATOS QUE NO CORRESPONDEN A LAS OPCIONES ELEGIDAS
        if (!$datos['tiene_jardin'] && !$datos['tiene_terraza']) {
            $datos['superficie_exterior'] = null;
        }

        if (!$datos['hay_ninos']) {
            $datos['edades_ninos'] = null;
        }

        if (!$datos['hay_otros_animales']) {
            $datos['otros_animales'] = null;
        }

        // COMPROBAR LA COHERENCIA DE LA UNIDAD FAMILIAR
        if ($datos['hay_ninos']) {
            if (count($datos['edades_ninos']) > $datos['numero_personas']) {
                return response()->json([
                    'success' => false,
                    'message' => 'El número de niños no puede superar el total de personas.'
                ], 422);
            }
        }

        // COMPROBAR EL INTERVALO DE EDAD
        if (isset($datos['edad_minima']) && isset($datos['edad_maxima'])) {
            if ($datos['edad_maxima'] < $datos['edad_minima']) {
                return response()->json([
                    'success' => false,
                    'message' => 'La edad máxima no puede ser menor que la mínima.'
                ], 422);
            }
        }

        $usuario = $request->user();
        $hogar = $usuario->hogar;

        if (!$hogar) {
            $hogar = new Hogar();
        }

        $hogar->fill($datos);

        $usuario->hogar()->save($hogar);

        return response()->json([
            'success' => true,
            'message' => 'Datos del hogar guardados correctamente.',
            'data' => $hogar
        ], 200);
    }

}