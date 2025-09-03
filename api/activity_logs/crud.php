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
            // Read activity logs
            $currentUser = checkAuth();

            if (isset($_GET['id'])) {
                // Get single activity log
                $stmt = $pdo->prepare("SELECT a.*, u.username as user_name
                                     FROM activity_logs a
                                     LEFT JOIN users u ON a.user_id = u.id
                                     WHERE a.id = ?");
                $stmt->execute([$_GET['id']]);
                $activity = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($activity) {
                    // Check access permissions (users can see their own logs, admin can see all)
                    if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $activity['user_id']) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                        exit;
                    }

                    echo json_encode(['status' => 'success', 'data' => $activity]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Activity log not found']);
                }
            } else {
                // Get activity logs with filters
                // $page = $_GET['page'] ?? 1;
                // $limit = $_GET['limit'] ?? 20;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
                $user_id = $_GET['user_id'] ?? '';
                $action = $_GET['action'] ?? '';
                $entity_type = $_GET['entity_type'] ?? '';
                $date_from = $_GET['date_from'] ?? '';
                $date_to = $_GET['date_to'] ?? '';

                $offset = ($page - 1) * $limit;

                $whereConditions = [];
                $params = [];

                // Role-based filtering
                if ($currentUser['role'] === 'admin') {
                    if ($user_id) {
                        $whereConditions[] = "a.user_id = ?";
                        $params[] = $user_id;
                    }
                } else {
                    $whereConditions[] = "a.user_id = ?";
                    $params[] = $currentUser['id'];
                }

                if ($action) {
                    $whereConditions[] = "a.action = ?";
                    $params[] = $action;
                }

                if ($entity_type) {
                    $whereConditions[] = "a.entity_type = ?";
                    $params[] = $entity_type;
                }

                if ($date_from) {
                    $whereConditions[] = "DATE(a.created_at) >= ?";
                    $params[] = $date_from;
                }

                if ($date_to) {
                    $whereConditions[] = "DATE(a.created_at) <= ?";
                    $params[] = $date_to;
                }

                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs a $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Get activity logs
                $stmt = $pdo->prepare("SELECT a.*, u.username as user_name
                                     FROM activity_logs a
                                     LEFT JOIN users u ON a.user_id = u.id
                                     $whereClause
                                     ORDER BY a.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'data' => $activities,
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
            // Create activity log (system/admin only - typically automated)
            $currentUser = checkAuth();

            $requiredFields = ['action', 'entity_type'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }

            // Validate action and entity_type
            $validActions = ['create', 'read', 'update', 'delete', 'login', 'logout', 'register', 'upload', 'download'];
            $validEntityTypes = ['user', 'gig', 'order', 'message', 'review', 'payment', 'withdrawal', 'category', 'favorite'];

            if (!in_array($input['action'], $validActions)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid action type']);
                exit;
            }

            if (!in_array($input['entity_type'], $validEntityTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid entity type']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, user_agent, data) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['user_id'] ?? $currentUser['id'],
                $input['action'],
                $input['entity_type'],
                $input['entity_id'] ?? null,
                $input['description'] ?? null,
                $input['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? null,
                $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null,
                json_encode($input['data'] ?? [])
            ]);

            if ($result) {
                $activityId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Activity log created successfully', 'id' => $activityId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create activity log']);
            }
            break;

        case 'DELETE':
            // Delete activity logs (admin only - for cleanup)
            $currentUser = checkAuth('admin');

            if (isset($input['id'])) {
                // Delete single activity log
                $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE id = ?");
                $result = $stmt->execute([$input['id']]);

                if ($result) {
                    echo json_encode(['status' => 'success', 'message' => 'Activity log deleted successfully']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to delete activity log']);
                }
            } elseif (isset($input['cleanup'])) {
                // Cleanup old activity logs (older than specified days)
                $days = $input['days'] ?? 90; // Default 90 days

                if ($days < 30) {
                    echo json_encode(['status' => 'error', 'message' => 'Cannot delete logs newer than 30 days']);
                    exit;
                }

                $stmt = $pdo->prepare("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $result = $stmt->execute([$days]);

                if ($result) {
                    $deletedCount = $stmt->rowCount();
                    echo json_encode(['status' => 'success', 'message' => "Deleted $deletedCount old activity logs"]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to cleanup activity logs']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Activity log ID or cleanup parameter is required']);
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
