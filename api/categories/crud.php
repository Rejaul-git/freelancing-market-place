<?php
session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Handle file uploads
function handleFileUpload($file)
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF files are allowed.');
    }

    if ($file['size'] > 2 * 1024 * 1024) { // 2MB limit
        throw new Exception('File size exceeds 2MB limit.');
    }

    $uploadDir = '../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return 'uploads/' . $newFileName;
    } else {
        throw new Exception('Failed to move uploaded file.');
    }
}

// Check authentication for protected operations
function checkAuth($requiredRole = null)
{
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    if ($requiredRole && $_SESSION['user']['role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Insufficient permissions']);
        exit;
    }

    return $_SESSION['user'];
}

try {
    switch ($method) {
        case 'GET':
            // Read categories (public endpoint)
            if (isset($_GET['id'])) {
                // Get single category
                $stmt = $pdo->prepare("SELECT c.*, COUNT(g.id) as gig_count
                                     FROM categories c
                                     LEFT JOIN gigs g ON c.name = g.category AND g.status = 'active'
                                     WHERE c.id = ?
                                     GROUP BY c.id");
                $stmt->execute([$_GET['id']]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($category) {
                    echo json_encode(['status' => 'success', 'data' => $category]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Category not found']);
                }
            } else {
                // Get all categories with filters
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $search = $_GET['search'] ?? '';
                $status = $_GET['status'] ?? 'active';
                $with_gig_count = $_GET['with_gig_count'] ?? true;

                $offset = ($page - 1) * $limit;

                $whereConditions = [];
                $params = [];

                if ($search) {
                    $whereConditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                if ($status !== 'all') {
                    $whereConditions[] = "c.status = ?";
                    $params[] = $status;
                }

                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM categories c $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Get categories
                if ($with_gig_count) {
                    $stmt = $pdo->prepare("SELECT c.*, COUNT(g.id) as gig_count
                                         FROM categories c
                                         LEFT JOIN gigs g ON c.name = g.category AND g.status = 'active'
                                         $whereClause
                                         GROUP BY c.id
                                         ORDER BY c.sort_order ASC, c.name ASC
                                         LIMIT $limit OFFSET $offset");
                } else {
                    $stmt = $pdo->prepare("SELECT c.*
                                         FROM categories c
                                         $whereClause
                                         ORDER BY c.sort_order ASC, c.name ASC
                                         LIMIT $limit OFFSET $offset");
                }
                $stmt->execute($params);
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'data' => $categories,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            }
            break;

        case 'POST':
            // Create category (admin only)
            $currentUser = checkAuth('admin');

            // Handle both JSON and form data
            $input = [];
            if (!empty($_POST)) {
                $input = $_POST;
            } else {
                $input = json_decode(file_get_contents('php://input'), true);
            }

            $requiredFields = ['name'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }

            // Check if category name already exists
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$input['name']]);
            $existingCategory = $stmt->fetch();

            if ($existingCategory) {
                echo json_encode(['status' => 'error', 'message' => 'Category name already exists']);
                exit;
            }

            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $imagePath = handleFileUpload($_FILES['image']);
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => 'Image upload failed: ' . $e->getMessage()]);
                    exit;
                }
            }

            // Generate slug if not provided
            $slug = $input['slug'] ?? null;
            if (empty($slug)) {
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $input['name'])));
            }

            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon, image, parent_id, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['name'],
                $slug,
                $input['description'] ?? null,
                $input['icon'] ?? null,
                $imagePath,
                $input['parent_id'] ?? null,
                $input['sort_order'] ?? 0,
                $input['status'] ?? 'active'
            ]);

            if ($result) {
                $categoryId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Category created successfully', 'id' => $categoryId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create category']);
            }
            break;

        case 'PUT':
            // Update category (admin only)
            // Handle both JSON and form data
            $input = [];
            if (!empty($_POST)) {
                $input = $_POST;
            } else {
                $input = json_decode(file_get_contents('php://input'), true);
            }

            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
                exit;
            }

            $currentUser = checkAuth('admin');

            // Check if category exists
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$input['id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                echo json_encode(['status' => 'error', 'message' => 'Category not found']);
                exit;
            }

            $updateFields = [];
            $params = [];

            $allowedFields = ['name', 'slug', 'description', 'icon', 'parent_id', 'sort_order', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    // Check if name already exists (excluding current category)
                    if ($field === 'name' && $input[$field] !== $category['name']) {
                        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
                        $stmt->execute([$input[$field], $input['id']]);
                        $existingCategory = $stmt->fetch();

                        if ($existingCategory) {
                            echo json_encode(['status' => 'error', 'message' => 'Category name already exists']);
                            exit;
                        }
                    }

                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $imagePath = handleFileUpload($_FILES['image']);
                    $updateFields[] = "image = ?";
                    $params[] = $imagePath;
                } catch (Exception $e) {
                    echo json_encode(['status' => 'error', 'message' => 'Image upload failed: ' . $e->getMessage()]);
                    exit;
                }
            }

            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }

            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $input['id'];

            $stmt = $pdo->prepare("UPDATE categories SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Category updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update category']);
            }
            break;

        case 'DELETE':
            // Delete category (admin only)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
                exit;
            }

            $currentUser = checkAuth('admin');

            // Check if category exists
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$input['id']]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                echo json_encode(['status' => 'error', 'message' => 'Category not found']);
                exit;
            }

            // Check if category has gigs
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM gigs WHERE category = ?");
            $stmt->execute([$category['name']]);
            $gigCount = $stmt->fetchColumn();

            if ($gigCount > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete category with existing gigs. Please move or delete gigs first.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $result = $stmt->execute([$input['id']]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete category']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
