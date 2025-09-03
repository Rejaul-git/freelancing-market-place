<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET POST , options");

header("Content-Type: application/json; charset=UTF-8");
require_once '../config/db.php';

// Get search query from GET request
if (!isset($_GET['q']) || trim($_GET['q']) === '') {
    echo json_encode([]);
    exit;
}

$searchTerm = "%" . $_GET['q'] . "%";

try {
    $sql = "SELECT id, name, slug, description, icon, parent_id, sort_order, status, created_at, image
            FROM categories
            WHERE name LIKE :search OR slug LIKE :search
            ORDER BY sort_order ASC
            LIMIT 10";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results, JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
