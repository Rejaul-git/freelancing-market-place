<?php
require_once 'config/db.php';

echo "Testing database connection and user data:\n\n";

try {
    // Test database connection
    echo "Database connection: SUCCESS\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table: EXISTS\n";
        
        // Check users in the table
        $stmt = $pdo->query("SELECT id, username, email, role FROM users");
        $users = $stmt->fetchAll();
        
        echo "Total users: " . count($users) . "\n\n";
        
        if (count($users) > 0) {
            echo "Users in database:\n";
            foreach ($users as $user) {
                echo "- ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}, Role: {$user['role']}\n";
            }
            
            // Test login with admin user
            echo "\nTesting login with admin@freelancebd.com:\n";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute(['admin@freelancebd.com']);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                echo "User found: " . $user['username'] . "\n";
                $password_check = password_verify('password', $user['password']);
                echo "Password verification: " . ($password_check ? "SUCCESS" : "FAILED") . "\n";
            } else {
                echo "User NOT found\n";
            }
        } else {
            echo "No users found in database\n";
        }
    } else {
        echo "Users table: NOT EXISTS\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
