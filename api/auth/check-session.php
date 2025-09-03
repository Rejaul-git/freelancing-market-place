<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if (isset($_SESSION['user'])) {
    echo json_encode([
        'status' => 'success',
        'user' => $_SESSION['user']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Not logged in'
    ]);
}
?>
