<?php
require_once 'api/config/db.php';

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($users) {
    echo "Users found:\n";
    foreach ($users as $user) {
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Password Hash: " . $user['password'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "------------------------\n";
    }
} else {
    echo "No users found.\n";
}
