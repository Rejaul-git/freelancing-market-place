<?php
// get_users.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once './config/db.php';

try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll();
    echo json_encode($users);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
