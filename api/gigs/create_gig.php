<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
require_once '../config/db.php';

// File upload handler
function uploadFile($file, $folder = "uploads/")
{
    if (!file_exists($folder)) {
        mkdir($folder, 0777, true);
    }

    $filename = time() . '_' . basename($file['name']);
    $targetFile = $folder . $filename;

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        return $targetFile;
    } else {
        return null;
    }
}

// Get input fields from POST
$title = $_POST['title'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';
$shortTitle = $_POST['shortTitle'] ?? '';
$shortDescription = $_POST['shortDescription'] ?? '';
$deliveryTime = $_POST['deliveryTime'] ?? 0;
$revisionNumber = $_POST['revisionNumber'] ?? 0;
$price = $_POST['price'] ?? 0;
$features = isset($_POST['features']) ? json_encode($_POST['features']) : json_encode([]);
$user_id = $_POST['user_id'] ?? 1; // Default user_id for testing

// Upload cover image
$cover = '';
if (isset($_FILES['cover'])) {
    $cover = uploadFile($_FILES['cover']);
}

// Upload multiple images
$images = [];
if (!empty($_FILES['images']['name'][0])) {
    foreach ($_FILES['images']['name'] as $key => $name) {
        $file = [
            'name' => $name,
            'type' => $_FILES['images']['type'][$key],
            'tmp_name' => $_FILES['images']['tmp_name'][$key],
            'error' => $_FILES['images']['error'][$key],
            'size' => $_FILES['images']['size'][$key]
        ];
        $uploaded = uploadFile($file);
        if ($uploaded) {
            $images[] = $uploaded;
        }
    }
}
$images_json = json_encode($images);

// Insert into DB
$sql = "INSERT INTO gigs (
    user_id, title, description, category, price, cover, images,
    short_title, short_description, delivery_time, revision_number, features
) VALUES (
    :user_id, :title, :description, :category, :price, :cover, :images,
    :short_title, :short_description, :delivery_time, :revision_number, :features
)";
$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $title,
        ':description' => $description,
        ':category' => $category,
        ':price' => $price,
        ':cover' => $cover,
        ':images' => $images_json,
        ':short_title' => $shortTitle,
        ':short_description' => $shortDescription,
        ':delivery_time' => $deliveryTime,
        ':revision_number' => $revisionNumber,
        ':features' => $features
    ]);

    echo json_encode(['success' => true, 'message' => 'Gig added successfully.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to insert gig: ' . $e->getMessage()]);
}
