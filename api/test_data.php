<?php
// test_data.php - Script to create test data for FreelanceBD

require_once 'config/db.php';

try {
    // Create test users
    $users = [
        [
            'username' => 'admin_user',
            'email' => 'admin@freelancebd.com',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'country' => 'Bangladesh',
            'phone' => '+8801234567890',
            'desc' => 'System Administrator',
            'is_seller' => 0
        ],
        [
            'username' => 'john_seller',
            'email' => 'seller@gmail.com',
            'password' => password_hash('seller123', PASSWORD_DEFAULT),
            'role' => 'seller',
            'country' => 'USA',
            'phone' => '+1234567890',
            'desc' => 'Professional Web Developer',
            'is_seller' => 1
        ],
        [
            'username' => 'jane_buyer',
            'email' => 'buyer@gmail.com',
            'password' => password_hash('buyer123', PASSWORD_DEFAULT),
            'role' => 'buyer',
            'country' => 'Canada',
            'phone' => '+1987654321',
            'desc' => 'Business Owner looking for services',
            'is_seller' => 0
        ]
    ];

    // Insert users
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role, country, phone, `desc`, is_seller, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE email = email
    ");

    foreach ($users as $user) {
        $stmt->execute([
            $user['username'],
            $user['email'],
            $user['password'],
            $user['role'],
            $user['country'],
            $user['phone'],
            $user['desc'],
            $user['is_seller']
        ]);
        echo "Created user: " . $user['email'] . " (Role: " . $user['role'] . ")\n";
    }

    // Create test categories
    $categories = [
        ['title' => 'Web Development', 'desc' => 'Website and web application development services'],
        ['title' => 'Graphic Design', 'desc' => 'Logo, branding, and visual design services'],
        ['title' => 'Digital Marketing', 'desc' => 'SEO, social media, and online marketing services'],
        ['title' => 'Writing & Translation', 'desc' => 'Content writing and translation services'],
        ['title' => 'Video & Animation', 'desc' => 'Video editing and animation services']
    ];

    $stmt = $pdo->prepare("
        INSERT INTO categories (title, `desc`, created_at) 
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE title = title
    ");

    foreach ($categories as $category) {
        $stmt->execute([$category['title'], $category['desc']]);
        echo "Created category: " . $category['title'] . "\n";
    }

    // Get seller user ID for creating gigs
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'seller' LIMIT 1");
    $stmt->execute();
    $seller = $stmt->fetch();

    if ($seller) {
        // Get category IDs
        $stmt = $pdo->prepare("SELECT id, title FROM categories");
        $stmt->execute();
        $categories = $stmt->fetchAll();

        // Create test gigs
        $gigs = [
            [
                'title' => 'I will create a professional website for your business',
                'desc' => 'I will design and develop a modern, responsive website tailored to your business needs. This includes custom design, mobile optimization, and basic SEO setup.',
                'short_title' => 'Professional Website Development',
                'short_desc' => 'Custom business website with modern design',
                'delivery_time' => 7,
                'revision_number' => 3,
                'price' => 299.99,
                'features' => json_encode(['Responsive Design', 'SEO Optimized', 'Contact Forms', 'Social Media Integration']),
                'category_id' => $categories[0]['id'] // Web Development
            ],
            [
                'title' => 'I will design a stunning logo for your brand',
                'desc' => 'Professional logo design service with unlimited revisions until you are 100% satisfied. Includes multiple concepts, vector files, and brand guidelines.',
                'short_title' => 'Professional Logo Design',
                'short_desc' => 'Custom logo with unlimited revisions',
                'delivery_time' => 3,
                'revision_number' => 999,
                'price' => 89.99,
                'features' => json_encode(['Multiple Concepts', 'Vector Files', 'Brand Guidelines', 'Unlimited Revisions']),
                'category_id' => $categories[1]['id'] // Graphic Design
            ],
            [
                'title' => 'I will boost your website SEO ranking',
                'desc' => 'Complete SEO optimization service including keyword research, on-page optimization, technical SEO, and monthly reporting.',
                'short_title' => 'SEO Optimization Service',
                'short_desc' => 'Complete SEO package for better rankings',
                'delivery_time' => 14,
                'revision_number' => 2,
                'price' => 199.99,
                'features' => json_encode(['Keyword Research', 'On-Page SEO', 'Technical SEO', 'Monthly Reports']),
                'category_id' => $categories[2]['id'] // Digital Marketing
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO gigs (user_id, title, `desc`, short_title, short_desc, delivery_time, revision_number, price, features, category_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        foreach ($gigs as $gig) {
            $stmt->execute([
                $seller['id'],
                $gig['title'],
                $gig['desc'],
                $gig['short_title'],
                $gig['short_desc'],
                $gig['delivery_time'],
                $gig['revision_number'],
                $gig['price'],
                $gig['features'],
                $gig['category_id']
            ]);
            echo "Created gig: " . $gig['title'] . "\n";
        }
    }

    echo "\n✅ Test data created successfully!\n";
    echo "\nTest Login Credentials:\n";
    echo "Admin: admin@freelancebd.com / admin123\n";
    echo "Seller: seller@gmail.com / seller123\n";
    echo "Buyer: buyer@gmail.com / buyer123\n";

} catch (PDOException $e) {
    echo "❌ Error creating test data: " . $e->getMessage() . "\n";
}
?>
