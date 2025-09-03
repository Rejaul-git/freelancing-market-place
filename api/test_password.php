<?php
// Test script to verify password hash
$password = "password";
$hash_from_db = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password verification:\n";
echo "Password: " . $password . "\n";
echo "Hash: " . $hash_from_db . "\n";
echo "Verification result: " . (password_verify($password, $hash_from_db) ? "TRUE" : "FALSE") . "\n";

// Also generate a new hash for comparison
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "\nNew hash for 'password': " . $new_hash . "\n";
echo "New hash verification: " . (password_verify($password, $new_hash) ? "TRUE" : "FALSE") . "\n";
?>
