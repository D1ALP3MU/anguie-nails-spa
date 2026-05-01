<?php

function jsonResponse(
    bool $success,
    mixed $data = null,
    int $status = 200
): void {
    http_response_code($status);

    header(
        "Content-Type: application/json"
    );

    echo json_encode([
        "success" => $success,
        "data" => $data
    ]);
    
    exit;
}