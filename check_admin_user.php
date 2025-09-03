<?php
require_once 'api/config/db.php';

// Fetch admin user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Admin user found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Password Hash: " . $user['password'] . "\n";
    echo "Role: " . $user['role'] . "\n";
} else {
    echo "Admin user not found.\n";
}
