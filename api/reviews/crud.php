<?php
session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Check authentication for protected operations
function checkAuth($requiredRole = null) {
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
            // Read reviews
            if (isset($_GET['id'])) {
                // Get single review
                $stmt = $pdo->prepare("SELECT r.*, u.username as reviewer_name, u.img as reviewer_img,
                                     g.title as gig_title, seller.username as seller_name
                                     FROM reviews r
                                     LEFT JOIN users u ON r.user_id = u.id
                                     LEFT JOIN gigs g ON r.gig_id = g.id
                                     LEFT JOIN users seller ON g.user_id = seller.id
                                     WHERE r.id = ?");
                $stmt->execute([$_GET['id']]);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($review) {
                    echo json_encode(['status' => 'success', 'data' => $review]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Review not found']);
                }
            } else {
                // Get reviews with filters
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $gig_id = $_GET['gig_id'] ?? '';
                $user_id = $_GET['user_id'] ?? '';
                $seller_id = $_GET['seller_id'] ?? '';
                $rating = $_GET['rating'] ?? '';
                
                $offset = ($page - 1) * $limit;
                
                $whereConditions = [];
                $params = [];
                
                if ($gig_id) {
                    $whereConditions[] = "r.gig_id = ?";
                    $params[] = $gig_id;
                }
                
                if ($user_id) {
                    $whereConditions[] = "r.user_id = ?";
                    $params[] = $user_id;
                }
                
                if ($seller_id) {
                    $whereConditions[] = "g.user_id = ?";
                    $params[] = $seller_id;
                }
                
                if ($rating) {
                    $whereConditions[] = "r.rating = ?";
                    $params[] = $rating;
                }
                
                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM reviews r LEFT JOIN gigs g ON r.gig_id = g.id $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get reviews
                $stmt = $pdo->prepare("SELECT r.*, u.username as reviewer_name, u.img as reviewer_img,
                                     g.title as gig_title, seller.username as seller_name
                                     FROM reviews r
                                     LEFT JOIN users u ON r.user_id = u.id
                                     LEFT JOIN gigs g ON r.gig_id = g.id
                                     LEFT JOIN users seller ON g.user_id = seller.id
                                     $whereClause
                                     ORDER BY r.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $reviews,
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
            // Create review (buyers only, after order completion)
            $currentUser = checkAuth();
            
            if ($currentUser['role'] !== 'buyer' && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Only buyers can create reviews']);
                exit;
            }
            
            $requiredFields = ['gig_id', 'order_id', 'rating', 'comment'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }
            
            // Validate rating
            if ($input['rating'] < 1 || $input['rating'] > 5) {
                echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5']);
                exit;
            }
            
            // Check if order exists and is completed by current user
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ? AND status = 'completed'");
            $stmt->execute([$input['order_id'], $currentUser['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order && $currentUser['role'] !== 'admin') {
                echo json_encode(['status' => 'error', 'message' => 'Order not found or not completed']);
                exit;
            }
            
            // Check if review already exists for this order
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE order_id = ?");
            $stmt->execute([$input['order_id']]);
            $existingReview = $stmt->fetch();
            
            if ($existingReview) {
                echo json_encode(['status' => 'error', 'message' => 'Review already exists for this order']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO reviews (gig_id, user_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['gig_id'],
                $currentUser['id'],
                $input['order_id'],
                $input['rating'],
                $input['comment']
            ]);
            
            if ($result) {
                $reviewId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Review created successfully', 'id' => $reviewId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create review']);
            }
            break;
            
        case 'PUT':
            // Update review (reviewer can edit their own review)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Review ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get review details
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
            $stmt->execute([$input['id']]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$review) {
                echo json_encode(['status' => 'error', 'message' => 'Review not found']);
                exit;
            }
            
            // Check permissions (only reviewer or admin can update)
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $review['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot update this review']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['rating', 'comment'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'rating' && ($input[$field] < 1 || $input[$field] > 5)) {
                        echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5']);
                        exit;
                    }
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $input['id'];
            
            $stmt = $pdo->prepare("UPDATE reviews SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Review updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update review']);
            }
            break;
            
        case 'DELETE':
            // Delete review (reviewer or admin only)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Review ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get review details
            $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
            $stmt->execute([$input['id']]);
            $review = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$review) {
                echo json_encode(['status' => 'error', 'message' => 'Review not found']);
                exit;
            }
            
            // Check permissions (only reviewer or admin can delete)
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $review['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete this review']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Review deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete review']);
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
?>
