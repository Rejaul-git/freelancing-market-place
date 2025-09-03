<?php
require_once '../config/cors.php'; // CORS headers
require_once '../config/db.php';   // $pdo PDO connection

$uploadDir = __DIR__ . "/uploads/";

// Create uploads folder if not exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Get POST data
$summary = $_POST['summary'] ?? '';
$order_id = $_POST['order_id'] ?? '';

// Validate inputs
if (empty($summary) || empty($order_id) || empty($_FILES['files']['name'][0])) {
    echo json_encode([
        "status" => "error",
        "message" => "Summary, order_id and files are required"
    ]);
    exit;
}

$uploaded = [];

// Handle multiple files
foreach ($_FILES['files']['name'] as $index => $fileName) {
    $tmpName = $_FILES['files']['tmp_name'][$index];
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = uniqid() . "." . $ext; // avoid conflicts
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($tmpName, $targetPath)) {
        $uploaded[] = $newFileName;
    }
}

// Insert into DB using PDO
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO order_deliveries (order_id, summary, files) VALUES (:order_id, :summary, :files)");
    $stmt->execute([
        ":order_id" => $order_id,
        ":summary" => $summary,
        ":files" => json_encode($uploaded)
    ]);
    
    // Update order status to delivered
    $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered', delivery_date = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$order_id]);
    
    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Order submitted successfully",
        "order_id" => $order_id,
        "summary" => $summary,
        "uploaded_files" => $uploaded
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        "status" => "error",
        "message" => "Database insert failed: " . $e->getMessage()
    ]);
}
