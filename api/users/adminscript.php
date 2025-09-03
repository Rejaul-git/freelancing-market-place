<?php
// admin_create.php



require_once '../config/db.php'; // PDO connection


// ফর্ম ডেটা নাও (JSON বা Form-data উভয়েই চলবে)
$username = "karim";
$email = "karim@gmail";
$password = 123456;
$country = "bangladesh";
$role = "seller";
$phone = "01712345678";
$img = "https://example.com/profile.jpg";
$des = "I am a seller";


if (!$username || !$email || !$password) {
    echo json_encode(["status" => "error", "message" => "Username, email, and password are required"]);
    exit;
}

// Password hash
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, img, country, phone, des, role) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'admin')");
    $stmt->execute([
        $username,
        $email,
        $hashedPassword,
        $img,
        $country,
        $phone,
        $des
    ]);

    echo json_encode(["status" => "success", "message" => "Admin user created successfully"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "DB Error: " . $e->getMessage()]);
}
