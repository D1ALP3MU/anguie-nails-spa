<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;

    public function __construct() {
        $this->host = $_ENV["DB_HOST"];
        $this->db_name = $_ENV["DB_NAME"];
        $this->username = $_ENV["DB_USER"];
        $this->password = $_ENV["DB_PASS"]; 
    }

    public function connect(): PDO {

        try {
            $pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password
            );

            $pdo->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );

            $pdo->setAttribute(
                PDO::ATTR_EMULATE_PREPARES,
                false
            );

            return $pdo;
        }
        catch (PDOException $error) {

            // El detalle del error se registra en el servidor.
            // NUNCA se devuelve al cliente: contiene host,
            // usuario y en ocasiones la contraseña de la base de datos.
            error_log(
                'Fallo de conexión a la base de datos: '
                . $error->getMessage()
            );

            http_response_code(500);

            header('Content-Type: application/json; charset=utf-8');

            die(
                json_encode([
                    "success" => false,
                    "message" => "Ha ocurrido un error interno del servidor."
                ])
            );
        }
    }
}