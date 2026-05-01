<?php

class Database {
    private string $host = "localhost";
    private string $db_name = "spa_db";
    private string $username = "root";
    private string $password = "admindap*";
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