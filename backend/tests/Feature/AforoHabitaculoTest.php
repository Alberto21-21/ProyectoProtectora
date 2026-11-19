<?php

namespace Tests\Feature;

use App\Models\Alojamiento;
use App\Models\Animal;
use App\Models\Habitaculo;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AforoHabitaculoTest extends TestCase
{
    use RefreshDatabase;

    private function crearPersonalAutorizado(): User
    {
        $usuario = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        Sanctum::actingAs($usuario, ['*']);

        return $usuario;
    }

    private function crearHabitaculo(int $capacidad = 1, string $identificador = 'H-01'): Habitaculo
    {
        $zona = Zona::create([
            'nombre' => 'Zona Test ' . $identificador,
            'descripcion' => 'Zona para pruebas de aforo',
            'activo' => true,
        ]);

        return Habitaculo::create([
            'zona_id' => $zona->id,
            'identificador' => $identificador,
            'capacidad' => $capacidad,
            'estado_limpieza' => 'Limpio',
            'estado_mantenimiento' => 'Operativo',
            'activo' => true,
        ]);
    }

    private function crearAnimal(string $nombre, ?int $habitaculoId = null): Animal
    {
        return Animal::create([
            'nombre' => $nombre,
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'tamano' => 'Mediano',
            'fecha_entrada' => now()->toDateString(),
            'motivo' => 'Rescate',
            'estado' => 'Disponible',
            'habitaculo_id' => $habitaculoId,
            'activo' => true,
        ]);
    }

    public function test_impide_superar_aforo_al_alojar_nuevo_animal(): void
    {
        $this->crearPersonalAutorizado();
        $habitaculo = $this->crearHabitaculo(capacidad: 1, identificador: 'H-CAP-1');

        $animal1 = $this->crearAnimal('Toby');
        $animal2 = $this->crearAnimal('Balto');

        $respuesta1 = $this->postJson('/api/habitaculos/alojar', [
            'animal_id' => $animal1->id,
            'habitaculo_destino_id' => $habitaculo->id,
        ]);

        $respuesta1->assertStatus(201)
            ->assertJsonPath('success', true);

        $respuesta2 = $this->postJson('/api/habitaculos/alojar', [
            'animal_id' => $animal2->id,
            'habitaculo_destino_id' => $habitaculo->id,
        ]);

        $respuesta2->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'El habitáculo "H-CAP-1" está completo (capacidad máxima: 1).'
            ]);

        $this->assertEquals(1, Animal::where('habitaculo_id', $habitaculo->id)->count());
        $this->assertNull($animal2->fresh()->habitaculo_id);
        $this->assertEquals(1, Alojamiento::count());
    }

    public function test_impide_superar_aforo_al_trasladar_animal_desde_otro_habitaculo(): void
    {
        $this->crearPersonalAutorizado();
        $origen = $this->crearHabitaculo(capacidad: 2, identificador: 'H-ORIGEN');
        $destinoLleno = $this->crearHabitaculo(capacidad: 1, identificador: 'H-LLENO');

        $ocupante = $this->crearAnimal('Kiara', $destinoLleno->id);
        $animalATrasladar = $this->crearAnimal('Panceta', $origen->id);

        $respuesta = $this->postJson('/api/habitaculos/alojar', [
            'animal_id' => $animalATrasladar->id,
            'habitaculo_destino_id' => $destinoLleno->id,
        ]);

        $respuesta->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'El habitáculo "H-LLENO" está completo (capacidad máxima: 1).'
            ]);

        $this->assertEquals($origen->id, $animalATrasladar->fresh()->habitaculo_id);
        $this->assertEquals($destinoLleno->id, $ocupante->fresh()->habitaculo_id);
        $this->assertEquals(0, Alojamiento::count());
    }

    public function test_dos_asignaciones_consecutivas_sobre_ultima_plaza_no_exceden_el_maximo(): void
    {
        $this->crearPersonalAutorizado();
        $habitaculo = $this->crearHabitaculo(capacidad: 2, identificador: 'H-CONC');

        $this->crearAnimal('Animal 1', $habitaculo->id);
        $candidatoA = $this->crearAnimal('Candidato A');
        $candidatoB = $this->crearAnimal('Candidato B');

        $resA = $this->postJson('/api/habitaculos/alojar', [
            'animal_id' => $candidatoA->id,
            'habitaculo_destino_id' => $habitaculo->id,
        ]);

        $resB = $this->postJson('/api/habitaculos/alojar', [
            'animal_id' => $candidatoB->id,
            'habitaculo_destino_id' => $habitaculo->id,
        ]);

        $resA->assertStatus(201);
        $resB->assertStatus(422);

        $this->assertEquals(2, Animal::where('habitaculo_id', $habitaculo->id)->count());
    }
}