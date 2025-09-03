<?php
require_once '../config/db.php';

try {
    // Add image column to categories table
    $sql = "ALTER TABLE categories ADD image VARCHAR(255) NULL AFTER icon";
    $pdo->exec($sql);
    echo "Image column added successfully to categories table.";
} catch (PDOException $e) {
    // Check if the error is because the column already exists
    if ($e->getCode() == '42S21') { // Column already exists
        echo "Image column already exists in categories table.";
    } else {
        echo "Error adding image column: " . $e->getMessage();
    }
}
