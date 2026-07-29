<?php

namespace App\Validators;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Módulo   : Usuarios
 * Archivo  : UserValidator.php
 * Versión  : 1.0.0
 * 
 * Descripción:
 * Valida los datos enviados para el registro de usuarios.
 * 
 * Responsabilidades:
 * - Validar nombre
 * - Validar correo electrónico
 * - Validar contraseña
 * 
 * No realiza consultas a la base de datos
 * ---------------------------------------------------------
 */

class UserValidator
{
    /**
     * Valida la información del formulario de registro.
     * 
     * @param array $data Datos enviados por el usuario.
     * 
     * @return array Resultado de la validación.
     */
    public static function validateRegister(array $data): array
    {
        $errors = [];

        // Validar nombre
        if (empty(trim($data['nombre'] ?? ''))) {
            $errors[] = "El nombre es obligatorio.";
        }

        // Validar correo electrónico
        if (empty(trim($data['email'] ?? ''))) {
            $errors[] = "El correo electrónico es obligatorio.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "El correo electrónico no es válido.";
        }

        // Validar contraseña
        if (empty($data['password'] ?? '')) {
            $errors[] = "La contraseña es obligatoria.";
        } elseif (strlen($data['password']) < 8) {
            $errors[] = "La contraseña debe tener al menos 8 caracteres.";
        }

        return [
            "valid" => empty($errors),
            "errors" => $errors
        ];
    }
}