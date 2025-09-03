<?php
require_once 'api/config/db.php';

// Update admin user's email and password
$email = 'admin@gmail.com';
$password = '12345';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = 1");
$stmt->execute([$email, $hashedPassword]);

echo "Admin user updated successfully.\n";
echo "Email: $email\n";
echo "Password: $password (hashed)\n";
