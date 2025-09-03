<?php
session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Validate input
if (!isset($input['order_id']) || empty($input['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
    exit;
}

$orderId = $input['order_id'];
$userId = $_SESSION['user']['id'];

try {
    // Check if order exists and belongs to the buyer
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied']);
        exit;
    }

    // Check if order is already completed
    if ($order['status'] === 'completed') {
        echo json_encode(['status' => 'error', 'message' => 'Order is already completed']);
        exit;
    }

    // Update order status to completed
    $stmt = $pdo->prepare("UPDATE orders SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$orderId]);

    if ($result) {
        // Update buyer stats
        echo json_encode(['status' => 'success', 'message' => 'Order confirmed successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to confirm order']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>