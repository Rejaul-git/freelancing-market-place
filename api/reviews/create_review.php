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
$requiredFields = ['order_id', 'rating'];
foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
        exit;
    }
}

$orderId = $input['order_id'];
$rating = $input['rating'];
$reviewText = $input['review_text'] ?? '';
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

    // Check if order is completed
    if ($order['status'] !== 'completed') {
        echo json_encode(['status' => 'error', 'message' => 'Order must be completed to submit a review']);
        exit;
    }

    // Check if review already exists for this order
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $existingReview = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingReview) {
        echo json_encode(['status' => 'error', 'message' => 'Review already submitted for this order']);
        exit;
    }

    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (order_id, gig_id, buyer_id, seller_id, rating, review_text) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $orderId,
        $order['gig_id'],
        $userId,
        $order['seller_id'],
        $rating,
        $reviewText
    ]);

    if ($result) {
        // Mark order as reviewed
        $stmt = $pdo->prepare("UPDATE orders SET reviewed = 1 WHERE id = ?");
        $stmt->execute([$orderId]);
        
        echo json_encode(['status' => 'success', 'message' => 'Review submitted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to submit review']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>