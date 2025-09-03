<?php
session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

require_once '../config/db.php';


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT 
    od.id AS delivery_id,
    od.order_id,
    od.summary,
    od.files,
    od.created_at AS delivery_created_at,
    
    o.id AS order_id,
    o.gig_id,
    o.buyer_id,
    o.seller_id,
    o.gig_title,
    o.gig_image,
    o.price,
    o.status,
    o.deadline,
    o.delivery_date,
    o.requirements,
    o.delivery_note,
    o.reviewed,
    o.payment_status,
    o.created_at AS order_created_at,
    o.updated_at,
    o.platform_fee,
    u.username AS seller_name
FROM orders o
LEFT JOIN order_deliveries od ON o.id = od.order_id
LEFT JOIN users u ON o.seller_id = u.id
WHERE o.buyer_id = ? 
ORDER BY o.created_at DESC LIMIT 5;
 ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $orders]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
