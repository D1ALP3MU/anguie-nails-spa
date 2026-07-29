<?php

namespace App\Controllers;

use App\Services\ServiceService;
use App\Responses\Response;

class ServiceController {

    public function __construct(
        private ServiceService $service
    ){}

    public function index(): void {

        $services = $this->service->getServices();

        Response::success($services);
        
    }
}