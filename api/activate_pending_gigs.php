<?php
require_once 'config/db.php';

try {
    // Update all pending gigs to active status
    $stmt = $pdo->prepare("UPDATE gigs SET status = 'active' WHERE status = 'pending'");
    $result = $stmt->execute();
    
    $rowCount = $stmt->rowCount();
    
    if ($rowCount > 0) {
        echo "Successfully updated $rowCount gig(s) from pending to active status.\n";
    } else {
        echo "No pending gigs found to update.\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>