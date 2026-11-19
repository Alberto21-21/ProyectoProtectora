<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use Illuminate\Http\Request;
use Carbon\Carbon;

/*
 * Compatibilidad sobre 100 puntos:
 * especie 15, tamaño 15, edad 10, paseos 30 y cuidados 30.
 * Cada criterio suma sus puntos si se cumple; sin preferencia también suma.
 *
 * Una incompatibilidad o tiempo insuficiente impide recomendar.
 * Si faltan datos o una valoración, la puntuación queda pendiente.
 *
 * No se puntúan vivienda, actividad ni experiencia.
 * El resultado es orientativo y no aprueba la adopción.
 */

class CompatibilidadController extends Controller
{
    // CONSULTAR LA COMPATIBILIDAD DEL USUARIO AUTENTICADO
    public function index(Request $request)
    {
        $hogar = $request->user()->hogar;

        if (!$hogar) {
            return response()->json([
                'success' => false,
                'message' => 'Debes completar los datos de tu hogar.'
            ], 422);
        }

        $animales = Animal::where('activo', true)
            ->where('estado', 'Disponible')
            ->get();

        $resultados = [];

        foreach ($animales as $animal) {
            $convivencia = $this->comprobarConvivencia($animal, $hogar);
            $preferencias = $this->calcularPreferencias($animal, $hogar);
            $tiempos = $this->comprobarTiempos($animal, $hogar);
            $necesidades = $this->comprobarNecesidades($animal, $hogar);

            $incompatibilidades = array_merge(
                $convivencia['incompatibilidades'],
                $tiempos['incompatibilidades'],
                $necesidades['incompatibilidades']
            );

            $pendientes = array_merge(
                $convivencia['pendientes'],
                $preferencias['pendientes'],
                $tiempos['pendientes'],
                $necesidades['pendientes']
            );

            $motivos = array_merge(
                $preferencias['motivos'],
                $tiempos['motivos'],
                $necesidades['motivos']
            );

            $puntuacion = $preferencias['puntos'] + $tiempos['puntos'];
            $estado = 'Evaluado';

            if (count($incompatibilidades) > 0) {
                $estado = 'No recomendado';
                $puntuacion = null;
            } elseif (count($pendientes) > 0) {
                $estado = 'Pendiente de confirmar';
                $puntuacion = null;
            }

                        $fotografiaUrl = null;

            if ($animal->imagen) {
                $fotografiaUrl = $animal->imagen_public_id
                    ? $animal->imagen
                    : asset('storage/' . $animal->imagen);

            } elseif ($animal->fotografia) {
                $fotografiaUrl = $animal->fotografia_public_id
                    ? $animal->fotografia
                    : asset('storage/' . $animal->fotografia);
            }

            $resultados[] = [
                'id' => $animal->id,
                'nombre' => $animal->nombre,
                'fotografia_url' => $fotografiaUrl,
                'estado_compatibilidad' => $estado,
                'puntuacion' => $puntuacion,
                'maximo' => 100,
                'motivos' => $motivos,
                'incompatibilidades' => $incompatibilidades,
                'pendientes' => $pendientes,
            ];
        }

        // ORDENAR DE MAYOR A MENOR PUNTUACIÓN
        $resultados = collect($resultados)
            ->sortByDesc(function ($resultado) {
                return $resultado['puntuacion'] ?? -1;
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Compatibilidad calculada. El resultado es orientativo y no supone la aprobación de la adopción.',
            'data' => $resultados
        ], 200);
    }

    // COMPROBAR LA CONVIVENCIA ANTES DE CALCULAR LOS PUNTOS
    private function comprobarConvivencia(Animal $animal, $hogar)
    {
        $incompatibilidades = [];
        $pendientes = [];

        if ($hogar->hay_ninos) {
            if ($animal->con_ninos === 'No compatible') {
                $incompatibilidades[] =
                    'El animal no es compatible con niños.';
            } elseif ($animal->con_ninos !== 'Compatible') {
                $pendientes[] =
                    'La protectora debe confirmar la convivencia con niños.';
            }
        }

        if ($hogar->hay_otros_animales) {
            if ($animal->con_otros_animales === 'No compatible') {
                $incompatibilidades[] =
                    'El animal no es compatible con otros animales.';
            } elseif ($animal->con_otros_animales !== 'Compatible') {
                $pendientes[] =
                    'La protectora debe confirmar la convivencia con otros animales.';
            }
        }

        return [
            'incompatibilidades' => $incompatibilidades,
            'pendientes' => $pendientes
        ];
    }

        // CALCULAR LOS PUNTOS SEGÚN LAS PREFERENCIAS DEL ADOPTANTE
    private function calcularPreferencias(Animal $animal, $hogar)
    {
        $puntos = 0;
        $motivos = [];
        $pendientes = [];

        // ESPECIE: 15 PUNTOS
        if (!$hogar->especie_preferida) {
            $puntos += 15;
            $motivos[] = 'No tienes preferencia de especie.';
        } elseif (!$animal->especie) {
            $pendientes[] = 'Falta indicar la especie del animal.';
        } elseif ($hogar->especie_preferida === $animal->especie) {
            $puntos += 15;
            $motivos[] = 'La especie coincide con tu preferencia.';
        } else {
            $motivos[] = 'La especie no coincide con tu preferencia.';
        }

        // TAMAÑO: 15 PUNTOS
        if (!$hogar->tamano_preferido) {
            $puntos += 15;
            $motivos[] = 'No tienes preferencia de tamaño.';
        } elseif (!$animal->tamano) {
            $pendientes[] = 'Falta indicar el tamaño del animal.';
        } elseif ($hogar->tamano_preferido === $animal->tamano) {
            $puntos += 15;
            $motivos[] = 'El tamaño coincide con tu preferencia.';
        } else {
            $motivos[] = 'El tamaño no coincide con tu preferencia.';
        }

        // EDAD: 10 PUNTOS
        if ($hogar->edad_minima === null && $hogar->edad_maxima === null) {
            $puntos += 10;
            $motivos[] = 'No tienes preferencia de edad.';
        } elseif (!$animal->fecha_nacimiento) {
            $pendientes[] = 'Falta indicar la fecha de nacimiento del animal.';
        } else {
            $edad = Carbon::parse($animal->fecha_nacimiento)->age;
            $cumpleEdad = true;

            if ($hogar->edad_minima !== null && $edad < $hogar->edad_minima) {
                $cumpleEdad = false;
            }

            if ($hogar->edad_maxima !== null && $edad > $hogar->edad_maxima) {
                $cumpleEdad = false;
            }

            if ($cumpleEdad) {
                $puntos += 10;
                $motivos[] = 'La edad está dentro de tus preferencias.';
            } else {
                $motivos[] = 'La edad está fuera de tus preferencias.';
            }
        }

        return [
            'puntos' => $puntos,
            'motivos' => $motivos,
            'pendientes' => $pendientes
        ];
    }

        // COMPARAR LOS TIEMPOS DIARIOS DE PASEO Y CUIDADOS
    private function comprobarTiempos(Animal $animal, $hogar)
    {
        $puntos = 0;
        $motivos = [];
        $incompatibilidades = [];
        $pendientes = [];

        // PASEOS: 30 PUNTOS
        if ($animal->minutos_paseos === null || $hogar->minutos_paseos === null) {
            $pendientes[] =
                'Falta indicar el tiempo de paseo del animal o del adoptante.';
        } elseif ($hogar->minutos_paseos >= $animal->minutos_paseos) {
            $puntos += 30;
            $motivos[] = 'Puedes dedicar el tiempo de paseo que necesita.';
        } else {
            $incompatibilidades[] =
                'Tu tiempo disponible para paseos es insuficiente.';
        }

        // CUIDADOS: 30 PUNTOS
        if ($animal->minutos_cuidados === null || $hogar->minutos_cuidados === null) {
            $pendientes[] =
                'Falta indicar el tiempo de cuidados del animal o del adoptante.';
        } elseif ($hogar->minutos_cuidados >= $animal->minutos_cuidados) {
            $puntos += 30;
            $motivos[] = 'Puedes dedicar el tiempo de cuidados que necesita.';
        } else {
            $incompatibilidades[] =
                'Tu tiempo disponible para cuidados es insuficiente.';
        }

        return [
            'puntos' => $puntos,
            'motivos' => $motivos,
            'incompatibilidades' => $incompatibilidades,
            'pendientes' => $pendientes
        ];
    }

        // COMPROBAR SI EL ADOPTANTE ACEPTA LOS CUIDADOS ESPECIALES
    private function comprobarNecesidades(Animal $animal, $hogar)
    {
        $motivos = [];
        $incompatibilidades = [];
        $pendientes = [];

        if ($animal->tiene_necesidades_especiales === null) {
            $pendientes[] =
                'La protectora debe valorar si el animal necesita cuidados especiales.';
        } elseif ($animal->tiene_necesidades_especiales === false) {
            $motivos[] =
                'El animal no tiene necesidades especiales registradas.';
        } elseif ($hogar->acepta_necesidades_especiales === null) {
            $pendientes[] =
                'Debes indicar si puedes asumir cuidados especiales.';
        } elseif ($hogar->acepta_necesidades_especiales === false) {
            $incompatibilidades[] =
                'El animal necesita cuidados especiales que has indicado que no puedes asumir.';
        } else {
            $pendientes[] =
                'La protectora debe confirmar que puedes asumir los cuidados específicos de este animal.';
        }

        return [
            'motivos' => $motivos,
            'incompatibilidades' => $incompatibilidades,
            'pendientes' => $pendientes
        ];
    }
}