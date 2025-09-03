<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT * FROM gigs WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['status' => 'success', 'data' => $gigs]);
