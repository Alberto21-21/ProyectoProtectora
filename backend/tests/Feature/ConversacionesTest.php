<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Conversacion;
use App\Models\Empleado;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// T65: conversaciones y mensajes entre personal y veterinarios
class ConversacionesTest extends TestCase
{
    use RefreshDatabase;

    private function crearCuenta(string $rol, string $email, string $estado = 'activo'): User
    {
        return User::forceCreate([
            'name' => ucfirst($rol) . ' ' . $email,
            'email' => $email,
            'password' => 'Secreta123',
            'rol' => $rol,
            'estado' => $estado,
        ]);
    }

    private function crearTrabajador(string $rol, string $email, string $estadoFicha = 'activo'): User
    {
        $usuario = $this->crearCuenta($rol, $email);

        Empleado::create([
            'user_id' => $usuario->id,
            'telefono' => null,
            'puesto' => $rol === 'veterinario' ? 'Veterinario' : 'Cuidador',
            'estado_laboral' => $estadoFicha,
        ]);

        return $usuario;
    }

    private function entrarComo(User $usuario): void
    {
        Sanctum::actingAs($usuario, $usuario->obtenerPermisos());
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

    private function iniciarConversacion(User $creador, array $participantes, array $extra = []): int
    {
        $this->entrarComo($creador);

        return $this->postJson('/api/conversaciones', array_merge([
            'participantes' => $participantes,
        ], $extra))->assertStatus(201)->json('data.id');
    }

    // ---------- creación ----------

    public function test_crear_conversacion_con_animal(): void
    {
        $personal = $this->crearTrabajador('personal', 'p@test.com');
        $vet = $this->crearTrabajador('veterinario', 'v@test.com');
        $animal = $this->crearAnimal();

        $this->entrarComo($personal);
        $respuesta = $this->postJson('/api/conversaciones', [
            'asunto' => 'Cojera de Toby',
            'animal_id' => $animal->id,
            'participantes' => [$vet->id],
            'mensaje' => 'Hola, Toby cojea desde ayer.',
        ])->assertStatus(201);

        $respuesta->assertJsonPath('data.tipo', 'conversacion')
            ->assertJsonPath('data.animal.nombre', 'Toby')
            ->assertJsonPath('data.ultimo_mensaje.texto', 'Hola, Toby cojea desde ayer.')
            ->assertJsonPath('data.ultimo_mensaje.emisor.id', $personal->id)
            ->assertJsonCount(2, 'data.participantes');

        $this->assertDatabaseHas('conversacion_participantes', ['user_id' => $personal->id]);
        $this->assertDatabaseHas('conversacion_participantes', ['user_id' => $vet->id]);
        $this->assertDatabaseCount('mensajes', 1);
    }

    public function test_crear_conversacion_entre_personal(): void
    {
        $uno = $this->crearTrabajador('personal', 'uno@test.com');
        $dos = $this->crearTrabajador('personal', 'dos@test.com');

        $this->entrarComo($uno);
        $this->postJson('/api/conversaciones', ['participantes' => [$dos->id]])
            ->assertStatus(201)
            ->assertJsonPath('data.animal', null);
    }

    public function test_solo_se_invita_a_cuentas_validas(): void
    {
        $personal = $this->crearTrabajador('personal', 'p@test.com');
        $admin = $this->crearCuenta('admin', 'admin@test.com');
        $adoptante = $this->crearCuenta('adoptante', 'ado@test.com');
        $sinFicha = $this->crearCuenta('personal', 'sinficha@test.com');
        $fichaInactiva = $this->crearTrabajador('personal', 'inactiva@test.com', 'inactivo');
        $cuentaInactiva = $this->crearTrabajador('veterinario', 'baja@test.com');
        $cuentaInactiva->forceFill(['estado' => 'inactivo'])->save();

        $this->entrarComo($personal);

        foreach ([$admin, $adoptante, $sinFicha, $fichaInactiva, $cuentaInactiva] as $invalido) {
            $this->postJson('/api/conversaciones', ['participantes' => [$invalido->id]])
                ->assertStatus(422);
        }

        $this->assertDatabaseCount('conversaciones', 0);
    }

    public function test_validaciones_al_crear(): void
    {
        $personal = $this->crearTrabajador('personal', 'p@test.com');
        $otro = $this->crearTrabajador('personal', 'o@test.com');
        $this->entrarComo($personal);

        $this->postJson('/api/conversaciones', [])->assertStatus(422);
        $this->postJson('/api/conversaciones', ['participantes' => []])->assertStatus(422);
        $this->postJson('/api/conversaciones', ['participantes' => [$personal->id]])->assertStatus(422);
        $this->postJson('/api/conversaciones', ['participantes' => [$otro->id, $otro->id]])->assertStatus(422);
        $this->postJson('/api/conversaciones', ['participantes' => [9999]])->assertStatus(422);
        $this->postJson('/api/conversaciones', [
            'participantes' => [$otro->id],
            'animal_id' => 9999,
        ])->assertStatus(422);
        $this->postJson('/api/conversaciones', [
            'participantes' => [$otro->id],
            'mensaje' => str_repeat('a', 2001),
        ])->assertStatus(422);
    }

    public function test_creador_sin_ficha_no_puede_usar_el_chat(): void
    {
        $sinFicha = $this->crearCuenta('personal', 'sinficha@test.com');
        $otro = $this->crearTrabajador('personal', 'o@test.com');

        $this->entrarComo($sinFicha);
        $this->postJson('/api/conversaciones', ['participantes' => [$otro->id]])->assertStatus(403);
        $this->getJson('/api/conversaciones')->assertStatus(403);
    }

    public function test_admin_y_adoptante_sin_acceso(): void
    {
        $admin = $this->crearCuenta('admin', 'admin@test.com');
        $adoptante = $this->crearCuenta('adoptante', 'ado@test.com');
        $otro = $this->crearTrabajador('personal', 'o@test.com');

        foreach ([$admin, $adoptante] as $cuenta) {
            $this->entrarComo($cuenta);
            $this->getJson('/api/conversaciones')->assertStatus(403);
            $this->postJson('/api/conversaciones', ['participantes' => [$otro->id]])->assertStatus(403);
            $this->getJson('/api/conversaciones/contactos')->assertStatus(403);
        }
    }

    public function test_sin_sesion_da_401(): void
    {
        $this->getJson('/api/conversaciones')->assertStatus(401);
        $this->postJson('/api/conversaciones/1/mensajes', ['texto' => 'hola'])->assertStatus(401);
    }

    // ---------- lista, contactos y detalle ----------

    public function test_lista_solo_las_propias(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $c = $this->crearTrabajador('personal', 'c@test.com');

        $propia = $this->iniciarConversacion($a, [$b->id], ['asunto' => 'Propia']);
        $ajena = $this->iniciarConversacion($b, [$c->id], ['asunto' => 'Ajena']);

        $this->entrarComo($a);
        $respuesta = $this->getJson('/api/conversaciones')->assertStatus(200);

        $ids = collect($respuesta->json('data'))->pluck('id')->all();
        $this->assertSame([$propia], $ids);
        $this->assertNotContains($ajena, $ids);
    }

    public function test_contactos_validos(): void
    {
        $yo = $this->crearTrabajador('personal', 'yo@test.com');
        $vet = $this->crearTrabajador('veterinario', 'vet@test.com');
        $this->crearCuenta('admin', 'admin@test.com');
        $this->crearCuenta('adoptante', 'ado@test.com');
        $this->crearTrabajador('personal', 'inactiva@test.com', 'inactivo');

        $this->entrarComo($yo);
        $contactos = $this->getJson('/api/conversaciones/contactos')->assertStatus(200)->json('data');

        $this->assertSame([$vet->id], collect($contactos)->pluck('id')->all());
        $this->assertArrayNotHasKey('email', $contactos[0]);
    }

    public function test_detalle_solo_participantes(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $ajeno = $this->crearTrabajador('personal', 'ajeno@test.com');
        $admin = $this->crearCuenta('admin', 'admin@test.com');

        $id = $this->iniciarConversacion($a, [$b->id]);

        $this->entrarComo($b);
        $this->getJson("/api/conversaciones/{$id}")->assertStatus(200);

        $this->entrarComo($ajeno);
        $this->getJson("/api/conversaciones/{$id}")->assertStatus(403);

        $this->entrarComo($admin);
        $this->getJson("/api/conversaciones/{$id}")->assertStatus(403);

        $this->entrarComo($a);
        $this->getJson('/api/conversaciones/9999')->assertStatus(404);
    }

    // ---------- mensajes ----------

    public function test_enviar_y_leer_mensajes(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->iniciarConversacion($a, [$b->id]);

        $this->entrarComo($a);
        $primero = $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'Uno'])
            ->assertStatus(201)
            ->assertJsonPath('data.emisor.id', $a->id)
            ->json('data.id');

        $this->entrarComo($b);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'Dos'])->assertStatus(201);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'Tres'])->assertStatus(201);

        $todos = $this->getJson("/api/conversaciones/{$id}/mensajes")->assertStatus(200)->json('data');
        $this->assertSame(['Uno', 'Dos', 'Tres'], collect($todos)->pluck('texto')->all());

        // los últimos mensajes, en orden cronológico
        $ultimos = $this->getJson("/api/conversaciones/{$id}/mensajes?limite=2")->json('data');
        $this->assertSame(['Dos', 'Tres'], collect($ultimos)->pluck('texto')->all());

        // solo los posteriores a uno conocido
        $nuevos = $this->getJson("/api/conversaciones/{$id}/mensajes?despues_de={$primero}")->json('data');
        $this->assertSame(['Dos', 'Tres'], collect($nuevos)->pluck('texto')->all());

        $this->assertNotNull(Conversacion::find($id)->ultimo_mensaje_at);
    }

    public function test_ajeno_no_lee_ni_escribe(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $ajeno = $this->crearTrabajador('veterinario', 'ajeno@test.com');
        $id = $this->iniciarConversacion($a, [$b->id], ['mensaje' => 'Privado']);

        $this->entrarComo($ajeno);
        $this->getJson("/api/conversaciones/{$id}/mensajes")->assertStatus(403);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'intruso'])->assertStatus(403);

        $this->assertDatabaseMissing('mensajes', ['texto' => 'intruso']);
    }

    public function test_validaciones_del_mensaje(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->iniciarConversacion($a, [$b->id]);

        $this->entrarComo($a);
        $this->postJson("/api/conversaciones/{$id}/mensajes", [])->assertStatus(422);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => '   '])->assertStatus(422);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => str_repeat('a', 2001)])->assertStatus(422);
        $this->getJson("/api/conversaciones/{$id}/mensajes?limite=500")->assertStatus(422);
        $this->postJson('/api/conversaciones/9999/mensajes', ['texto' => 'hola'])->assertStatus(404);

        $this->assertDatabaseCount('mensajes', 0);
    }

    public function test_ficha_inactiva_pierde_acceso(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->iniciarConversacion($a, [$b->id]);

        $b->empleado->forceFill(['estado_laboral' => 'inactivo'])->save();

        $this->entrarComo($b->fresh());
        $this->getJson("/api/conversaciones/{$id}")->assertStatus(403);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'hola'])->assertStatus(403);
    }

    public function test_politica_solo_participantes(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $ajeno = $this->crearTrabajador('personal', 'ajeno@test.com');
        $id = $this->iniciarConversacion($a, [$b->id]);
        $conversacion = Conversacion::find($id);

        $this->assertTrue(Gate::forUser($a)->allows('ver', $conversacion));
        $this->assertTrue(Gate::forUser($b)->allows('escribir', $conversacion));
        $this->assertFalse(Gate::forUser($ajeno)->allows('ver', $conversacion));
    }
}