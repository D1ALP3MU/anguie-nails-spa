<?php

namespace App\Core;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : Route.php
 *
 * Descripción:
 * Describe una ruta de la API y lo que exige para ejecutarse.
 *
 * La autorización se declara junto a la ruta en lugar de
 * aplicarse a mano dentro de cada caso del enrutador. Así,
 * olvidar una protección se ve de un vistazo en la tabla.
 *
 * Los segmentos entre llaves son parámetros y se inyectan en
 * el método del controlador por nombre:
 *
 *     Route::get('/api/clients/{id}', ClientController::class, 'show')
 *
 *     public function show(int $id, array $authUser): void
 * ---------------------------------------------------------
 */
class Route
{
    /**
     * Roles autorizados. Vacío significa cualquier rol.
     *
     * @var array<int>
     */
    private array $allowedRoles = [];

    private bool $requiresAuth = false;

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $controller,
        public readonly string $action
    ) {}

    public static function get(
        string $path,
        string $controller,
        string $action
    ): self {
        return new self('GET', $path, $controller, $action);
    }

    public static function post(
        string $path,
        string $controller,
        string $action
    ): self {
        return new self('POST', $path, $controller, $action);
    }

    public static function put(
        string $path,
        string $controller,
        string $action
    ): self {
        return new self('PUT', $path, $controller, $action);
    }

    public static function delete(
        string $path,
        string $controller,
        string $action
    ): self {
        return new self('DELETE', $path, $controller, $action);
    }

    /**
     * Exige un token válido.
     *
     * @return self
     */
    public function requireAuth(): self
    {
        $this->requiresAuth = true;

        return $this;
    }

    /**
     * Restringe la ruta a los roles indicados. Implica requireAuth().
     *
     * @param int ...$roles Roles autorizados.
     *
     * @return self
     */
    public function allowRoles(int ...$roles): self
    {
        $this->requiresAuth = true;

        $this->allowedRoles = $roles;

        return $this;
    }

    public function needsAuthentication(): bool
    {
        return $this->requiresAuth;
    }

    /**
     * @return array<int>
     */
    public function roles(): array
    {
        return $this->allowedRoles;
    }

    /**
     * Compara la ruta con una URL y devuelve los parámetros
     * capturados, o null si no coincide.
     *
     * @param string $method Método HTTP de la petición.
     * @param string $path Ruta solicitada.
     *
     * @return array<string, string>|null
     */
    public function match(string $method, string $path): ?array
    {
        if ($method !== $this->method) {
            return null;
        }

        if (!preg_match('#^' . $this->pattern() . '$#', $path, $matches)) {
            return null;
        }

        return array_filter(
            $matches,
            'is_string',
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Traduce la ruta declarada a una expresión regular.
     *
     * Los tramos literales se escapan: sin eso, un punto en la
     * ruta actuaría como comodín y '/api/v1.0' también aceptaría
     * '/api/v1X0'.
     *
     * @return string
     */
    private function pattern(): string
    {
        // Se conserva el delimitador con llaves incluidas, de modo
        // que un tramo literal nunca pueda confundirse con un
        // parámetro aunque coincida su texto.
        $segments = preg_split(
            '#(\{[a-zA-Z_][a-zA-Z0-9_]*\})#',
            $this->path,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $pattern = '';

        foreach ($segments as $segment) {

            $isPlaceholder = preg_match(
                '#^\{([a-zA-Z_][a-zA-Z0-9_]*)\}$#',
                $segment,
                $name
            );

            $pattern .= $isPlaceholder
                ? '(?P<' . $name[1] . '>[^/]+)'
                : preg_quote($segment, '#');
        }

        return $pattern;
    }
}
