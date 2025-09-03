<?php
session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

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
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT g.*, u.username as seller_name, u.img as seller_img
                                     FROM gigs g
                                     JOIN users u ON g.user_id = u.id
                                     WHERE g.id = ? AND (g.status = 'active' OR g.status = 'pending')");
                $stmt->execute([$_GET['id']]);
                $gig = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($gig) {
                    $gig['images'] = json_decode($gig['images'], true);
                    echo json_encode(['status' => 'success', 'data' => $gig]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Gig not found']);
                }
            } else {
                $page = (int)($_GET['page'] ?? 1);
                $limit = (int)($_GET['limit'] ?? 20);
                $search = $_GET['search'] ?? '';
                $category = $_GET['category'] ?? '';
                $subcategory = $_GET['subcategory'] ?? '';
                $minPrice = $_GET['min_price'] ?? '';
                $maxPrice = $_GET['max_price'] ?? '';
                $sortBy = $_GET['sort_by'] ?? 'newest';
                $status = $_GET['status'] ?? 'active';
                $userId = $_GET['user_id'] ?? '';

                $offset = ($page - 1) * $limit;

                $whereConditions = [];
                $params = [];

                // Filter by status
                if ($status !== 'all') {
                    if ($status === 'active') {
                        $whereConditions[] = "(g.status = ? OR g.status = ?)";
                        $params[] = $status;
                        $params[] = 'pending';
                    } else {
                        $whereConditions[] = "g.status = ?";
                        $params[] = $status;
                    }
                }

                // Filter by user_id if provided
                if ($userId !== '') {
                    $whereConditions[] = "g.user_id = ?";
                    $params[] = $userId;
                }

                if ($search) {
                    $whereConditions[] = "(g.title LIKE ? OR g.description LIKE ? OR g.short_description LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                if ($category) {
                    if ($category === 'logo design') {
                        $whereConditions[] = "(g.category = ? OR g.category = ?)";
                        $params[] = $category;
                        $params[] = 'logo desigin';
                    } else {
                        $whereConditions[] = "g.category = ?";
                        $params[] = $category;
                    }
                }

                if ($subcategory) {
                    $whereConditions[] = "g.subcategory = ?";
                    $params[] = $subcategory;
                }

                if (is_numeric($minPrice)) {
                    $whereConditions[] = "g.price >= ?";
                    $params[] = $minPrice;
                }

                if (is_numeric($maxPrice)) {
                    $whereConditions[] = "g.price <= ?";
                    $params[] = $maxPrice;
                }

                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                $orderBy = 'g.created_at DESC';
                switch ($sortBy) {
                    case 'price_low':
                        $orderBy = 'g.price ASC';
                        break;
                    case 'price_high':
                        $orderBy = 'g.price DESC';
                        break;
                    case 'rating':
                        $orderBy = 'g.total_stars DESC';
                        break;
                    case 'sales':
                        $orderBy = 'g.sales DESC';
                        break;
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM gigs g $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT g.*, u.username as seller_name, u.img as seller_img
                                     FROM gigs g
                                     JOIN users u ON g.user_id = u.id
                                     $whereClause
                                     ORDER BY $orderBy
                                     LIMIT $limit OFFSET $offset");
                $stmt->execute($params);
                $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($gigs as &$gig) {
                    $gig['images'] = json_decode($gig['images'], true);
                }

                echo json_encode([
                    'status' => 'success',
                    'data' => $gigs,
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
            $currentUser = checkAuth('seller');
            $requiredFields = ['title', 'description', 'category', 'price', 'short_title', 'short_description', 'delivery_time', 'revision_number'];

            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }

            if (!is_numeric($input['price']) || $input['price'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Price must be a positive number']);
                exit;
            }

            if (!is_numeric($input['delivery_time']) || $input['delivery_time'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Delivery time must be a positive number']);
                exit;
            }

            if (!is_numeric($input['revision_number']) || $input['revision_number'] < 0) {
                echo json_encode(['status' => 'error', 'message' => 'Revision number must be a non-negative number']);
                exit;
            }

            if (!isset($input['features']) || !is_array($input['features']) || count($input['features']) < 1) {
                echo json_encode(['status' => 'error', 'message' => 'At least one feature is required']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO gigs (user_id, title, description, category, subcategory, price, cover, images, short_title, short_description, delivery_time, revision_number, features) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $currentUser['id'],
                $input['title'],
                $input['description'],
                $input['category'],
                $input['subcategory'] ?? null,
                $input['price'],
                $input['cover'] ?? '',
                json_encode($input['images'] ?? []),
                $input['short_title'],
                $input['short_description'],
                $input['delivery_time'],
                $input['revision_number'],
                json_encode($input['features'])
            ]);

            if ($result) {
                $gigId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Gig created successfully', 'id' => $gigId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create gig']);
            }
            break;

        case 'PUT':
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Gig ID is required']);
                exit;
            }

            $currentUser = checkAuth('seller');

            $stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ? AND user_id = ?");
            $stmt->execute([$input['id'], $currentUser['id']]);
            $gig = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$gig) {
                echo json_encode(['status' => 'error', 'message' => 'Gig not found or unauthorized']);
                exit;
            }

            $updateFields = [];
            $params = [];
            $allowedFields = ['title', 'description', 'category', 'subcategory', 'price', 'cover', 'images', 'short_title', 'short_description', 'delivery_time', 'revision_number', 'features', 'status'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'price' && (!is_numeric($input[$field]) || $input[$field] <= 0)) {
                        echo json_encode(['status' => 'error', 'message' => 'Price must be a positive number']);
                        exit;
                    }

                    if ($field === 'delivery_time' && (!is_numeric($input[$field]) || $input[$field] <= 0)) {
                        echo json_encode(['status' => 'error', 'message' => 'Delivery time must be a positive number']);
                        exit;
                    }

                    if ($field === 'revision_number' && (!is_numeric($input[$field]) || $input[$field] < 0)) {
                        echo json_encode(['status' => 'error', 'message' => 'Revision number must be a non-negative number']);
                        exit;
                    }

                    if ($field === 'features' && (!is_array($input[$field]) || count($input[$field]) < 1)) {
                        echo json_encode(['status' => 'error', 'message' => 'At least one feature is required']);
                        exit;
                    }

                    if ($field === 'images') {
                        $updateFields[] = "$field = ?";
                        $params[] = json_encode($input[$field]);
                    } else {
                        $updateFields[] = "$field = ?";
                        $params[] = $input[$field];
                    }
                }
            }

            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }

            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $input['id'];

            $stmt = $pdo->prepare("UPDATE gigs SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Gig updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update gig']);
            }
            break;

        case 'DELETE':
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Gig ID is required']);
                exit;
            }

            $currentUser = checkAuth();

            if ($currentUser['role'] === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ?");
                $stmt->execute([$input['id']]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ? AND user_id = ?");
                $stmt->execute([$input['id'], $currentUser['id']]);
            }

            $gig = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$gig) {
                echo json_encode(['status' => 'error', 'message' => 'Gig not found or unauthorized']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM gigs WHERE id = ?");
            $result = $stmt->execute([$input['id']]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Gig deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete gig']);
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
