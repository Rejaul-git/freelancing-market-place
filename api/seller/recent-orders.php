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
    $stmt = $pdo->prepare("SELECT o.id, o.gig_title, o.price, o.status, o.deadline, o.created_at,
                          u.username as buyer_name
                          FROM orders o
                          LEFT JOIN users u ON o.buyer_id = u.id
                          WHERE o.seller_id = ?
                          ORDER BY o.created_at DESC 
                          LIMIT 10");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $orders]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
