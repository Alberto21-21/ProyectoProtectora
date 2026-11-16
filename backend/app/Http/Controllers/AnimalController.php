<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateEstadoAnimalRequest;
use App\Models\Animal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\CloudinaryService;

class AnimalController extends Controller
{
    /**
     * Obtener listado de animales con búsqueda y filtros.
     * El catálogo público muestra únicamente los animales disponibles.
     */
    
        // CONSULTAR EL LISTADO SEGÚN LOS PERMISOS DEL USUARIO
    public function index(Request $request)
    {
        $consulta = Animal::query();

        $puedeVerInternos = $this->puedeConsultarDatosInternos($request);

        // SOLO EL PERSONAL AUTORIZADO PUEDE CONSULTAR LAS BAJAS
        if ($puedeVerInternos && $request->boolean('bajas')) {
            $consulta->where('activo', false);
        } else {
            $consulta->where('activo', true);
        }

        // EL CATÁLOGO PÚBLICO SOLO MUESTRA ANIMALES DISPONIBLES
        if (!$puedeVerInternos) {
            $consulta->where('estado', 'Disponible');
        } elseif ($request->filled('estado')) {
            $consulta->where('estado', $request->estado);
        }

        // BUSCAR POR NOMBRE O RAZA
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');

            $consulta->where(function ($consultaBusqueda) use ($buscar) {
                $consultaBusqueda->where('nombre', 'like', '%' . $buscar . '%')
                    ->orWhere('raza', 'like', '%' . $buscar . '%');
            });
        }

        // FILTRAR POR ESPECIE
        if ($request->filled('especie')) {
            $consulta->where('especie', $request->especie);
        }

        $animales = $consulta->orderBy('created_at', 'desc')->get();

        if ($puedeVerInternos) {
            return response()->json($animales, 200);
        }

        // DEVOLVER SOLO LOS CAMPOS PUBLICABLES
        $datosPublicos = $animales->map(function ($animal) {
            return $this->datosPublicosAnimal($animal);
        });

        return response()->json($datosPublicos, 200);
    }

    // CONSULTAR LA FICHA SEGÚN LOS PERMISOS DEL USUARIO
    public function show(Request $request, $id)
    {
        $puedeVerInternos = $this->puedeConsultarDatosInternos($request);

        $consulta = Animal::query();

        if (!$puedeVerInternos) {
            $consulta->where('activo', true)
                ->where('estado', 'Disponible');
        }

        $animal = $consulta->find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        if ($puedeVerInternos) {
            return response()->json($animal, 200);
        }

        return response()->json(
            $this->datosPublicosAnimal($animal),
            200
        );
    }

    public function store(
        StoreAnimalRequest $request,
        CloudinaryService $cloudinary
    ) {
        $datos = $request->validated();

        // SEPARAR LOS ARCHIVOS DE LOS DATOS DEL ANIMAL
        unset($datos['fotografia'], $datos['imagen']);

        $resultado = null;

        $archivo = $request->file('fotografia');

        // SUBIR LA FOTOGRAFÍA A CLOUDINARY
        if ($archivo) {
            $resultado = $cloudinary->upload(
                $archivo,
                'protectora/animales'
            );

            $datos['fotografia'] = $resultado['secure_url'];
            $datos['fotografia_public_id'] = $resultado['public_id'];
        }

        $datos['user_id'] = $request->user()->id;

        try {
            $animal = Animal::create($datos);

        } catch (\Throwable $error) {
            // EVITAR DEJAR UNA IMAGEN SIN ANIMAL ASOCIADO
            if ($resultado) {
                try {
                    $cloudinary->eliminar($resultado['public_id']);

                } catch (\Throwable $errorLimpieza) {
                    report($errorLimpieza);
                }
            }

            throw $error;
        }

        return response()->json([
            'success' => true,
            'message' => 'Animal registrado correctamente.',
            'data' => [
                'id' => $animal->id,
                'nombre' => $animal->nombre,
                'especie' => $animal->especie,
                'estado' => $animal->estado,
                'fotografia_url' => $animal->fotografia
            ]
        ], 201);
    }

    /**
     * Actualizar los datos generales de un animal (sin saltar las transiciones de estado).
     */
    public function update(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'especie' => 'required|in:Perro,Gato,Otro',
            'raza' => 'nullable|string|max:100',
            'edad' => 'nullable|integer|min:0|max:40',
            'sexo' => 'required|in:Macho,Hembra',
            'peso' => 'nullable|numeric|min:0|max:200',
            'caracter' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'historia_publica' => 'sometimes|nullable|string|max:3000',
            'cuidados_publicos' => 'sometimes|nullable|string|max:3000'
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.string' => 'El nombre debe ser un texto.',
            'nombre.max' => 'El nombre no puede superar los 255 caracteres.',
            'especie.required' => 'La especie es obligatoria.',
            'especie.in' => 'La especie debe ser Perro, Gato u Otro.',
            'raza.string' => 'La raza debe ser un texto.',
            'raza.max' => 'La raza no puede superar los 100 caracteres.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.min' => 'La edad no puede ser negativa.',
            'edad.max' => 'La edad no puede ser mayor de 40 años.',
            'sexo.required' => 'El sexo es obligatorio.',
            'sexo.in' => 'El sexo debe ser Macho o Hembra.',
            'peso.numeric' => 'El peso debe ser un número.',
            'peso.min' => 'El peso no puede ser negativo.',
            'peso.max' => 'El peso no puede ser mayor de 200 kg.',
            'caracter.string' => 'El carácter debe ser un texto.',
            'caracter.max' => 'El carácter no puede superar los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'historia_publica.string' => 'La historia pública debe ser un texto.',
            'historia_publica.max' => 'La historia pública no puede superar los 3000 caracteres.',
            'cuidados_publicos.string' => 'Los cuidados públicos deben ser un texto.',
            'cuidados_publicos.max' => 'Los cuidados públicos no pueden superar los 3000 caracteres.'
        ]);

        $animal->nombre = $request->nombre;
        $animal->especie = $request->especie;
        $animal->raza = $request->raza;
        $animal->edad = $request->edad;
        $animal->sexo = $request->sexo;
        $animal->peso = $request->peso;
        $animal->caracter = $request->caracter;
        $animal->descripcion = $request->descripcion;

        // ACTUALIZAR LOS TEXTOS PÚBLICOS SOLO SI SE ENVÍAN
        if ($request->exists('historia_publica')) {
            $animal->historia_publica = $request->historia_publica;
        }

        if ($request->exists('cuidados_publicos')) {
            $animal->cuidados_publicos = $request->cuidados_publicos;
        }
        
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'Ficha del animal actualizada correctamente.',
            'data' => $animal
        ], 200);
    }

    /**
     * T15: Actualizar el estado de un animal aplicando solo transiciones permitidas.
     */
    public function actualizarEstado(UpdateEstadoAnimalRequest $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        // Un animal dado de baja no admite cambios de estado
        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El animal está dado de baja y no admite cambios de estado.'
            ], 422);
        }

        $estadoActual = $animal->estado;
        $nuevoEstado = $request->validated()['estado'];

        // La adopción formalizada se realiza desde su proceso específico
        if ($nuevoEstado === 'Adoptado') {
            return response()->json([
                'success' => false,
                'message' => 'La adopción formalizada debe realizarse desde su proceso específico.'
            ], 422);
        }

        if ($nuevoEstado === $estadoActual) {
            return response()->json([
                'success' => false,
                'message' => "El animal ya se encuentra en estado '{$estadoActual}'."
            ], 422);
        }

        // Definición de transiciones permitidas
        $transicionesPermitidas = [
            'En acogida'     => ['En tratamiento', 'Disponible'],
            'En tratamiento' => ['En acogida', 'Disponible'],
            'Disponible'     => ['En acogida', 'En tratamiento', 'Reservado'],
            'Reservado'      => ['Disponible', 'En acogida', 'En tratamiento'],
            'Adoptado'       => [], // Estado final: no admite cambios manuales
        ];

        $permitidos = $transicionesPermitidas[$estadoActual] ?? [];

        if (!in_array($nuevoEstado, $permitidos, true)) {
            return response()->json([
                'success' => false,
                'message' => "Transición no permitida: un animal en estado '{$estadoActual}' no puede pasar a '{$nuevoEstado}'."
            ], 422);
        }

        $animal->estado = $nuevoEstado;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => "Estado actualizado a '{$nuevoEstado}' correctamente.",
            'data' => $animal
        ], 200);
    }

    /**
     * T16: Dar de baja un animal (baja lógica).
     * El animal no se borra: se marca como inactivo y se conservan
     * su historial, solicitudes y adopciones.
     */
    public function darDeBaja(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'motivo_baja' => 'required|string|max:255'
        ], [
            'motivo_baja.required' => 'Debes indicar el motivo de la baja.',
            'motivo_baja.string' => 'El motivo debe ser un texto.',
            'motivo_baja.max' => 'El motivo no puede superar los 255 caracteres.'
        ]);

        // RECHAZO: EL ANIMAL YA ESTÁ DADO DE BAJA
        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'El animal ya está dado de baja desde el ' . $animal->fecha_baja . '.'
            ], 422);
        }

        // RECHAZO: EL ANIMAL TIENE UNA RESERVA EN CURSO
        if ($animal->estado === 'Reservado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede dar de baja: el animal está reservado por un proceso de adopción en curso. Cambia primero su estado.'
            ], 422);
        }

        $animal->activo = false;
        $animal->fecha_baja = now()->toDateString();
        $animal->motivo_baja = $request->motivo_baja;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'El animal se ha dado de baja correctamente. Sus registros se conservan.',
            'data' => $animal
        ], 200);
    }

    /**
     * T16: Eliminar un animal definitivamente.
     * Solo se permite si no tiene historial, solicitudes ni adopciones.
     * Si los tiene, se rechaza para no perder su historia.
     */
    
    public function destroy($id, CloudinaryService $cloudinary)    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        // COMPROBAR LOS REGISTROS ASOCIADOS AL ANIMAL
        $historiales = 0;
        $solicitudes = 0;
        $adopciones = 0;

        if (Schema::hasTable('historiales')) {
            $historiales = DB::table('historiales')->where('animal_id', $animal->id)->count();
        }

        if (Schema::hasTable('solicitudes')) {
            $solicitudes = DB::table('solicitudes')->where('animal_id', $animal->id)->count();
        }

        if (Schema::hasTable('adopciones')) {
            $adopciones = DB::table('adopciones')->where('animal_id', $animal->id)->count();
        }

        // UN ANIMAL ADOPTADO SIEMPRE TIENE UNA ADOPCIÓN QUE CONSERVAR
        if ($animal->estado === 'Adoptado' && $adopciones === 0) {
            $adopciones = 1;
        }

        // RECHAZO: TIENE REGISTROS QUE NO SE PUEDEN PERDER
        if ($historiales > 0 || $solicitudes > 0 || $adopciones > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: el animal tiene registros asociados (historiales: ' . $historiales
                    . ', solicitudes: ' . $solicitudes
                    . ', adopciones: ' . $adopciones
                    . '). Usa "Dar de baja" para conservar su historia.'
            ], 422);
        }

        // RECHAZO: EL ANIMAL TIENE UNA RESERVA EN CURSO
        if ($animal->estado === 'Reservado') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar: el animal está reservado por un proceso de adopción en curso.'
            ], 422);
        }

               // GUARDAR LAS REFERENCIAS ANTES DE ELIMINAR EL ANIMAL
        $imagenes = [
            [
                'ruta' => $animal->fotografia,
                'public_id' => $animal->fotografia_public_id
            ],
            [
                'ruta' => $animal->imagen,
                'public_id' => $animal->imagen_public_id
            ]
        ];

        $animal->delete();

        // ELIMINAR LAS IMÁGENES DE CLOUDINARY O DEL ALMACENAMIENTO LOCAL
        $imagenesEliminadas = [];

        foreach ($imagenes as $imagen) {
            try {
                if ($imagen['public_id']) {
                    if (!in_array(
                        $imagen['public_id'],
                        $imagenesEliminadas,
                        true
                    )) {
                        $cloudinary->eliminar($imagen['public_id']);
                        $imagenesEliminadas[] = $imagen['public_id'];
                    }

                } elseif (
                    $imagen['ruta']
                    && str_starts_with($imagen['ruta'], 'animales/')
                ) {
                    Storage::disk('public')->delete($imagen['ruta']);
                }

            } catch (\Throwable $errorLimpieza) {
                report($errorLimpieza);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'El animal no tenía registros asociados y se ha eliminado definitivamente.'
        ], 200);
    }

    /**
     * T17: Consultar los animales asignados al trabajador autenticado.
     * Devuelve los datos de cuidado y necesidades, pero NO los datos clínicos.
     */
    public function misAnimales(Request $request)
    {
        $usuario = $request->user();

        $animales = Animal::where('cuidador_id', $usuario->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        // Las necesidades especiales están ocultas por defecto en el modelo,
        // pero el cuidador necesita verlas para atender bien al animal.
        // Las observaciones clínicas siguen ocultas ($hidden del modelo).
        $animales->makeVisible('necesidades_especiales');

        return response()->json([
            'success' => true,
            'message' => 'Animales asignados consultados correctamente.',
            'data' => $animales
        ], 200);
    }

    /**
     * T17: Asignar un trabajador y sus instrucciones de cuidado a un animal (solo administrador).
     */
    public function asignarCuidador(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        if (!$animal->activo) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede asignar un cuidador a un animal dado de baja.'
            ], 422);
        }

        $request->validate([
            'cuidador_id' => 'nullable|integer|exists:users,id',
            'instrucciones_cuidado' => 'nullable|string|max:2000'
        ], [
            'cuidador_id.integer' => 'El trabajador seleccionado no es válido.',
            'cuidador_id.exists' => 'El trabajador seleccionado no existe.',
            'instrucciones_cuidado.string' => 'Las instrucciones deben ser un texto.',
            'instrucciones_cuidado.max' => 'Las instrucciones no pueden superar los 2000 caracteres.'
        ]);

        // COMPROBAR QUE EL USUARIO ELEGIDO ES UN TRABAJADOR ACTIVO
        if ($request->cuidador_id) {
            $cuidador = User::find($request->cuidador_id);

            if ($cuidador->rol !== 'personal' && $cuidador->rol !== 'veterinario') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede asignar el animal a personal o veterinarios.'
                ], 422);
            }

            if ($cuidador->estado !== 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede asignar el animal a una cuenta desactivada.'
                ], 422);
            }
        }

        $animal->cuidador_id = $request->cuidador_id;
        $animal->instrucciones_cuidado = $request->instrucciones_cuidado;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'Asignación del animal guardada correctamente.',
            'data' => [
                'id' => $animal->id,
                'cuidador_id' => $animal->cuidador_id,
                'instrucciones_cuidado' => $animal->instrucciones_cuidado
            ]
        ], 200);
    }

    /**
     * Actualizar la fotografía de un animal.
     */
    public function actualizarImagen(
        Request $request,
        $id,
        CloudinaryService $cloudinary
    ) {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'imagen' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048'
        ], [
            'imagen.required' => 'Debes seleccionar una fotografía.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La fotografía debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La fotografía no puede superar los 2 MB.'
        ]);

        $imagenAnterior = $animal->imagen;
        $publicIdAnterior = $animal->imagen_public_id;

        // SUBIR LA NUEVA IMAGEN
        $resultado = $cloudinary->upload(
            $request->file('imagen'),
            'protectora/animales'
        );

        try {
            $animal->imagen = $resultado['secure_url'];
            $animal->imagen_public_id = $resultado['public_id'];
            $animal->save();

        } catch (\Throwable $error) {
            try {
                $cloudinary->eliminar($resultado['public_id']);

            } catch (\Throwable $errorLimpieza) {
                report($errorLimpieza);
            }

            throw $error;
        }

        // ELIMINAR LA IMAGEN ANTERIOR TRAS GUARDAR LA NUEVA
        try {
            if ($publicIdAnterior) {
                $cloudinary->eliminar($publicIdAnterior);

            } elseif (
                $imagenAnterior
                && str_starts_with($imagenAnterior, 'animales/')
            ) {
                Storage::disk('public')->delete($imagenAnterior);
            }

        } catch (\Throwable $errorLimpieza) {
            report($errorLimpieza);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fotografía del animal actualizada correctamente.',
            'data' => [
                'imagen_url' => $animal->imagen
            ]
        ], 200);
    }
    /**
     * Consultar los datos clínicos de un animal.
     */
    public function mostrarDatosClinicos($id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $animal->id,
                'necesidades_especiales' => $animal->necesidades_especiales,
                'observaciones_clinicas' => $animal->observaciones_clinicas
            ]
        ], 200);
    }

    /**
     * Actualizar los datos clínicos de un animal.
     */
    public function actualizarDatosClinicos(Request $request, $id)
    {
        $animal = Animal::find($id);

        if (!$animal) {
            return response()->json([
                'success' => false,
                'message' => 'Animal no encontrado.'
            ], 404);
        }

        $request->validate([
            'necesidades_especiales' => 'nullable|string|max:2000',
            'observaciones_clinicas' => 'nullable|string|max:2000'
        ], [
            'necesidades_especiales.string' => 'Las necesidades especiales deben ser un texto.',
            'necesidades_especiales.max' => 'Las necesidades especiales no pueden superar los 2000 caracteres.',
            'observaciones_clinicas.string' => 'Las observaciones clínicas deben ser un texto.',
            'observaciones_clinicas.max' => 'Las observaciones clínicas no pueden superar los 2000 caracteres.'
        ]);

        $animal->necesidades_especiales = $request->necesidades_especiales;
        $animal->observaciones_clinicas = $request->observaciones_clinicas;
        $animal->save();

        return response()->json([
            'success' => true,
            'message' => 'Datos clínicos actualizados correctamente.',
            'data' => [
                'id' => $animal->id,
                'necesidades_especiales' => $animal->necesidades_especiales,
                'observaciones_clinicas' => $animal->observaciones_clinicas
            ]
        ], 200);
    }

        // COMPROBAR EL ACCESO A LA INFORMACIÓN INTERNA
    private function puedeConsultarDatosInternos(Request $request): bool
    {
        $usuario = $request->user('sanctum');

        if (!$usuario || $usuario->estado !== 'activo') {
            return false;
        }

        if (!in_array(
            $usuario->rol,
            ['admin', 'personal', 'veterinario'],
            true
        )) {
            return false;
        }

        return in_array(
            'animales:consultar',
            $usuario->obtenerPermisos(),
            true
        ) && $usuario->tokenCan('animales:consultar');
    }

    // CAMPOS PERMITIDOS EN EL CATÁLOGO PÚBLICO

    private function datosPublicosAnimal(Animal $animal): array
    {
        $datos = $animal->only([
            'id',
            'nombre',
            'fotografia',
            'imagen',
            'especie',
            'raza',
            'fecha_nacimiento',
            'sexo',
            'tamano',
            'peso',
            'estado',
            'caracter',
            'con_ninos',
            'con_otros_animales',
            'con_desconocidos',
            'historia_publica',
            'cuidados_publicos',
        ]);

        $datos['fotografia_url'] = $this->obtenerUrlImagen(
            $animal->fotografia
        );

        $datos['imagen_url'] = $this->obtenerUrlImagen(
            $animal->imagen
        );

        return $datos;
    }

    // OBTENER LA URL DE UNA IMAGEN LOCAL O DE CLOUDINARY
    private function obtenerUrlImagen(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        if (str_starts_with($ruta, 'https://')) {
            return $ruta;
        }

        return asset('storage/' . $ruta);
    }
 }