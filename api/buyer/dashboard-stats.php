<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once '../config/db.php';

// Check if user is buyer
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    // Get active orders for this buyer
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_orders FROM orders WHERE buyer_id = ? AND status IN ('pending', 'active')");
    $stmt->execute([$userId]);
    $activeOrders = $stmt->fetch(PDO::FETCH_ASSOC)['active_orders'] ?? 0;

    // Get completed orders for this buyer
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM orders WHERE buyer_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['completed_orders'] ?? 0;

    // Get total spent by this buyer
    $stmt = $pdo->prepare("SELECT SUM(price) as total_spent FROM orders WHERE buyer_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalSpent = $stmt->fetch(PDO::FETCH_ASSOC)['total_spent'] ?? 0;

    // Get saved gigs (assuming favorites table exists)
    $stmt = $pdo->prepare("SELECT COUNT(*) as saved_gigs FROM favorites WHERE user_id = ?");
    $stmt->execute([$userId]);
    $savedGigs = $stmt->fetch(PDO::FETCH_ASSOC)['saved_gigs'] ?? 0;

    // Get unread messages (assuming messages table exists)
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_messages FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadMessages = $stmt->fetch(PDO::FETCH_ASSOC)['unread_messages'] ?? 0;

    // Get pending reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_reviews FROM orders WHERE buyer_id = ? AND status = 'completed' AND reviewed = 0");
    $stmt->execute([$userId]);
    $pendingReviews = $stmt->fetch(PDO::FETCH_ASSOC)['pending_reviews'] ?? 0;

    $stats = [
        'activeOrders' => (int)$activeOrders,
        'completedOrders' => (int)$completedOrders,
        'totalSpent' => (float)$totalSpent,
        'savedGigs' => (int)$savedGigs,
        'unreadMessages' => (int)$unreadMessages,
        'pendingReviews' => (int)$pendingReviews
    ];

    echo json_encode(['status' => 'success', 'data' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
