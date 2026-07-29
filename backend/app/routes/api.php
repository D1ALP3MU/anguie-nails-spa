<?php

use App\Config\Database;
use App\Controllers\ServiceController;
use App\Repositories\ServiceRepository;
use App\Services\ServiceService;

require_once __DIR__ . "/../config/database.php";

$database = new Database();

$pdo = $database->connect();

$repository = new ServiceRepository($pdo);

$service = new ServiceService($repository);

$controller = new ServiceController($service);

$controller->index();