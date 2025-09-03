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
    // Get total gigs for this seller
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_gigs FROM gigs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalGigs = $stmt->fetch(PDO::FETCH_ASSOC)['total_gigs'];

    // Get active orders for this seller
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_orders FROM orders WHERE seller_id = ? AND status IN ('pending', 'active')");
    $stmt->execute([$userId]);
    $activeOrders = $stmt->fetch(PDO::FETCH_ASSOC)['active_orders'] ?? 0;

    // Get total earnings for this seller
    $stmt = $pdo->prepare("SELECT SUM(price) as total_earnings FROM orders WHERE seller_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total_earnings'] ?? 0;

    // Get completed orders for this seller
    $stmt = $pdo->prepare("SELECT COUNT(*) as completed_orders FROM orders WHERE seller_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $completedOrders = $stmt->fetch(PDO::FETCH_ASSOC)['completed_orders'] ?? 0;

    // Get average rating (assuming reviews table exists)
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM reviews WHERE seller_id = ?");
    $stmt->execute([$userId]);
    $averageRating = $stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'] ?? 0;

    // Get total reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_reviews FROM reviews WHERE seller_id = ?");
    $stmt->execute([$userId]);
    $totalReviews = $stmt->fetch(PDO::FETCH_ASSOC)['total_reviews'] ?? 0;

    $stats = [
        'totalGigs' => (int)$totalGigs,
        'activeOrders' => (int)$activeOrders,
        'totalEarnings' => (float)$totalEarnings,
        'completedOrders' => (int)$completedOrders,
        'averageRating' => round((float)$averageRating, 1),
        'totalReviews' => (int)$totalReviews
    ];

    echo json_encode(['status' => 'success', 'data' => $stats]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
