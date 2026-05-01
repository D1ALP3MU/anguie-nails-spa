<?php

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
            return $pdo;
        }
        catch (PDOException $error) {
            http_response_code(500);
            die(

                json_encode([
                    "success" => false,
                    "message" => "Database connection failed",
                    "error" => $error->getMessage()
                ])
            );
        }
    }
}