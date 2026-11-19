<?php

namespace App\Http\Controllers;

use App\Models\Zona;
use Illuminate\Http\Request;

class ZonaController extends Controller
{
    // LISTAR LAS ZONAS CON CUÁNTOS HABITÁCULOS TIENEN Y CUÁNTOS ESTÁN ACTIVOS
    public function index()
    {
        $zonas = Zona::withCount('habitaculos')
            ->orderBy('nombre')
            ->get();

        foreach ($zonas as $zona) {
            $zona->habitaculos_activos_count = $zona->habitaculos()
                ->where('activo', true)
                ->count();
        }

        return response()->json([
            'success' => true,
            'message' => 'Zonas consultadas correctamente.',
            'data' => $zonas
        ], 200);
    }

    // CONSULTAR UNA ZONA CON SUS HABITÁCULOS
    public function show($id)
    {
        $zona = Zona::with('habitaculos')->find($id);

        if (is_null($zona)) {
            return response()->json([
                'success' => false,
                'message' => 'Zona no encontrada.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Zona consultada correctamente.',
            'data' => $zona
        ], 200);
    }

    // DAR DE ALTA UNA ZONA
    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:100|unique:zonas,nombre',
            'descripcion' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El nombre de la zona es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe una zona con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.'
        ];

        $request->validate($rules, $messages);

        $zona = Zona::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'activo' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zona creada correctamente.',
            'data' => $zona
        ], 201);
    }

    // EDITAR EL NOMBRE Y LA DESCRIPCIÓN DE UNA ZONA
    public function update(Request $request, $id)
    {
        $zona = Zona::find($id);

        if (is_null($zona)) {
            return response()->json([
                'success' => false,
                'message' => 'Zona no encontrada.'
            ], 404);
        }

        // EL NOMBRE NO SE PUEDE REPETIR, SALVO EL DE LA PROPIA ZONA
        $rules = [
            'nombre' => 'required|string|max:100|unique:zonas,nombre,' . $zona->id,
            'descripcion' => 'nullable|string|max:255'
        ];

        $messages = [
            'nombre.required' => 'El nombre de la zona es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique' => 'Ya existe otra zona con ese nombre.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.max' => 'La descripción no puede superar los 255 caracteres.'
        ];

        $request->validate($rules, $messages);

        $zona->nombre = $request->nombre;
        $zona->descripcion = $request->descripcion;
        $zona->save();

        return response()->json([
            'success' => true,
            'message' => 'Zona actualizada correctamente.',
            'data' => $zona
        ], 200);
    }

    // ACTIVAR O DESACTIVAR UNA ZONA (NO SE BORRA)
    public function toggleActivo($id)
    {
        $zona = Zona::find($id);

        if (is_null($zona)) {
            return response()->json([
                'success' => false,
                'message' => 'Zona no encontrada.'
            ], 404);
        }

         // NO SE PUEDE DESACTIVAR SI TIENE HABITÁCULOS ACTIVOS
        if ($zona->activo) {
            $habitaculosActivos = $zona->habitaculos()->where('activo', true)->count();

            if ($habitaculosActivos > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar: la zona tiene ' . $habitaculosActivos . ' habitáculos activos.'
                ], 422);
            }
        }

        $zona->activo = !$zona->activo;
        $zona->save();

        if ($zona->activo) {
            $mensaje = 'Zona activada correctamente.';
        } else {
            $mensaje = 'Zona desactivada correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $zona
        ], 200);
    }
}
