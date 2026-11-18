<?php

namespace App\Http\Controllers;

use App\Models\Habitaculo;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HabitaculoController extends Controller
{
    // LISTAR LOS HABITÁCULOS (SE PUEDEN FILTRAR POR ZONA Y POR LIMPIEZA)
    public function index(Request $request)
    {
        $request->validate([
            'zona_id' => 'nullable|integer|exists:zonas,id',
            'estado_limpieza' => 'nullable|in:Limpio,Pendiente,En limpieza'
        ], [
            'zona_id.integer' => 'La zona no es válida.',
            'zona_id.exists' => 'La zona no existe.',
            'estado_limpieza.in' => 'El estado de limpieza no es válido.'
        ]);

        $consulta = Habitaculo::with('zona:id,nombre,activo');

        if ($request->zona_id) {
            $consulta->where('zona_id', $request->zona_id);
        }

        if ($request->estado_limpieza) {
            $consulta->where('estado_limpieza', $request->estado_limpieza);
        }

        $habitaculos = $consulta->orderBy('identificador')->get();

        return response()->json([
            'success' => true,
            'message' => 'Habitáculos consultados correctamente.',
            'data' => $habitaculos
        ], 200);
    }

    // CONSULTAR UN HABITÁCULO CON SU ZONA
    public function show($id)
    {
        $habitaculo = Habitaculo::with('zona:id,nombre,activo')->find($id);

        if (is_null($habitaculo)) {
            return response()->json([
                'success' => false,
                'message' => 'Habitáculo no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Habitáculo consultado correctamente.',
            'data' => $habitaculo
        ], 200);
    }

    // DAR DE ALTA UN HABITÁCULO EN UNA ZONA ACTIVA
    public function store(Request $request)
    {
        $request->validate($this->reglas($request, null), $this->mensajes());

        // SI SE DA DE ALTA COMO LIMPIO Y NO HAY FECHA, SE HA LIMPIADO HOY
        $fechaUltimaLimpieza = $request->fecha_ultima_limpieza;

        if ($request->estado_limpieza === 'Limpio' && !$fechaUltimaLimpieza) {
            $fechaUltimaLimpieza = now()->toDateString();
        }

        // CREAR EL HABITÁCULO DESDE LA RELACIÓN DE SU ZONA
        $zona = Zona::find($request->zona_id);

        $habitaculo = $zona->habitaculos()->create([
            'identificador' => $request->identificador,
            'capacidad' => $request->capacidad,
            'estado_limpieza' => $request->estado_limpieza,
            'estado_mantenimiento' => $request->estado_mantenimiento,
            'fecha_ultima_limpieza' => $fechaUltimaLimpieza,
            'fecha_proxima_limpieza' => $request->fecha_proxima_limpieza,
            'observaciones' => $request->observaciones,
            'activo' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Habitáculo creado correctamente.',
            'data' => $habitaculo->load('zona:id,nombre,activo')
        ], 201);
    }

    // EDITAR UN HABITÁCULO
    public function update(Request $request, $id)
    {
        $habitaculo = Habitaculo::find($id);

        if (is_null($habitaculo)) {
            return response()->json([
                'success' => false,
                'message' => 'Habitáculo no encontrado.'
            ], 404);
        }

        $request->validate($this->reglas($request, $habitaculo->id), $this->mensajes());

        // SI PASA A ESTAR LIMPIO Y NO SE INDICA LA FECHA, SE HA LIMPIADO HOY
        $fechaUltimaLimpieza = $request->fecha_ultima_limpieza;

        if ($request->estado_limpieza === 'Limpio' && $habitaculo->estado_limpieza !== 'Limpio' && !$fechaUltimaLimpieza) {
            $fechaUltimaLimpieza = now()->toDateString();
        }

        $habitaculo->zona_id = $request->zona_id;
        $habitaculo->identificador = $request->identificador;
        $habitaculo->capacidad = $request->capacidad;
        $habitaculo->estado_limpieza = $request->estado_limpieza;
        $habitaculo->estado_mantenimiento = $request->estado_mantenimiento;
        $habitaculo->fecha_ultima_limpieza = $fechaUltimaLimpieza;
        $habitaculo->fecha_proxima_limpieza = $request->fecha_proxima_limpieza;
        $habitaculo->observaciones = $request->observaciones;
        $habitaculo->save();

        return response()->json([
            'success' => true,
            'message' => 'Habitáculo actualizado correctamente.',
            'data' => $habitaculo->load('zona:id,nombre,activo')
        ], 200);
    }

    // ACTIVAR O DESACTIVAR UN HABITÁCULO (NO SE BORRA)
    public function toggleActivo($id)
    {
        $habitaculo = Habitaculo::find($id);

        if (is_null($habitaculo)) {
            return response()->json([
                'success' => false,
                'message' => 'Habitáculo no encontrado.'
            ], 404);
        }

         // PARA ACTIVARLO, SU ZONA TIENE QUE ESTAR ACTIVA
        if (!$habitaculo->activo && !$habitaculo->zona->activo) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede activar: la zona "' . $habitaculo->zona->nombre . '" está desactivada.'
            ], 422);
        }

        $habitaculo->activo = !$habitaculo->activo;
        $habitaculo->save();

        if ($habitaculo->activo) {
            $mensaje = 'Habitáculo activado correctamente.';
        } else {
            $mensaje = 'Habitáculo desactivado correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $habitaculo->load('zona:id,nombre,activo')
        ], 200);
    }

    // REGLAS DE VALIDACIÓN DEL ALTA Y DE LA EDICIÓN
    private function reglas(Request $request, $idHabitaculo)
    {
        // EL IDENTIFICADOR NO SE PUEDE REPETIR (AL EDITAR SE IGNORA EL PROPIO)
        $reglaIdentificador = 'required|string|max:50|unique:habitaculos,identificador';

        if ($idHabitaculo) {
            $reglaIdentificador = $reglaIdentificador . ',' . $idHabitaculo;
        }

        // LA PRÓXIMA LIMPIEZA TIENE QUE SER DESPUÉS DE LA ÚLTIMA (SI SE CONOCE)
        $reglaProximaLimpieza = 'nullable|date';

        if ($request->fecha_ultima_limpieza) {
            $reglaProximaLimpieza = $reglaProximaLimpieza . '|after:fecha_ultima_limpieza';
        }

        return [
            'zona_id' => [
                'required',
                'integer',
                Rule::exists('zonas', 'id')->where('activo', true)
            ],
            'identificador' => $reglaIdentificador,
            'capacidad' => 'required|integer|min:1|max:50',
            'estado_limpieza' => 'required|in:Limpio,Pendiente,En limpieza',
            'estado_mantenimiento' => 'required|in:Operativo,En mantenimiento,Averiado',
            'fecha_ultima_limpieza' => 'nullable|date|before_or_equal:today',
            'fecha_proxima_limpieza' => $reglaProximaLimpieza,
            'observaciones' => 'nullable|string|max:1000'
        ];
    }

    // MENSAJES DE ERROR EN ESPAÑOL
    private function mensajes()
    {
        return [
            'zona_id.required' => 'Debes seleccionar una zona.',
            'zona_id.integer' => 'La zona no es válida.',
            'zona_id.exists' => 'La zona no existe o está desactivada.',
            'identificador.required' => 'El identificador es obligatorio.',
            'identificador.string' => 'El identificador debe ser un texto.',
            'identificador.max' => 'El identificador no puede superar los 50 caracteres.',
            'identificador.unique' => 'Ya existe un habitáculo con ese identificador.',
            'capacidad.required' => 'La capacidad es obligatoria.',
            'capacidad.integer' => 'La capacidad debe ser un número entero.',
            'capacidad.min' => 'La capacidad mínima es 1 animal.',
            'capacidad.max' => 'La capacidad máxima es 50 animales.',
            'estado_limpieza.required' => 'El estado de limpieza es obligatorio.',
            'estado_limpieza.in' => 'El estado de limpieza debe ser Limpio, Pendiente o En limpieza.',
            'estado_mantenimiento.required' => 'El estado de mantenimiento es obligatorio.',
            'estado_mantenimiento.in' => 'El estado de mantenimiento debe ser Operativo, En mantenimiento o Averiado.',
            'fecha_ultima_limpieza.date' => 'La fecha de la última limpieza no es válida.',
            'fecha_ultima_limpieza.before_or_equal' => 'La última limpieza no puede ser una fecha futura.',
            'fecha_proxima_limpieza.date' => 'La fecha de la próxima limpieza no es válida.',
            'fecha_proxima_limpieza.after' => 'La próxima limpieza tiene que ser posterior a la última.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.'
        ];
    }
}
