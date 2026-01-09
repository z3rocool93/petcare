<?php

return [
    'accepted'        => ':attribute debe ser aceptado.',
    'after_or_equal'  => 'El campo :attribute no puede ser una fecha pasada.',
    'before_or_equal' => ':attribute debe ser una fecha anterior o igual a :date.',
    'date'            => ':attribute no es una fecha válida.',
    'email'           => ':attribute debe ser una dirección de correo válida.',
    'required'        => 'El campo :attribute es obligatorio.',
    'min'             => [
        'string' => 'El campo :attribute debe contener al menos :min caracteres.',
    ],
    // Puedes personalizar nombres de atributos aquí
    'attributes' => [
        'pet_id' => 'mascota',
        'date' => 'fecha',
        'time' => 'hora',
        'reason' => 'motivo',
        'name' => 'nombre',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
    ],
];
