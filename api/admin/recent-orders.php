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
    // Create orders table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        gig_id INT,
        buyer_id INT,
        seller_id INT,
        gig_title VARCHAR(255),
        price DECIMAL(10,2),
        status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
        deadline DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (gig_id) REFERENCES gigs(id),
        FOREIGN KEY (buyer_id) REFERENCES users(id),
        FOREIGN KEY (seller_id) REFERENCES users(id)
    )");

    $stmt = $pdo->query("SELECT o.id, o.gig_title, o.price, o.status, o.created_at,
                        u.username as buyer_name
                        FROM orders o
                        LEFT JOIN users u ON o.buyer_id = u.id
                        ORDER BY o.created_at DESC 
                        LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $orders]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
