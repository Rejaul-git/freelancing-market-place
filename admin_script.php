<?php
// Database config
$host = 'localhost';
$db   = 'brainsto_complete_database_schema';
$user = 'brainsto_marketplace'; // আপনার MySQL ইউজারনেম
$pass = 'RIg?.WZM[ev%75gQ';     // আপনার MySQL পাসওয়ার্ড
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO সেটিংস
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Error ধরবে
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // ডাটাবেজ কানেকশন
    $pdo = new PDO($dsn, $user, $pass, $options);

    // ইউজার ইনসার্ট করার জন্য ডেটা
    $username = 'admin';
    $email = 'admin@.com';
    $plainPassword = '12345'; // এখান থেকে হ্যাশ করব
    $password = password_hash($plainPassword, PASSWORD_DEFAULT);
    $role = 'admin';
    $country = 'Bangladesh';
    $phone = '+8801234567890';
    $des = 'System Administrator';

    // ইনসার্ট SQL
    $sql = "INSERT INTO users (username, email, password, role, country, phone, des, created_at)
            VALUES (:username, :email, :password, :role, :country, :phone, :des, NOW())
            ON DUPLICATE KEY UPDATE email = email"; // ডুপ্লিকেট ইমেইল হলে কিছু করবে না

    // প্রিপেয়ার ও এক্সিকিউট
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':role' => $role,
        ':country' => $country,
        ':phone' => $phone,
        ':des' => $des,
    ]);

    echo "✅ Admin user inserted successfully.";
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
}
