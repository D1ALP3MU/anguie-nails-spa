<?php

require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/../repositories/ServiceRepository.php";

require_once __DIR__ . "/../services/ServiceService.php";

require_once __DIR__ . "/../controllers/ServiceController.php";

$database = new Database();

$pdo = $database->connect();

$repository = new ServiceRepository($pdo);

$service = new ServiceService($repository);

$controller = new ServiceController($service);

$controller->index();