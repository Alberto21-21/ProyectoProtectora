<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Animal;
use App\Models\Empleado;
use App\Models\Veterinario;
use App\Models\AnimalAsignacion;
use Illuminate\Support\Facades\Auth;

class AccesosTest extends TestCase
{

    use RefreshDatabase;

    // SIN TOKEN NO SE PUEDE CONSULTAR EL PERFIL
    public function test_no_se_puede_consultar_el_perfil_sin_token(): void
    {
        $respuesta = $this->getJson('/api/user');

        $respuesta->assertStatus(401);
    }

    // SIN TOKEN NO SE PUEDE CONSULTAR EL LISTADO DE USUARIOS
    public function test_no_se_pueden_consultar_usuarios_sin_token(): void
    {
        $respuesta = $this->getJson('/api/usuarios');

        $respuesta->assertStatus(401);
    }

    // EL ADMINISTRADOR PUEDE CONSULTAR USUARIOS
    public function test_el_administrador_puede_consultar_usuarios(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo'
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/usuarios');

        $respuesta->assertStatus(200);

        $respuesta->assertJsonStructure([
            'success',
            'data' => [
                'data',
                'current_page',
                'total'
            ]
        ]);
    }

    // EL ADOPTANTE NO PUEDE CONSULTAR USUARIOS
    public function test_el_adoptante_no_puede_consultar_usuarios(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/usuarios');

        $respuesta->assertStatus(403);
    }

    // PERSONAL Y VETERINARIOS NO PUEDEN ADMINISTRAR USUARIOS
    public function test_personal_y_veterinarios_no_pueden_consultar_usuarios(): void
    {
        foreach (['personal', 'veterinario'] as $rol) {
            $usuario = User::factory()->create([
                'rol' => $rol,
                'estado' => 'activo'    
            ]);

            $token = $usuario->createToken(
                'prueba',
                $usuario->obtenerPermisos()
            )->plainTextToken;

            $respuesta = $this->withToken($token)
                ->getJson('/api/usuarios');

            $respuesta->assertStatus(403);
        }
    }

    // EL PERFIL DEVUELVE LOS DATOS DE LA CUENTA AUTENTICADA
    public function test_el_usuario_consulta_su_propio_perfil(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $otroUsuario = User::factory()->create();

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        // ENVIAR OTRO ID NO DEBE CAMBIAR EL PERFIL CONSULTADO
        $respuesta = $this->withToken($token)
            ->getJson('/api/user?id=' . $otroUsuario->id);

        $respuesta->assertStatus(200);
        $respuesta->assertJson([
            'id' => $usuario->id,
            'email' => $usuario->email
        ]);
        $respuesta->assertJsonMissingPath('password');
        $respuesta->assertJsonMissingPath('remember_token');
    }

    // EL VETERINARIO SOLO PUEDE CONSULTAR LA CLÍNICA DE SUS ANIMALES
    public function test_el_veterinario_solo_accede_a_animales_asignados(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'veterinario',
            'estado' => 'activo'
        ]);

        $empleado = new Empleado();
        $empleado->puesto = 'Veterinario';
        $empleado->estado_laboral = 'activo';

        $usuario->empleado()->save($empleado);

        $veterinario = new Veterinario();
        $veterinario->especialidad = 'Medicina general';

        $empleado->veterinario()->save($veterinario);

        // ANIMAL ASIGNADO
        $animalAsignado = new Animal();
        $animalAsignado->nombre = 'Luna';
        $animalAsignado->especie = 'Gato';
        $animalAsignado->sexo = 'Hembra';
        $animalAsignado->estado = 'Disponible';
        $animalAsignado->activo = true;
        $animalAsignado->observaciones_clinicas = 'Revisión de Luna.';
        $animalAsignado->save();

        // ANIMAL AJENO
        $animalAjeno = new Animal();
        $animalAjeno->nombre = 'Toby';
        $animalAjeno->especie = 'Perro';
        $animalAjeno->sexo = 'Macho';
        $animalAjeno->estado = 'Disponible';
        $animalAjeno->activo = true;
        $animalAjeno->save();

        $asignacion = new AnimalAsignacion();
        $asignacion->animal_id = $animalAsignado->id;
        $asignacion->tipo_asignacion = 'veterinario';

        $empleado->asignaciones()->save($asignacion);

        $token = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        // PUEDE CONSULTAR EL ANIMAL ASIGNADO
        $respuesta = $this->withToken($token)
            ->getJson('/api/animales/' . $animalAsignado->id . '/clinico');

        $respuesta->assertStatus(200);
        $respuesta->assertJson([
            'data' => [
                'id' => $animalAsignado->id,
                'observaciones_clinicas' => 'Revisión de Luna.'
            ]
        ]);

        // NO PUEDE CONSULTAR UN ANIMAL AJENO
        $respuesta = $this->withToken($token)
            ->getJson('/api/animales/' . $animalAjeno->id . '/clinico');

        $respuesta->assertStatus(403);
        $respuesta->assertJsonMissingPath('data.observaciones_clinicas');
    }

    // UN TOKEN INVÁLIDO NO PERMITE ACCEDER
    public function test_un_token_invalido_no_permite_consultar_el_perfil(): void
    {
        $respuesta = $this->withToken('token-invalido')
            ->getJson('/api/user');

        $respuesta->assertStatus(401);
    }

    // SER ADMINISTRADOR NO SUSTITUYE LAS ABILITIES DEL TOKEN
    public function test_un_token_sin_la_ability_necesaria_no_permite_acceder(): void
    {
        $usuario = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo'
        ]);

        // ESTE TOKEN SOLO PERMITE CONSULTAR EL PERFIL
        $token = $usuario->createToken(
            'prueba',
            ['perfil:consultar']
        )->plainTextToken;

        $respuesta = $this->withToken($token)
            ->getJson('/api/usuarios');

        $respuesta->assertStatus(403);
    }

    // DESACTIVAR UNA CUENTA REVOCA SUS TOKENS
    public function test_una_cuenta_desactivada_no_puede_usar_su_token_anterior(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo'
        ]);

        $usuario = User::factory()->create([
            'rol' => 'adoptante',
            'estado' => 'activo'
        ]);

        $tokenAdministrador = $administrador->createToken(
            'prueba',
            $administrador->obtenerPermisos()
        )->plainTextToken;

        $tokenUsuario = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        // EL ADMINISTRADOR DESACTIVA LA CUENTA
        $respuesta = $this->withToken($tokenAdministrador)
            ->patchJson('/api/usuarios/' . $usuario->id . '/desactivar');

        $respuesta->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'estado' => 'inactivo'
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => $usuario->getMorphClass(),
            'tokenable_id' => $usuario->id
        ]);

        // REINICIAR LA AUTENTICACIÓN ENTRE LAS DOS PETICIONES DEL TEST
        Auth::forgetGuards();

        // EL TOKEN ANTERIOR YA NO ES VÁLIDO
        $respuesta = $this->withToken($tokenUsuario)
            ->getJson('/api/user');

        $respuesta->assertStatus(401);
    }

    // CAMBIAR EL ROL REVOCA LOS TOKENS ANTERIORES
    public function test_el_cambio_de_rol_invalida_el_token_anterior(): void
    {
        $administrador = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo'
        ]);

        $usuario = User::factory()->create([
            'rol' => 'personal',
            'estado' => 'activo'
        ]);

        $tokenAdministrador = $administrador->createToken(
            'prueba',
            $administrador->obtenerPermisos()
        )->plainTextToken;

        $tokenAnterior = $usuario->createToken(
            'prueba',
            $usuario->obtenerPermisos()
        )->plainTextToken;

        // CAMBIAR EL ROL DESDE LA API
        $respuesta = $this->withToken($tokenAdministrador)
            ->patchJson('/api/usuarios/' . $usuario->id . '/rol', [
                'rol' => 'adoptante'
            ]);

        $respuesta->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'rol' => 'adoptante'
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_type' => $usuario->getMorphClass(),
            'tokenable_id' => $usuario->id
        ]);

        Auth::forgetGuards();

        // EL TOKEN CON LOS PERMISOS ANTERIORES YA NO FUNCIONA
        $respuesta = $this->withToken($tokenAnterior)
            ->getJson('/api/user');

        $respuesta->assertStatus(401);
    }
}