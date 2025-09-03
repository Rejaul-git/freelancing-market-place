<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'seller') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$gigId = intval($data['id'] ?? 0);
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$price = floatval($data['price'] ?? 0);
$delivery_time = trim($data['delivery_time'] ?? '');
$image_url = trim($data['image_url'] ?? '');

if ($gigId <= 0 || empty($title) || empty($description) || $price <= 0 || empty($delivery_time)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Check ownership
$stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ? AND user_id = ?");
$stmt->execute([$gigId, $_SESSION['user']['id']]);
$gig = $stmt->fetch();

if (!$gig) {
    echo json_encode(['status' => 'error', 'message' => 'Gig not found or unauthorized']);
    exit;
}

// Update gig
$stmt = $pdo->prepare("UPDATE gigs SET title = ?, description = ?, price = ?, delivery_time = ?, image_url = ? WHERE id = ?");
$updated = $stmt->execute([$title, $description, $price, $delivery_time, $image_url, $gigId]);

if ($updated) {
    echo json_encode(['status' => 'success', 'message' => 'Gig updated']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed']);
}
