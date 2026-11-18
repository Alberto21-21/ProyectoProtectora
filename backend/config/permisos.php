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
        ],

        'adoptante' => [
            'animales:consultar',
        ],

        'personal' => [
            'animales:consultar',
            'animales:gestionar',
            'disponibilidad:consultar',
            'asignaciones:consultar',
            'asignaciones:gestionar',
        ],

        'veterinario' => [
            'animales:consultar',
            'animales:gestionar',
            'disponibilidad:consultar',
            'clinica:consultar',
            'clinica:gestionar',
            'asignaciones:consultar',
        ],

    ],

];