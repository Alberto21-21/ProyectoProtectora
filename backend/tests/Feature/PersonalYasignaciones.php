<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Empleado;
use App\Models\User;
use App\Models\Veterinario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// T32 (jornada y ausencias del personal) y T33 (veterinarios asignados a animales)
class PersonalYAsignacionesTest extends TestCase
{
    use RefreshDatabase;

    private function crearCuenta(string $rol, string $email): User
    {
        return User::forceCreate([
            'name' => ucfirst($rol),
            'email' => $email,
            'password' => 'Secreta123',
            'rol' => $rol,
            'estado' => 'activo',
        ]);
    }

    private function entrarComo(User $usuario): void
    {
        Sanctum::actingAs($usuario, $usuario->obtenerPermisos());
    }

    private function crearFicha(User $usuario, string $puesto): Empleado
    {
        return Empleado::create([
            'user_id' => $usuario->id,
            'telefono' => null,
            'puesto' => $puesto,
            'estado_laboral' => 'activo',
        ]);
    }

    private function crearAnimal(): Animal
    {
        return Animal::forceCreate([
            'nombre' => 'Toby',
            'especie' => 'Perro',
            'sexo' => 'Macho',
            'estado' => 'Disponible',
        ]);
    }

    private function escenario(): array
    {
        $admin = $this->crearCuenta('admin', 'admin@test.com');
        $personal = $this->crearCuenta('personal', 'personal@test.com');
        $vet = $this->crearCuenta('veterinario', 'vet@test.com');

        $fichaPersonal = $this->crearFicha($personal, 'Cuidador');
        $fichaVet = $this->crearFicha($vet, 'Veterinario');
        Veterinario::create(['empleado_id' => $fichaVet->id, 'especialidad' => 'General']);

        return [$admin, $personal, $vet, $fichaPersonal, $fichaVet, $this->crearAnimal()];
    }

    // ---------- T33 ----------

    public function test_el_admin_asigna_y_retira_un_veterinario(): void
    {
        [$admin, , , , $fichaVet, $animal] = $this->escenario();
        $this->entrarComo($admin);

        $respuesta = $this->postJson('/api/animal-asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaVet->id,
        ]);

        $respuesta->assertStatus(201);
        $this->assertDatabaseHas('animal_asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaVet->id,
            'tipo_asignacion' => 'veterinario',
        ]);

        $id = $respuesta->json('data.id');
        $this->deleteJson("/api/animal-asignaciones/{$id}")->assertStatus(200);
        $this->assertDatabaseMissing('animal_asignaciones', ['id' => $id]);
    }

    public function test_no_se_puede_asignar_dos_veces_ni_a_personal(): void
    {
        [$admin, , , $fichaPersonal, $fichaVet, $animal] = $this->escenario();
        $this->entrarComo($admin);

        $datos = ['animal_id' => $animal->id, 'empleado_id' => $fichaVet->id];
        $this->postJson('/api/animal-asignaciones', $datos)->assertStatus(201);
        $this->postJson('/api/animal-asignaciones', $datos)->assertStatus(422);

        $this->postJson('/api/animal-asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaPersonal->id,
        ])->assertStatus(422);
    }

    public function test_el_personal_no_puede_asignar_ni_retirar_veterinarios(): void
    {
        [$admin, $personal, , , $fichaVet, $animal] = $this->escenario();

        $this->entrarComo($admin);
        $id = $this->postJson('/api/animal-asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaVet->id,
        ])->json('data.id');

        $this->entrarComo($personal);
        $this->postJson('/api/animal-asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaVet->id,
        ])->assertStatus(403);
        $this->deleteJson("/api/animal-asignaciones/{$id}")->assertStatus(403);
        $this->getJson('/api/animal-asignaciones/veterinarios')->assertStatus(403);
    }

    public function test_el_personal_consulta_los_veterinarios_de_un_animal(): void
    {
        [$admin, $personal, , , $fichaVet, $animal] = $this->escenario();

        $this->entrarComo($admin);
        $this->postJson('/api/animal-asignaciones', [
            'animal_id' => $animal->id,
            'empleado_id' => $fichaVet->id,
        ]);

        $this->entrarComo($personal);
        $this->getJson('/api/animal-asignaciones?animal_id=' . $animal->id)
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/animal-asignaciones')->assertStatus(422);
    }

    public function test_el_veterinario_solo_ve_sus_asignaciones(): void
    {
        [$admin, , $vet, , $fichaVet, $animal] = $this->escenario();
        $otro = $this->crearCuenta('veterinario', 'otro@test.com');
        $fichaOtro = $this->crearFicha($otro, 'Veterinario');
        Veterinario::create(['empleado_id' => $fichaOtro->id, 'especialidad' => 'Cirugía']);

        $this->entrarComo($admin);
        foreach ([$fichaVet, $fichaOtro] as $ficha) {
            $this->postJson('/api/animal-asignaciones', [
                'animal_id' => $animal->id,
                'empleado_id' => $ficha->id,
            ])->assertStatus(201);
        }

        $this->entrarComo($vet);
        $this->getJson('/api/animal-asignaciones')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // ---------- T32 ----------

    public function test_la_jornada_semanal_solo_existe_para_personal(): void
    {
        [$admin, $personal, $vet] = $this->escenario();
        $this->entrarComo($admin);

        $this->putJson("/api/empleados/usuario/{$personal->id}/jornada", [
            'jornada' => [
                ['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '14:00'],
                ['dia_semana' => 3, 'hora_inicio' => '16:00', 'hora_fin' => '20:00'],
            ],
        ])->assertStatus(200)->assertJsonCount(2, 'data');

        $this->getJson("/api/empleados/usuario/{$personal->id}/jornada")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->putJson("/api/empleados/usuario/{$personal->id}/jornada", [
            'jornada' => [['dia_semana' => 1, 'hora_inicio' => '14:00', 'hora_fin' => '09:00']],
        ])->assertStatus(422);

        $this->getJson("/api/empleados/usuario/{$vet->id}/jornada")->assertStatus(404);
    }

    public function test_las_ausencias_se_validan_y_ocultan_el_motivo(): void
    {
        [$admin, $personal, $vet, $fichaPersonal, $fichaVet] = $this->escenario();
        $this->entrarComo($admin);

        $this->postJson('/api/ausencias', [
            'empleado_id' => $fichaPersonal->id,
            'fecha_inicio' => '2026-12-01 08:00:00',
            'fecha_fin' => '2026-12-03 20:00:00',
            'motivo' => 'Cita médica',
        ])->assertStatus(201);

        // solapada
        $this->postJson('/api/ausencias', [
            'empleado_id' => $fichaPersonal->id,
            'fecha_inicio' => '2026-12-02 08:00:00',
            'fecha_fin' => '2026-12-04 20:00:00',
        ])->assertStatus(422);

        // los veterinarios no tienen ausencias
        $this->postJson('/api/ausencias', [
            'empleado_id' => $fichaVet->id,
            'fecha_inicio' => '2026-12-10 08:00:00',
            'fecha_fin' => '2026-12-11 20:00:00',
        ])->assertStatus(422);

        $consulta = '/api/ausencias?fecha_inicio=2026-11-30&fecha_fin=2026-12-31';
        $this->getJson($consulta)->assertJsonPath('data.0.motivo', 'Cita médica');

        $this->entrarComo($personal);
        $respuesta = $this->getJson($consulta)->assertStatus(200);
        $this->assertArrayNotHasKey('motivo', $respuesta->json('data.0'));
    }

    public function test_la_disponibilidad_por_fechas_ya_no_existe(): void
    {
        [$admin] = $this->escenario();
        $this->entrarComo($admin);

        $this->getJson('/api/disponibilidades?fecha_inicio=2026-12-01&fecha_fin=2026-12-02')
            ->assertStatus(404);
        $this->postJson('/api/disponibilidades', [])->assertStatus(404);
        $this->getJson('/api/disponibilidades/empleados')->assertStatus(200);
    }
}
