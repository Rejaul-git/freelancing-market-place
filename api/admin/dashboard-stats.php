<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];


    // Get total gigs
    $stmt = $pdo->query("SELECT COUNT(*) as total_gigs FROM gigs");
    $totalGigs = $stmt->fetch(PDO::FETCH_ASSOC)['total_gigs'];

    // Get total orders (assuming orders table exists)
    $stmt = $pdo->query("SELECT COUNT(*) as total_orders FROM orders");
    $totalOrders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'] ?? 0;

    // Get total revenue (assuming orders table with price column)
    $stmt = $pdo->query("SELECT SUM(platform_fee) as total_revenue FROM orders WHERE status = 'completed'");
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Get active users (users who logged in within last 30 days)
    $stmt = $pdo->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active'");
    $activeUsers = $stmt->fetch(PDO::FETCH_ASSOC)['active_users'] ?? 0;

    // Get pending orders
    $stmt = $pdo->query("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending'");
    $pendingOrders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'] ?? 0;

    $stats = [
        'total_users' => (int)$totalUsers,
        'total_gigs' => (int)$totalGigs,
        'total_orders' => (int)$totalOrders,
        'total_revenue' => (float)$totalRevenue,
        'active_users' => (int)$activeUsers,
        'pending_orders' => (int)$pendingOrders
    ];

    echo json_encode(['status' => 'success', 'data' => $stats]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
