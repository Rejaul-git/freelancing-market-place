<?php
require_once 'config/db.php';

try {
    // Check all gigs and their statuses
    $stmt = $pdo->prepare("SELECT id, title, category, status FROM gigs");
    $stmt->execute();
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "All gigs in database:\n";
    foreach ($gigs as $gig) {
        echo "ID: {$gig['id']}, Title: {$gig['title']}, Category: {$gig['category']}, Status: {$gig['status']}\n";
    }
    
    // Check categories
    $stmt = $pdo->prepare("SELECT id, name FROM categories");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAll categories in database:\n";
    foreach ($categories as $category) {
        echo "ID: {$category['id']}, Name: {$category['name']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>