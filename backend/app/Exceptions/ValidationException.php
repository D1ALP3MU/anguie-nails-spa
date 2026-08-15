<?php

namespace App\Exceptions;

use Exception;

class ValidationException extends Exception
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        private array $errors
    ) {
        parent::__construct(
            'Los datos enviados no son válidos.'
        );
    }

    /**
     * Obtiene los errores de validación.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
