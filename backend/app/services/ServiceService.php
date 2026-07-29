<?php

namespace App\Services;

use App\Repositories\ServiceRepository;

#use PDO;

class ServiceService {

    public function __construct(
        private ServiceRepository $repository
    ){}

    public function getServices(): array {
        return $this->repository->findAll();
    }
}