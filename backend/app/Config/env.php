<?php

$envPath = realpath(__DIR__ . "/../../../.env");

if (!$envPath) {
    die("ENV FILE NOT FOUND");
}

$env = parse_ini_file($envPath);

if (!$env) {
    die("FAILED TO PARSE ENV FILE");
}

foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}