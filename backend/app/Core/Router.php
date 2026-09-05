<?php

namespace App\Core;

use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Exceptions\NotFoundException;
use App\Exceptions\MethodNotAllowedException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : Router.php
 *
 * Descripción:
 * Recorre la tabla de rutas, aplica las guardas que cada una
 * declara y ejecuta el controlador correspondiente.
 *
 * Responsabilidades:
 * - Emparejar método y ruta.
 * - Aplicar autenticación y autorización.
 * - Resolver el controlador a través del contenedor.
 * - Inyectar los parámetros de la URL y el usuario autenticado.
 *
 * Esta clase NO contiene lógica de negocio.
 * ---------------------------------------------------------
 */
class Router
{
    /**
     * @param Container $container Contenedor de dependencias.
     * @param array<Route> $routes Tabla de rutas.
     */
    public function __construct(
        private Container $container,
        private array $routes
    ) {}

    /**
     * Atiende una petición.
     *
     * @param Request $request Petición entrante.
     *
     * @return void
     *
     * @throws NotFoundException Si ninguna ruta coincide.
     */
    public function dispatch(Request $request): void
    {
        $method = $request->method;

        $path = $request->path;

        $pathMatched = false;

        foreach ($this->routes as $route) {

            $parameters = $route->match($method, $path);

            if ($parameters === null) {

                // Se recuerda si la ruta existe con otro método
                // para poder distinguir un 405 de un 404.
                if ($route->match($route->method, $path) !== null) {
                    $pathMatched = true;
                }

                continue;
            }

            $this->run($route, $parameters, $request);

            return;
        }

        if ($pathMatched) {
            throw new MethodNotAllowedException(
                'El método no está permitido para esta ruta.'
            );
        }

        throw new NotFoundException(
            'Ruta no encontrada.'
        );
    }

    /**
     * Aplica las guardas de la ruta y ejecuta el controlador.
     *
     * @param Route $route Ruta emparejada.
     * @param array<string, string> $parameters Parámetros de la URL.
     * @param Request $request Petición entrante.
     *
     * @return void
     */
    private function run(
        Route $route,
        array $parameters,
        Request $request
    ): void {
        $authUser = null;

        if ($route->needsAuthentication()) {

            $authUser = AuthMiddleware::handle();

            if ($route->roles() !== []) {
                RoleMiddleware::handle(
                    $authUser,
                    $route->roles()
                );
            }
        }

        $controller = $this->container->get($route->controller);

        $arguments = $this->resolveArguments(
            $route,
            $parameters,
            $authUser,
            $request
        );

        $controller->{$route->action}(...$arguments);
    }

    /**
     * Construye la lista de argumentos del método del controlador.
     *
     * Reglas, en orden:
     *   - un parámetro de tipo Request recibe la petición;
     *   - uno llamado $authUser recibe el usuario autenticado;
     *   - el resto se empareja por nombre con los segmentos de la URL.
     *
     * @param Route $route Ruta emparejada.
     * @param array<string, string> $parameters Parámetros de la URL.
     * @param array|null $authUser Usuario autenticado, si lo hay.
     * @param Request $request Petición entrante.
     *
     * @return array<mixed>
     */
    private function resolveArguments(
        Route $route,
        array $parameters,
        ?array $authUser,
        Request $request
    ): array {
        $method = new ReflectionMethod(
            $route->controller,
            $route->action
        );

        $arguments = [];

        foreach ($method->getParameters() as $parameter) {

            $name = $parameter->getName();

            $type = $parameter->getType();

            if (
                $type instanceof ReflectionNamedType
                && $type->getName() === Request::class
            ) {
                $arguments[] = $request;

                continue;
            }

            if ($name === 'authUser') {

                if ($authUser === null) {
                    throw new RuntimeException(
                        "{$route->controller}::{$route->action}() espera el usuario "
                        . "autenticado, pero la ruta {$route->path} no exige autenticación."
                    );
                }

                $arguments[] = $authUser;

                continue;
            }

            if (array_key_exists($name, $parameters)) {

                $arguments[] = (
                    $type instanceof ReflectionNamedType
                    && $type->getName() === 'int'
                )
                    ? (int) $parameters[$name]
                    : $parameters[$name];

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new RuntimeException(
                "No se puede resolver \${$name} para "
                . "{$route->controller}::{$route->action}()."
            );
        }

        return $arguments;
    }
}
