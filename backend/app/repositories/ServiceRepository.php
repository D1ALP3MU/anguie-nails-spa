<?php

class ServiceRepository {

    public function __construct(
        private PDO $db
    ) {}

    public function findAll(): array {

        $query = "
            SELECT
                id_servicio,
                nombre,
                duracion,
                precio
            FROM servicios
            WHERE activo = 1        
        ";

        $statement = $this->db->prepare($query);

        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}