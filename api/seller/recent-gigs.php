<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once '../config/db.php';

// Check if user is seller
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT id, title, price, status, created_at
                          FROM gigs 
                          WHERE user_id = ?
                          ORDER BY created_at DESC 
                          LIMIT 10");
    $stmt->execute([$userId]);
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $gigs]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
