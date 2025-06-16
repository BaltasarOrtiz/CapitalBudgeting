<?php

return [
    // Opción 1: Incluir solo rutas específicas (recomendado)
    'only' => [
        'dashboard.*',
        'login',
        'logout',
        'register',
        'password.*',
        'verification.*',
        'settings.*',
        'profile.*',
        // Agrega aquí las rutas que necesites en el frontend
    ],

    // Filtrar por grupos de rutas
    'groups' => [
        'auth' => ['login', 'logout', 'register'],
        'dashboard' => ['dashboard.*'],
        'settings' => ['settings.*'],
    ],
];
