<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Animal;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompatibilidadTest extends TestCase
{
    use RefreshDatabase;

    // COINCIDEN LAS PREFERENCIAS Y EL TIEMPO ES SUFICIENTE
    public function test_la_compatibilidad_es_de_100_puntos(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $usuario->hogar()->create([
            'tipo_vivienda' => 'Casa',
            'superficie' => 100,
            'tiene_jardin' => true,
            'tiene_terraza' => false,
            'superficie_exterior' => 30,
            'numero_personas' => 2,
            'hay_ninos' => false,
            'hay_otros_animales' => false,
            'experiencia' => 'He cuidado perros.',
            'minutos_paseos' => 90,
            'minutos_cuidados' => 60,
            'nivel_actividad' => 'Medio',
            'especie_preferida' => 'Perro',
            'tamano_preferido' => 'Mediano',
            'edad_minima' => 2,
            'edad_maxima' => 8,
            'acepta_necesidades_especiales' => false
        ]);

        $animal = Animal::create([
            'nombre' => 'Balto',
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
            'estado' => 'Disponible',
            'activo' => true,
            'con_ninos' => 'Compatible',
            'con_otros_animales' => 'Compatible',
            'minutos_paseos' => 60,
            'minutos_cuidados' => 30,
            'tiene_necesidades_especiales' => false
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/compatibilidad');

        $respuesta->assertStatus(200);

        $respuesta->assertJson([
            'success' => true,
            'data' => [
                'recomendados' => [
                    [
                        'id' => $animal->id,
                        'estado_compatibilidad' => 'Evaluado',
                        'puntuacion' => 100,
                        'maximo' => 100,
                        'incompatibilidades' => [],
                        'pendientes' => []
                    ]
                ],
                'pendientes' => [],
                'excluidos' => []
            ]
        ]);
    }

        public function test_una_especie_distinta_obtiene_85_puntos(): void
        {

        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $usuario->hogar()->create([
            'tipo_vivienda' => 'Casa',
            'superficie' => 100,
            'tiene_jardin' => true,
            'tiene_terraza' => false,
            'superficie_exterior' => 30,
            'numero_personas' => 2,
            'hay_ninos' => false,
            'hay_otros_animales' => false,
            'experiencia' => 'He cuidado perros.',
            'minutos_paseos' => 90,
            'minutos_cuidados' => 60,
            'nivel_actividad' => 'Medio',
            'especie_preferida' => 'Perro',
            'tamano_preferido' => 'Mediano',
            'edad_minima' => 2,
            'edad_maxima' => 8,
            'acepta_necesidades_especiales' => false
        ]);

        $animal = Animal::create([
            'nombre' => 'Balto',
            'especie' => 'Gato',            
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
            'estado' => 'Disponible',
            'activo' => true,
            'con_ninos' => 'Compatible',
            'con_otros_animales' => 'Compatible',
            'minutos_paseos' => 60,
            'minutos_cuidados' => 30,
            'tiene_necesidades_especiales' => false
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/compatibilidad');

        $respuesta->assertStatus(200);

        $respuesta->assertJson([
            'success' => true,
            'data' => [
                'recomendados' => [
                    [
                        'id' => $animal->id,
                        'estado_compatibilidad' => 'Evaluado',
                        'puntuacion' => 85,
                        'maximo' => 100,
                        'incompatibilidades' => [],
                        'pendientes' => []
                    ]
                ],
                'pendientes' => [],
                'excluidos' => []
            ]
        ]);
    }

    public function test_un_animal_incompatible_con_ninos_no_se_recomienda(): void 
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $usuario->hogar()->create([
            'tipo_vivienda' => 'Casa',
            'superficie' => 100,
            'tiene_jardin' => true,
            'tiene_terraza' => false,
            'superficie_exterior' => 30,
            'numero_personas' => 2,
            'hay_ninos' => true,
            'edad_ninos' => [8],
            'hay_otros_animales' => false,
            'experiencia' => 'He cuidado perros.',
            'minutos_paseos' => 90,
            'minutos_cuidados' => 60,
            'nivel_actividad' => 'Medio',
            'especie_preferida' => 'Perro',
            'tamano_preferido' => 'Mediano',
            'edad_minima' => 2,
            'edad_maxima' => 8,
            'acepta_necesidades_especiales' => false
        ]);

        $animal = Animal::create([
            'nombre' => 'Balto',
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
            'estado' => 'Disponible',
            'activo' => true,
            'con_ninos' => 'No compatible',
            'con_otros_animales' => 'Compatible',
            'minutos_paseos' => 60,
            'minutos_cuidados' => 30,
            'tiene_necesidades_especiales' => false
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/compatibilidad');

        $respuesta->assertJson([
            'success' => true,
            'data' => [
                'pendientes' => [],
                'excluidos' => [
                    [
                        'id' => $animal->id,
                        'estado_compatibilidad' => 'No recomendado',
                        'puntuacion' => null,
                        'incompatibilidades' => [
                            'El animal no es compatible con niños.'
                        ]
                    ]
                ]
            ]
        ]);

        $respuesta->assertJsonCount(0, 'data.recomendados');
    }

    public function test_no_se_recomienda_si_el_tiempo_de_paseo_es_insuficiente(): void    
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $usuario->hogar()->create([
            'tipo_vivienda' => 'Casa',
            'superficie' => 100,
            'tiene_jardin' => true,
            'tiene_terraza' => false,
            'superficie_exterior' => 30,
            'numero_personas' => 2,
            'hay_ninos' => false,
            'hay_otros_animales' => false,
            'experiencia' => 'He cuidado perros.',
            'minutos_paseos' => 30,
            'minutos_cuidados' => 60,
            'nivel_actividad' => 'Medio',
            'especie_preferida' => 'Perro',
            'tamano_preferido' => 'Mediano',
            'edad_minima' => 2,
            'edad_maxima' => 8,
            'acepta_necesidades_especiales' => false
        ]);

        $animal = Animal::create([
            'nombre' => 'Balto',
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
            'estado' => 'Disponible',
            'activo' => true,
            'con_ninos' => 'Compatible',
            'con_otros_animales' => 'Compatible',
            'minutos_paseos' => 60,
            'minutos_cuidados' => 30,
            'tiene_necesidades_especiales' => false
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/compatibilidad');

        $respuesta->assertStatus(200);

        $respuesta->assertJson([
            'success' => true,
            'data' => [
                'pendientes' => [],
                'excluidos' => [
                    [
                        'id' => $animal->id,
                        'estado_compatibilidad' => 'No recomendado',
                        'puntuacion' => null,
                        'incompatibilidades' => [
                            'Tu tiempo disponible para paseos es insuficiente.'
                        ]
                    ]
                ]
            ]
        ]);

        $respuesta->assertJsonCount(0, 'data.recomendados');
    }

    public function test_sin_tiempo_de_paseo_conocido_queda_pendiente(): void    
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $usuario->hogar()->create([
            'tipo_vivienda' => 'Casa',
            'superficie' => 100,
            'tiene_jardin' => true,
            'tiene_terraza' => false,
            'superficie_exterior' => 30,
            'numero_personas' => 2,
            'hay_ninos' => false,
            'hay_otros_animales' => false,
            'experiencia' => 'He cuidado perros.',
            'minutos_paseos' => null,
            'minutos_cuidados' => 60,
            'nivel_actividad' => 'Medio',
            'especie_preferida' => 'Perro',
            'tamano_preferido' => 'Mediano',
            'edad_minima' => 2,
            'edad_maxima' => 8,
            'acepta_necesidades_especiales' => false
        ]);

        $animal = Animal::create([
            'nombre' => 'Balto',
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_nacimiento' => now()->subYears(5)->toDateString(),
            'estado' => 'Disponible',
            'activo' => true,
            'con_ninos' => 'Compatible',
            'con_otros_animales' => 'Compatible',
            'minutos_paseos' => 60,
            'minutos_cuidados' => 30,
            'tiene_necesidades_especiales' => false
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/compatibilidad');

        $respuesta->assertStatus(200);

        $respuesta->assertJson([
            'success' => true,
            'data' => [
                'pendientes' => [
                    [
                        'id' => $animal->id,
                        'estado_compatibilidad' => 'Pendiente de confirmar',
                        'puntuacion' => null,
                        'pendientes' => [
                            'Falta indicar el tiempo de paseo del animal o del adoptante.'
                        ]
                    ]
                ],
                'excluidos' => []
            ]
        ]);

        $respuesta->assertJsonCount(0, 'data.recomendados');
    }

}