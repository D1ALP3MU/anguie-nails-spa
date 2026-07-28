<?php

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : Response.php
 *
 * Descripción:
 * Centraliza la generación de respuestas JSON para la API.
 *
 * Responsabilidades:
 * - Enviar respuestas exitosas.
 * - Enviar respuestas de error.
 * - Establecer códigos HTTP.
 * - Mantener un formato uniforme en toda la API.
 * ---------------------------------------------------------
 */
class Response
{
    /**
     * Envía una respuesta JSON al cliente.
     *
     * @param int $statusCode Código HTTP de la respuesta.
     * @param array $body Contenido de la respuesta.
     *
     * @return void
     */
    private static function send(int $statusCode, array $body): void
    {
        http_response_code($statusCode);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($body);
    }

    /**
     * Envía una respuesta exitosa.
     *
     * @param mixed $data Información que se devolverá al cliente.
     *
     * @return void
     */
    public static function success(mixed $data): void
    {
        self::send(200, [
            "success" => true,
            "data" => $data
        ]);
    }

    /**
     * Envía una respuesta indicando que un recurso fue creado.
     *
     * @param mixed $data Información del recurso creado.
     *
     * @return void
     */
    public static function created(mixed $data): void
    {
        self::send(201, [
            "success" => true,
            "data" => $data
        ]);
    }

    /**
     * Envía una respuesta de error.
     *
     * @param string $message Descripción del error.
     * @param int $statusCode Código HTTP.
     *
     * @return void
     */
    public static function error(string $message, int $statusCode = 400): void
    {
        self::send($statusCode, [
            "success" => false,
            "message" => $message
        ]);
    }

    /**
     * Envía errores de validación.
     *
     * @param array $errors Lista de errores encontrados.
     *
     * @return void
     */
    public static function validation(array $errors): void
    {
        self::send(422, [
            "success" => false,
            "errors" => $errors
        ]);
    }

    /**
     * Envía una respuesta JSON.
     *
     * @param array $body Contenido de la respuesta.
     * @param int $status Código HTTP.
     *
     * @return void
     */
    public static function json(array $body, int $status = 200): void
    {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        exit;
    }
}
