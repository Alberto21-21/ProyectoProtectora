<?php

namespace App\Http\Controllers;

use App\Models\Alojamiento;
use App\Models\Animal;
use App\Models\Habitaculo;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $consulta = Habitaculo::with('zona:id,nombre,activo')
            ->withCount(['animales as animales_activos_count' => function ($query) {
                $query->where('activo', true);
            }]);

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

        $fechaUltimaLimpieza = $request->fecha_ultima_limpieza;

        if ($request->estado_limpieza === 'Limpio' && !$fechaUltimaLimpieza) {
            $fechaUltimaLimpieza = now()->toDateString();
        }

        $zona = Zona::find($request->zona_id);

        $habitaculo = $zona->habitaculos()->create([
            'identificador' => $request->identificador,
            'capacidad' => $request->capacidad,
            'estado_limpieza' => $request->estado_limpieza,
            'estado_mantenimiento' => $request->estado_mantenimiento,
            'fecha_ultima_limpieza' => $fechaUltimaLimpieza,
            'fecha_proxima_limpieza' => $request->fecha_proxima_limpieza,
            'observaciones' => $request->observaciones,
            'restricciones' => $request->restricciones,
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

        $alojados = $habitaculo->animales()->where('activo', true)->count();

        if ($request->capacidad < $alojados) {
            return response()->json([
                'success' => false,
                'message' => 'La capacidad no puede ser menor que los ' . $alojados . ' animales alojados.',
                'errors' => [
                    'capacidad' => ['Hay ' . $alojados . ' animales alojados en este habitáculo.']
                ]
            ], 422);
        }

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
        $habitaculo->restricciones = $request->restricciones;
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

        if (!$habitaculo->activo && !$habitaculo->zona->activo) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede activar: la zona "' . $habitaculo->zona->nombre . '" está desactivada.'
            ], 422);
        }

        if ($habitaculo->activo) {
            $alojados = $habitaculo->animales()->where('activo', true)->count();

            if ($alojados > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede desactivar: el habitáculo tiene ' . $alojados . ' animales alojados.'
                ], 422);
            }
        }

        $habitaculo->activo = !$habitaculo->activo;
        $habitaculo->save();

        $mensaje = $habitaculo->activo
            ? 'Habitáculo activado correctamente.'
            : 'Habitáculo desactivado correctamente.';

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $habitaculo->load('zona:id,nombre,activo')
        ], 200);
    }

    // FICHA DEL HABITÁCULO PARA LOS TRABAJADORES (T24)
    public function ficha($id)
    {
        $habitaculo = Habitaculo::with('zona:id,nombre,activo')->find($id);

        if (is_null($habitaculo)) {
            return response()->json([
                'success' => false,
                'message' => 'Habitáculo no encontrado.'
            ], 404);
        }

        $animales = $habitaculo->animales()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'especie', 'sexo', 'tamano', 'estado']);

        $ocupadas = count($animales);
        $libres = max(0, $habitaculo->capacidad - $ocupadas);

        $restricciones = [];

        if ($habitaculo->restricciones) {
            $restricciones[] = $habitaculo->restricciones;
        }

        if (!$habitaculo->activo) {
            $restricciones[] = 'Habitáculo desactivado: no admite animales.';
        }

        if ($habitaculo->estado_mantenimiento !== 'Operativo') {
            $restricciones[] = 'Estado "' . $habitaculo->estado_mantenimiento . '": no se deben alojar animales nuevos.';
        }

        if ($libres === 0) {
            $restricciones[] = 'Completo: no quedan plazas libres.';
        }

        $actuaciones = [];
        $hoy = now()->toDateString();

        if ($habitaculo->estado_limpieza === 'Pendiente') {
            $actuaciones[] = [
                'tipo' => 'Limpieza',
                'fecha' => null,
                'descripcion' => 'Limpieza pendiente de realizar.'
            ];
        }

        if ($habitaculo->fecha_proxima_limpieza) {
            $fechaProxima = $habitaculo->fecha_proxima_limpieza->toDateString();
            $descripcion = $fechaProxima < $hoy
                ? 'Limpieza programada con fecha vencida.'
                : 'Limpieza programada.';

            $actuaciones[] = [
                'tipo' => 'Limpieza',
                'fecha' => $fechaProxima,
                'descripcion' => $descripcion
            ];
        }

        if ($habitaculo->estado_mantenimiento !== 'Operativo') {
            $actuaciones[] = [
                'tipo' => 'Mantenimiento',
                'fecha' => null,
                'descripcion' => 'Revisar el habitáculo (' . $habitaculo->estado_mantenimiento . ').'
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Ficha del habitáculo consultada correctamente.',
            'data' => [
                'habitaculo' => $habitaculo,
                'plazas' => [
                    'capacidad' => $habitaculo->capacidad,
                    'ocupadas' => $ocupadas,
                    'libres' => $libres
                ],
                'animales' => $animales,
                'restricciones' => $restricciones,
                'actuaciones' => $actuaciones
            ]
        ], 200);
    }

    // LISTAR ANIMALES ACTIVOS PARA ALOJAR O TRASLADAR (T25)
    public function animalesParaAlojar()
    {
        $animales = Animal::with('habitaculo:id,identificador')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'especie', 'estado', 'habitaculo_id']);

        return response()->json([
            'success' => true,
            'message' => 'Animales consultados correctamente.',
            'data' => $animales
        ], 200);
    }

    // ALOJAR O TRASLADAR UN ANIMAL A UN HABITÁCULO (T25 / T27)
    public function alojarAnimal(Request $request)
    {
        $request->validate([
            'animal_id' => 'required|integer|exists:animales,id',
            'habitaculo_destino_id' => 'required|integer|exists:habitaculos,id',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'animal_id.required' => 'Debes seleccionar un animal.',
            'animal_id.integer' => 'El animal seleccionado no es válido.',
            'animal_id.exists' => 'El animal seleccionado no existe.',
            'habitaculo_destino_id.required' => 'Debes seleccionar un habitáculo de destino.',
            'habitaculo_destino_id.integer' => 'El habitáculo de destino no es válido.',
            'habitaculo_destino_id.exists' => 'El habitáculo de destino no existe.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
        ]);

        $animal = Animal::find($request->animal_id);

        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'Rechazado por disponibilidad: no se puede alojar un animal dado de baja o inactivo.',
                'motivos' => ['Disponibilidad: el animal seleccionado no está activo.']
            ], 422);
        }

        $habitaculoDestino = Habitaculo::with([
            'zona:id,nombre,activo',
            'animales' => function ($query) {
                $query->where('activo', true)
                    ->select('id', 'nombre', 'especie', 'estado', 'con_otros_animales', 'tiene_necesidades_especiales', 'habitaculo_id');
            }
        ])->find($request->habitaculo_destino_id);

        $normalizar = function ($valor) {
            $texto = mb_strtolower(trim((string) $valor));
            return strtr($texto, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'ü' => 'u', 'ñ' => 'n'
            ]);
        };

        $motivosRechazo = [];

        if (!$habitaculoDestino->activo) {
            $motivosRechazo[] = 'Disponibilidad: el habitáculo "' . $habitaculoDestino->identificador . '" está desactivado.';
        }

        if ($habitaculoDestino->zona && !$habitaculoDestino->zona->activo) {
            $motivosRechazo[] = 'Disponibilidad: la zona "' . $habitaculoDestino->zona->nombre . '" está desactivada.';
        }

        if ($habitaculoDestino->estado_mantenimiento !== 'Operativo') {
            $motivosRechazo[] = 'Disponibilidad: el habitáculo "' . $habitaculoDestino->identificador . '" está en estado "' . $habitaculoDestino->estado_mantenimiento . '" y no admite animales.';
        }

        if ((int) $animal->habitaculo_id === (int) $habitaculoDestino->id) {
            $motivosRechazo[] = 'Disponibilidad: el animal ya se encuentra alojado en el habitáculo "' . $habitaculoDestino->identificador . '".';
        }

        $animalesAlojados = $habitaculoDestino->animales;

        if ($animalesAlojados->count() >= (int) $habitaculoDestino->capacidad) {
            $motivosRechazo[] = 'Disponibilidad: el habitáculo "' . $habitaculoDestino->identificador . '" está completo (capacidad máxima: ' . $habitaculoDestino->capacidad . ').';
        }

        $textoRestricciones = $normalizar($habitaculoDestino->restricciones);
        $textoZona = $normalizar($habitaculoDestino->zona->nombre ?? '');
        $especieAnimal = $normalizar($animal->especie);

        if (str_contains($textoRestricciones, 'solo perros') || str_contains($textoZona, 'perrera')) {
            if (!in_array($especieAnimal, ['perro', 'perros'], true)) {
                $motivosRechazo[] = 'Especie: el habitáculo "' . $habitaculoDestino->identificador . '" es exclusivo para perros.';
            }
        }

        if (str_contains($textoRestricciones, 'solo gatos') || str_contains($textoZona, 'gatera')) {
            if (!in_array($especieAnimal, ['gato', 'gatos'], true)) {
                $motivosRechazo[] = 'Especie: el habitáculo "' . $habitaculoDestino->identificador . '" es exclusivo para gatos.';
            }
        }

        foreach ($animalesAlojados as $companero) {
            if ($normalizar($companero->especie) !== $especieAnimal) {
                $motivosRechazo[] = 'Especie: no se puede mezclar "' . $animal->especie . '" con "' . $companero->especie . '" (' . $companero->nombre . ') en el mismo habitáculo.';
                break;
            }
        }

        if ($animalesAlojados->count() > 0) {
            if ($animal->con_otros_animales === 'No compatible') {
                $motivosRechazo[] = 'Convivencia: "' . $animal->nombre . '" no es compatible con otros animales y el habitáculo ya tiene ocupantes.';
            }

            foreach ($animalesAlojados as $companero) {
                if ($companero->con_otros_animales === 'No compatible') {
                    $motivosRechazo[] = 'Convivencia: "' . $companero->nombre . '" ya alojado no es compatible con otros animales.';
                }
            }
        }

        $esHabitaculoAislamiento = str_contains($textoRestricciones, 'aislamiento')
            || str_contains($textoRestricciones, 'cuarentena')
            || str_contains($textoZona, 'aislamiento')
            || str_contains($textoZona, 'cuarentena');

        $animalRequiereAislamiento = $animal->estado === 'En tratamiento'
            || (bool) $animal->tiene_necesidades_especiales;

        if ($esHabitaculoAislamiento && !$animalRequiereAislamiento) {
            $motivosRechazo[] = 'Aislamiento: el habitáculo "' . $habitaculoDestino->identificador . '" está reservado para aislamiento/cuarentena y el animal no lo requiere.';
        }

        if ($esHabitaculoAislamiento && $animalesAlojados->count() > 0) {
            $motivosRechazo[] = 'Aislamiento: un habitáculo de aislamiento/cuarentena no debe compartirse con otros animales.';
        }

        if ($animalRequiereAislamiento && $animalesAlojados->count() > 0) {
            $motivosRechazo[] = 'Aislamiento: "' . $animal->nombre . '" requiere aislamiento/atención especial y el habitáculo ya tiene animales alojados.';
        }

        if (count($motivosRechazo) > 0) {
            return response()->json([
                'success' => false,
                'message' => implode(' ', $motivosRechazo),
                'motivos' => $motivosRechazo
            ], 422);
        }

        $resultado = DB::transaction(function () use ($request, $animal, $habitaculoDestino) {
            $destinoBloqueado = Habitaculo::where('id', $habitaculoDestino->id)->lockForUpdate()->first();
            $ocupadas = $destinoBloqueado->animales()->where('activo', true)->count();

            if ($ocupadas >= $destinoBloqueado->capacidad) {
                return null;
            }

            $origenId = $animal->habitaculo_id;

            $movimiento = Alojamiento::create([
                'animal_id' => $animal->id,
                'habitaculo_origen_id' => $origenId,
                'habitaculo_destino_id' => $destinoBloqueado->id,
                'user_id' => $request->user()->id,
                'fecha_movimiento' => now(),
                'observaciones' => $request->observaciones,
            ]);

            $animal->habitaculo_id = $destinoBloqueado->id;
            $animal->save();

            return [
                'movimiento' => $movimiento->load([
                    'animal:id,nombre,especie',
                    'habitaculoOrigen:id,identificador',
                    'habitaculoDestino:id,identificador',
                    'responsable:id,name'
                ]),
                'es_traslado' => !is_null($origenId),
            ];
        });

        if (is_null($resultado)) {
            $motivoCapacidad = 'Disponibilidad: el habitáculo "' . $habitaculoDestino->identificador . '" está completo (capacidad máxima: ' . $habitaculoDestino->capacidad . ').';

            return response()->json([
                'success' => false,
                'message' => $motivoCapacidad,
                'motivos' => [$motivoCapacidad]
            ], 422);
        }

        $mensaje = $resultado['es_traslado']
            ? 'Animal trasladado correctamente al habitáculo ' . $habitaculoDestino->identificador . '.'
            : 'Animal alojado correctamente en el habitáculo ' . $habitaculoDestino->identificador . '.';

        return response()->json([
            'success' => true,
            'message' => $mensaje,
            'data' => $resultado['movimiento']
        ], 201);
    }

    // CONSULTAR HISTORIAL DE ALOJAMIENTOS Y TRASLADOS
    public function historialAlojamientos(Request $request)
    {
        $consulta = Alojamiento::with([
            'animal:id,nombre,especie',
            'habitaculoOrigen:id,identificador',
            'habitaculoDestino:id,identificador',
            'responsable:id,name'
        ])->orderByDesc('fecha_movimiento');

        if ($request->filled('animal_id')) {
            $consulta->where('animal_id', $request->animal_id);
        }

        if ($request->filled('habitaculo_id')) {
            $consulta->where(function ($q) use ($request) {
                $q->where('habitaculo_origen_id', $request->habitaculo_id)
                  ->orWhere('habitaculo_destino_id', $request->habitaculo_id);
            });
        }

        return response()->json([
            'success' => true,
            'message' => 'Historial de alojamientos consultado correctamente.',
            'data' => $consulta->get()
        ], 200);
    }

    private function reglas(Request $request, $idHabitaculo)
    {
        $reglaIdentificador = 'required|string|max:50|unique:habitaculos,identificador';

        if ($idHabitaculo) {
            $reglaIdentificador = $reglaIdentificador . ',' . $idHabitaculo;
        }

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
            'observaciones' => 'nullable|string|max:1000',
            'restricciones' => 'nullable|string|max:255'
        ];
    }

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
            'observaciones.max' => 'Las observaciones no pueden superar los 1000 caracteres.',
            'restricciones.string' => 'Las restricciones deben ser un texto.',
            'restricciones.max' => 'Las restricciones no pueden superar los 255 caracteres.'
        ];
    }
}