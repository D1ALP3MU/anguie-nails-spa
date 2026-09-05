<?php

namespace App\Core;

use App\Exceptions\ValidationException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : Request.php
 *
 * Descripción:
 * Representa la petición HTTP entrante.
 *
 * Los controladores la reciben del enrutador en lugar de leer
 * php://input por su cuenta. Eso centraliza el análisis del
 * cuerpo en un solo sitio y, sobre todo, los vuelve verificables:
 * una petición se puede construir con datos explícitos, cosa que
 * con php://input no era posible desde las pruebas.
 * ---------------------------------------------------------
 */
class Request
{
    /**
     * @param string $method Método HTTP.
     * @param string $path Ruta solicitada, sin cadena de consulta.
     * @param array $body Cuerpo ya decodificado.
     * @param array $query Parámetros de la cadena de consulta.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $body = [],
        private readonly array $query = []
    ) {}

    /**
     * Construye la petición a partir del entorno.
     *
     * @return self
     *
     * @throws ValidationException Si el cuerpo no es JSON válido.
     */
    public static function fromGlobals(): self
    {
        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            self::parseBody(
                file_get_contents('php://input')
            ),
            $_GET
        );
    }

    /**
     * Decodifica el cuerpo de la petición.
     *
     * Un cuerpo vacío es válido: hay métodos que no lo envían.
     * Uno malformado sí es un error, y conviene decirlo en vez de
     * dejar que se reporte como una lista de campos obligatorios.
     *
     * @param string $raw Cuerpo sin procesar.
     *
     * @return array
     *
     * @throws ValidationException
     */
    private static function parseBody(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            throw new ValidationException([
                'body' => 'El cuerpo de la petición no es JSON válido.'
            ]);
        }

        return $decoded;
    }

    /**
     * Cuerpo completo de la petición.
     *
     * @return array
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * Un campo del cuerpo.
     *
     * @param string $key Nombre del campo.
     * @param mixed $default Valor si el campo no viene.
     *
     * @return mixed
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Indica si el cuerpo trae un campo.
     *
     * @param string $key Nombre del campo.
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    /**
     * Un parámetro de la cadena de consulta.
     *
     * @param string $key Nombre del parámetro.
     * @param mixed $default Valor si el parámetro no viene.
     *
     * @return mixed
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }
}
