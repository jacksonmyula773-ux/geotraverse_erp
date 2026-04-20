<?php
// backend/includes/response.php

function sendSuccess($data = null, $message = "Success", $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sendError($message = "Error", $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "timestamp" => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>