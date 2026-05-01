<?php

class ServiceService {

    public function __construct(
        private ServiceRepository $repository
    ){}

    public function getServices(): array {
        return $this->repository->findAll();
    }
}