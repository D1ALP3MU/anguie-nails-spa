<?php

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : autoload.php
 *
 * Descripción:
 * Carga automáticamente las clases del proyecto.
 * ---------------------------------------------------------
 */

spl_autoload_register(function ($class) {

    $directories = [

        __DIR__ . '/../controllers/',
        __DIR__ . '/../services/',
        __DIR__ . '/../repositories/',
        __DIR__ . '/../validators/',
        __DIR__ . '/../helpers/',
        __DIR__ . '/../responses/',

    ];

    foreach ($directories as $directory) {

        $file = $directory . $class . '.php';

        if (file_exists($file)) {

            require_once $file;

            return;
        }
    }
});
