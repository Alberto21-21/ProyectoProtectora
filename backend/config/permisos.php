<?php

return [

    // OPERACIONES COMUNES A TODAS LAS CUENTAS ACTIVAS
    'comunes' => [
        'perfil:consultar',
        'perfil:editar',
        'password:cambiar',
    ],

    // OPERACIONES SEGÚN EL ROL
    'roles' => [

        'admin' => [
            // El administrador puede gestionar las instalaciones y consultar su ficha.
            'usuarios:consultar',
            'usuarios:crear',
            'usuarios:editar',
            'usuarios:desactivar',
            'usuarios:roles',
            'personal:gestionar',
            'disponibilidad:consultar',
            'disponibilidad:gestionar',
            'animales:consultar',
            'animales:gestionar',
            'animales:asignar',
            'clinica:consultar',
            'clinica:gestionar',
            'asignaciones:consultar',
            'asignaciones:gestionar',
            'clinica:consultar-todas',
            'asignaciones:administrar',
            'instalaciones:gestionar',
            'instalaciones:consultar',
            'solicitudes:revisar',
            'solicitudes:gestionar',        
        ],

        'adoptante' => [
            'animales:consultar',
            'hogar:consultar',
            'hogar:editar',
            'compatibilidad:consultar',
            'solicitudes:crear',
            'solicitudes:consultar-propias',
            'solicitudes:cancelar-propias',
        ],

        'personal' => [
            'animales:consultar',
            'animales:gestionar',
            'disponibilidad:consultar',
            'asignaciones:consultar',
            'instalaciones:consultar',
            'chat:usar',
        ],

        'veterinario' => [
            'animales:consultar',
            'animales:gestionar',
            'disponibilidad:consultar',
            'clinica:consultar',
            'clinica:gestionar',
            'asignaciones:consultar',
            'instalaciones:consultar',
            'chat:usar',
        ],

    ],

];