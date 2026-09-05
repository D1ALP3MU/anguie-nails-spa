<?php

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * ---------------------------------------------------------
 * Proyecto : Anguie Nails
 * Archivo  : Container.php
 *
 * Descripción:
 * Contenedor de dependencias mínimo. Construye una clase leyendo
 * los tipos de su constructor y resolviendo cada uno de forma
 * recursiva.
 *
 * Evita el cableado manual del enrutador, donde cada ruta repetía
 * la cadena repositorio -> servicio -> controlador. Ese cableado
 * a mano era también la causa de que fuera fácil olvidar un
 * middleware en una ruta.
 *
 * Las instancias se reutilizan durante la petición: un mismo
 * repositorio no se construye dos veces.
 * ---------------------------------------------------------
 */
class Container
{
    /**
     * Instancias ya resueltas, indexadas por nombre de clase.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Registra una instancia ya construida.
     *
     * Se usa para lo que el contenedor no puede fabricar solo,
     * como la conexión PDO.
     *
     * @param string $id Nombre de la clase o interfaz.
     * @param object $instance Instancia a reutilizar.
     *
     * @return void
     */
    public function bind(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * Obtiene una instancia de la clase solicitada.
     *
     * @param string $class Nombre completo de la clase.
     *
     * @return object
     */
    public function get(string $class): object
    {
        if (isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        $instance = $this->build($class);

        $this->instances[$class] = $instance;

        return $instance;
    }

    /**
     * Construye una clase resolviendo sus dependencias.
     *
     * @param string $class Nombre completo de la clase.
     *
     * @return object
     *
     * @throws RuntimeException Si alguna dependencia no se puede resolver.
     */
    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException(
                "No existe la clase {$class}."
            );
        }

        $reflection = new ReflectionClass($class);

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {

            $type = $parameter->getType();

            if (
                $type instanceof ReflectionNamedType
                && !$type->isBuiltin()
            ) {
                $arguments[] = $this->get($type->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();

                continue;
            }

            throw new RuntimeException(
                "No se puede resolver el parámetro \${$parameter->getName()} de {$class}."
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
