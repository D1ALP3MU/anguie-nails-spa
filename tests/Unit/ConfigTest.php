<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * La plantilla de configuración.
 *
 * El README indica copiar `.env.example` a `.env`, así que una
 * plantilla que no se pueda leer deja el proyecto sin arrancar en la
 * primera instalación: `config/env.php` termina el proceso con
 * «FAILED TO PARSE ENV FILE».
 *
 * Pasó de verdad: los comentarios estaban escritos con `#`, que el
 * analizador INI de PHP no reconoce —solo acepta `;`—, y una línea
 * con comillas rompía el archivo entero.
 */
class ConfigTest extends TestCase
{
    /**
     * Variables sin las que la aplicación no funciona.
     */
    private const REQUIRED = [
        'DB_HOST',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'JWT_SECRET',
        'JWT_EXPIRE',
        'APP_TIMEZONE',
        'APP_ENV'
    ];

    private function templatePath(): string
    {
        return dirname(__DIR__, 2) . '/.env.example';
    }

    #[Test]
    public function la_plantilla_existe(): void
    {
        $this->assertFileExists(
            $this->templatePath(),
            'El README indica copiarla para instalar el proyecto.'
        );
    }

    #[Test]
    public function la_plantilla_se_puede_analizar(): void
    {
        $this->assertIsArray(
            @parse_ini_file($this->templatePath()),
            'config/env.php usa parse_ini_file: si falla, la aplicación '
            . 'no arranca. Los comentarios deben empezar por ";", no por "#".'
        );
    }

    #[Test]
    public function la_plantilla_trae_todas_las_variables(): void
    {
        $claves = array_keys(parse_ini_file($this->templatePath()));

        foreach (self::REQUIRED as $variable) {
            $this->assertContains(
                $variable,
                $claves,
                "Falta {$variable} en .env.example."
            );
        }
    }

    #[Test]
    public function la_plantilla_no_trae_credenciales_reales(): void
    {
        // Es un archivo versionado: lo que entre aquí queda publicado.
        $valores = parse_ini_file($this->templatePath());

        $this->assertStringContainsString(
            'cambiar',
            $valores['JWT_SECRET'],
            'JWT_SECRET debe ser un marcador de posición evidente.'
        );
    }

    #[Test]
    public function la_zona_horaria_por_defecto_es_valida(): void
    {
        $valores = parse_ini_file($this->templatePath());

        $this->assertContains(
            $valores['APP_TIMEZONE'],
            timezone_identifiers_list(),
            'APP_TIMEZONE debe ser un identificador que PHP reconozca.'
        );
    }
}
