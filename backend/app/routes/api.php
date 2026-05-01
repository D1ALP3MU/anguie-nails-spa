<?php

require_once __DIR__ . "/../config/database.php";

require_once __DIR__ . "/../repositories/ServiceRepository.php";

require_once __DIR__ . "/../services/ServiceService.php";

require_once __DIR__ . "/../controllers/ServiceController.php";

echo "STEP 1 <br>";

$database = new Database();

echo "STEP 2 <br>";

$pdo = $database->connect();

echo "STEP 3 <br>";

$repository = new ServiceRepository($pdo);

echo "STEP 4 <br>";

$service = new ServiceService($repository);

echo "STEP 5 <br>";

$controller = new ServiceController($service);

echo "STEP 6 <br>";

$controller->index();

echo "STEP 7";

// require_once
// __DIR__ . "/../config/database.php";

// require_once
// __DIR__ . "/../repositories/ServiceRepository.php";

// require_once
// __DIR__ . "/../services/ServiceService.php";

// require_once
// __DIR__ . "/../controllers/ServiceController.php";

// $database = new Database();

// $db = $database->connect();

// $repository = new ServiceRepository($db);

// $service = new ServiceService($repository);

// $controller = new ServiceController($service);

// $method = $_SERVER["REQUEST_METHOD"];

// $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);


// //SERVICES
// if ($uri === "/api/services" && $method === "GET") {
//     $controller->index();
// }