<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'config/db.php';

try {
    // Get all gigs from database
    $stmt = $pdo->prepare("SELECT g.*, u.username as seller_name, u.img as seller_img,
                          COUNT(r.id) as reviews_count, AVG(r.rating) as average_rating
                          FROM gigs g 
                          LEFT JOIN users u ON g.user_id = u.id
                          LEFT JOIN reviews r ON g.id = r.gig_id
                          GROUP BY g.id
                          ORDER BY g.created_at DESC");
    $stmt->execute();
    $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>All Gigs from Database</h1>";
    echo "<p>Total gigs found: " . count($gigs) . "</p>";
    
    if (empty($gigs)) {
        echo "<p>No gigs found in the database.</p>";
    } else {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th>";
        echo "<th>Title</th>";
        echo "<th>Category</th>";
        echo "<th>Price</th>";
        echo "<th>Seller</th>";
        echo "<th>Status</th>";
        echo "<th>Created</th>";
        echo "<th>Reviews</th>";
        echo "<th>Rating</th>";
        echo "</tr>";
        
        foreach ($gigs as $gig) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($gig['id']) . "</td>";
            echo "<td>" . htmlspecialchars($gig['title']) . "</td>";
            echo "<td>" . htmlspecialchars($gig['category']) . "</td>";
            echo "<td>$" . htmlspecialchars($gig['price']) . "</td>";
            echo "<td>" . htmlspecialchars($gig['seller_name'] ?? 'Unknown') . "</td>";
            echo "<td>" . htmlspecialchars($gig['status']) . "</td>";
            echo "<td>" . htmlspecialchars($gig['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($gig['reviews_count'] ?? 0) . "</td>";
            echo "<td>" . number_format($gig['average_rating'] ?? 0, 1) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show detailed view of first gig
        if (count($gigs) > 0) {
            echo "<h2>Sample Gig Details (First Gig)</h2>";
            echo "<pre>";
            print_r($gigs[0]);
            echo "</pre>";
        }
    }
    
} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
