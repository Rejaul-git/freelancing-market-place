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

if ($gigId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid gig ID']);
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

// Delete gig
$stmt = $pdo->prepare("DELETE FROM gigs WHERE id = ?");
$deleted = $stmt->execute([$gigId]);

if ($deleted) {
    echo json_encode(['status' => 'success', 'message' => 'Gig deleted']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Deletion failed']);
}
