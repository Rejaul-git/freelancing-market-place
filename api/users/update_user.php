<?php
// update_user.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type");

require_once './config/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->id) || !isset($data->name) || !isset($data->email) || !isset($data->role)) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}
$id = $data->id;
$name = htmlspecialchars(trim($data->name));
$email = htmlspecialchars(trim($data->email));
$role = htmlspecialchars(trim($data->role));

try {
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$name, $email, $role, $id]);

    echo json_encode(["status" => "success", "message" => "User updated successfully"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
