<?php

require_once
__DIR__ . "/../helpers/response.php";

class ServiceController {

    public function __construct(
        private ServiceService $service
    ){}

    public function index(): void {
        $services = $this->service->getServices();

        jsonResponse(
            true,
            $services
        );
    }
}