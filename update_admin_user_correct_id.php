<?php
require_once 'api/config/db.php';

// Update admin user's email and password
$email = 'admin@gmail.com';
$password = '12345';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Update user with ID 7
$stmt = $pdo->prepare("UPDATE users SET email = ?, password = ? WHERE id = 7");
$stmt->execute([$email, $hashedPassword]);

echo "Admin user (ID 7) updated successfully.\n";
echo "Email: $email\n";
echo "Password: $password (hashed)\n";
