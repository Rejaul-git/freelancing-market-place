<?php
// db.php

$host = 'localhost';
$dbname = 'brainsto_complete_database_schema';
$username = 'brainsto_marketplace'; // XAMPP বা Localhost হলে সাধারণত root হয়
$password = 'RIg?.WZM[ev%75gQ';     // XAMPP এর ক্ষেত্রে default password ফাঁকা থাকে

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);

    // PDO Error Mode set করে নিচ্ছি Exception হিসেবে
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Optional: Default fetch mode associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
