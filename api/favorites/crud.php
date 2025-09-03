<?php
session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

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
            // Read favorites
            $currentUser = checkAuth();

            if (isset($_GET['check'])) {
                // Check if gig is favorited by user
                if (!isset($_GET['gig_id'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Gig ID is required']);
                    exit;
                }

                $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND gig_id = ?");
                $stmt->execute([$currentUser['id'], $_GET['gig_id']]);
                $favorite = $stmt->fetch();

                echo json_encode([
                    'status' => 'success',
                    'is_favorited' => $favorite ? true : false,
                    'favorite_id' => $favorite ? $favorite['id'] : null
                ]);
            } else {
                // Get user's favorites
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 12;
                $user_id = $_GET['user_id'] ?? $currentUser['id'];

                // Admin can view any user's favorites, others can only view their own
                if ($currentUser['role'] !== 'admin' && $user_id != $currentUser['id']) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                    exit;
                }

                $offset = ($page - 1) * $limit;

                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM favorites f 
                                          LEFT JOIN gigs g ON f.gig_id = g.id 
                                          WHERE f.user_id = ? AND (g.status = 'active' OR g.status = 'pending')");
                $countStmt->execute([$user_id]);
                $total = $countStmt->fetchColumn();

                // Get favorites with gig details
                $stmt = $pdo->prepare("SELECT f.*, g.title, g.description, g.price, g.cover, g.category,
                                     u.username as seller_name, u.img as seller_img,
                                     COUNT(r.id) as reviews_count, AVG(r.rating) as average_rating
                                     FROM favorites f
                                     LEFT JOIN gigs g ON f.gig_id = g.id
                                     LEFT JOIN users u ON g.user_id = u.id
                                     LEFT JOIN reviews r ON g.id = r.gig_id
                                     WHERE f.user_id = ? AND (g.status = 'active' OR g.status = 'pending')
                                     GROUP BY f.id
                                     ORDER BY f.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([$user_id, $limit, $offset]);
                $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'data' => $favorites,
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
            // Add to favorites
            $currentUser = checkAuth();

            $requiredFields = ['gig_id'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }

            // Check if gig exists and is active
            $stmt = $pdo->prepare("SELECT id FROM gigs WHERE id = ? AND (status = 'active' OR status = 'pending')");
            $stmt->execute([$input['gig_id']]);
            $gig = $stmt->fetch();

            if (!$gig) {
                echo json_encode(['status' => 'error', 'message' => 'Gig not found or inactive']);
                exit;
            }

            // Check if already favorited
            $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND gig_id = ?");
            $stmt->execute([$currentUser['id'], $input['gig_id']]);
            $existingFavorite = $stmt->fetch();

            if ($existingFavorite) {
                echo json_encode(['status' => 'error', 'message' => 'Gig already in favorites']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, gig_id) VALUES (?, ?)");
            $result = $stmt->execute([$currentUser['id'], $input['gig_id']]);

            if ($result) {
                $favoriteId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Added to favorites', 'id' => $favoriteId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to add to favorites']);
            }
            break;

        case 'DELETE':
            // Remove from favorites
            $currentUser = checkAuth();

            if (isset($input['id'])) {
                // Delete by favorite ID
                $stmt = $pdo->prepare("SELECT * FROM favorites WHERE id = ?");
                $stmt->execute([$input['id']]);
                $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$favorite) {
                    echo json_encode(['status' => 'error', 'message' => 'Favorite not found']);
                    exit;
                }

                // Check ownership (user can only remove their own favorites, admin can remove any)
                if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $favorite['user_id']) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Cannot remove this favorite']);
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
                $result = $stmt->execute([$input['id']]);
            } elseif (isset($input['gig_id'])) {
                // Delete by gig ID (remove current user's favorite)
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND gig_id = ?");
                $result = $stmt->execute([$currentUser['id'], $input['gig_id']]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Favorite ID or Gig ID is required']);
                exit;
            }

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Removed from favorites']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove from favorites']);
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
