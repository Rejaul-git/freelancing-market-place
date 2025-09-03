<?php
// Simulate the API request for fetching gigs with different parameters
require_once 'config/db.php';

// Function to simulate the API logic
function fetchGigs($category = '', $status = 'active') {
    global $pdo;
    
    $whereConditions = [];
    $params = [];
    
    // Status filter
    if ($status !== 'all') {
        // Updated logic to also show pending gigs
        if ($status === 'active') {
            $whereConditions[] = "(g.status = ? OR g.status = ?)";
            $params[] = $status;
            $params[] = 'pending';
        } else {
            $whereConditions[] = "g.status = ?";
            $params[] = $status;
        }
    }
    
    // Category filter with typo handling
    if ($category) {
        $categoryParam = $category;
        
        // Fix common typos in category names
        if ($categoryParam === 'logo design') {
            // Allow matching with 'logo desigin' (typo in DB)
            $whereConditions[] = "(g.category = ? OR g.category = ?)";
            $params[] = $categoryParam;
            $params[] = 'logo desigin';
        } else {
            $whereConditions[] = "g.category = ?";
            $params[] = $categoryParam;
        }
    }
    
    $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    try {
        // Get gigs with user info
        $stmt = $pdo->prepare("SELECT g.*, u.username as seller_name, u.img as seller_img
                             FROM gigs g
                             JOIN users u ON g.user_id = u.id
                             $whereClause
                             ORDER BY g.created_at DESC");
        $stmt->execute($params);
        $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse images JSON for each gig
        foreach ($gigs as &$gig) {
            $gig['images'] = json_decode($gig['images'], true);
        }
        
        return $gigs;
    } catch (PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

// Test cases
echo "Testing gig fetch functionality:\n\n";

echo "1. Fetch all gigs (active or pending):\n";
$gigs = fetchGigs();
echo "Found " . count($gigs) . " gigs\n";
foreach ($gigs as $gig) {
    echo "  - ID: {$gig['id']}, Title: {$gig['title']}, Category: {$gig['category']}, Status: {$gig['status']}\n";
}

echo "\n2. Fetch gigs with category 'web development':\n";
$gigs = fetchGigs('web development');
echo "Found " . count($gigs) . " gigs\n";

echo "\n3. Fetch gigs with category 'logo design' (testing typo handling):\n";
$gigs = fetchGigs('logo design');
echo "Found " . count($gigs) . " gigs\n";
if (count($gigs) > 0) {
    foreach ($gigs as $gig) {
        echo "  - ID: {$gig['id']}, Title: {$gig['title']}, Category: {$gig['category']}, Status: {$gig['status']}\n";
    }
}

echo "\n4. Fetch all gigs with status 'all':\n";
$gigs = fetchGigs('', 'all');
echo "Found " . count($gigs) . " gigs\n";

echo "\nTest completed.\n";
?>