<?php
// Simple script to check gigs in database
require_once 'config/db.php';

try {
    // Get all gigs from database
    $stmt = $pdo->prepare("SELECT * FROM gigs");
    $stmt->execute();
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total gigs found: " . count($gigs) . "\n";

    foreach ($gigs as $gig) {
        echo "ID: " . $gig['id'] . ", Title: " . $gig['title'] . ", Category: " . $gig['category'] . ", Status: " . $gig['status'] . "\n";
    }
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}
