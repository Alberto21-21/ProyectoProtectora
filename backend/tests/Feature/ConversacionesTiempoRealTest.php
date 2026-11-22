<?php

namespace Tests\Feature;

use App\Events\MensajeEnviado;
use App\Models\Conversacion;
use App\Models\Empleado;
use App\Models\Mensaje;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// T66: mensajes en tiempo real por canales privados
class ConversacionesTiempoRealTest extends TestCase
{
    use RefreshDatabase;

    private function crearTrabajador(string $rol, string $email, string $estadoFicha = 'activo'): User
    {
        $usuario = User::forceCreate([
            'name' => ucfirst($rol) . ' ' . $email,
            'email' => $email,
            'password' => 'Secreta123',
            'rol' => $rol,
            'estado' => 'activo',
        ]);

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

    // CONVERSACIÓN ENTRE DOS CUENTAS, DEVUELVE SU ID
    private function crearConversacion(User $creador, User $otro): int
    {
        $this->entrarComo($creador);

        return $this->postJson('/api/conversaciones', [
            'participantes' => [$otro->id],
        ])->assertStatus(201)->json('data.id');
    }

    public function test_enviar_un_mensaje_lo_publica(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->crearConversacion($a, $b);

        Event::fake([MensajeEnviado::class]);

        $this->entrarComo($a);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'Hola'])
            ->assertStatus(201);

        Event::assertDispatchedTimes(MensajeEnviado::class, 1);
        Event::assertDispatched(MensajeEnviado::class, function ($evento) use ($id) {
            return $evento->mensaje->texto === 'Hola'
                && $evento->mensaje->conversacion_id === $id;
        });
    }

    public function test_el_evento_va_a_un_canal_privado(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->crearConversacion($a, $b);

        $mensaje = Mensaje::create([
            'conversacion_id' => $id,
            'user_id' => $a->id,
            'texto' => 'Privado',
        ])->load('emisor');

        $evento = new MensajeEnviado($mensaje);
        $canales = $evento->broadcastOn();

        $this->assertCount(1, $canales);
        $this->assertInstanceOf(PrivateChannel::class, $canales[0]);
        $this->assertSame("private-conversacion.{$id}", $canales[0]->name);
        $this->assertSame('mensaje.enviado', $evento->broadcastAs());
        $this->assertSame('Privado', $evento->broadcastWith()['mensaje']['texto']);
        $this->assertSame($a->id, $evento->broadcastWith()['mensaje']['emisor']['id']);
    }

    public function test_sin_acceso_o_con_error_no_se_publica(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $ajeno = $this->crearTrabajador('personal', 'ajeno@test.com');
        $id = $this->crearConversacion($a, $b);

        Event::fake([MensajeEnviado::class]);

        $this->entrarComo($ajeno);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'intruso'])->assertStatus(403);

        $this->entrarComo($a);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => '   '])->assertStatus(422);

        Event::assertNotDispatched(MensajeEnviado::class);
    }

    public function test_si_falla_el_websocket_el_mensaje_se_guarda(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $id = $this->crearConversacion($a, $b);

        // Reverb mal configurado: al publicar falla
        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => null,
            'broadcasting.connections.reverb.app_id' => null,
        ]);

        $this->entrarComo($a);
        $this->postJson("/api/conversaciones/{$id}/mensajes", ['texto' => 'Se guarda'])
            ->assertStatus(201);

        $this->assertDatabaseHas('mensajes', ['texto' => 'Se guarda']);

        // y se recupera al reconectar con despues_de
        $this->getJson("/api/conversaciones/{$id}/mensajes?despues_de=0")
            ->assertStatus(200)
            ->assertJsonPath('data.0.texto', 'Se guarda');
    }

    public function test_el_canal_solo_autoriza_a_participantes(): void
    {
        $a = $this->crearTrabajador('personal', 'a@test.com');
        $b = $this->crearTrabajador('veterinario', 'b@test.com');
        $ajeno = $this->crearTrabajador('personal', 'ajeno@test.com');
        $inactivo = $this->crearTrabajador('personal', 'inactivo@test.com');
        $id = $this->crearConversacion($a, $b);

        // El inactivo participa, pero con la ficha laboral inactiva pierde el acceso
        Conversacion::find($id)->participantes()->attach($inactivo->id);
        $inactivo->empleado->forceFill(['estado_laboral' => 'inactivo'])->save();

        $canales = Broadcast::driver()->getChannels();
        $this->assertArrayHasKey('conversacion.{id}', $canales);
        $autorizar = $canales['conversacion.{id}'];

        $this->assertTrue((bool) $autorizar($a, $id));
        $this->assertTrue((bool) $autorizar($b, $id));
        $this->assertFalse((bool) $autorizar($ajeno, $id));
        $this->assertFalse((bool) $autorizar($inactivo->fresh(), $id));
        $this->assertFalse((bool) $autorizar($a, 9999));
    }
}
