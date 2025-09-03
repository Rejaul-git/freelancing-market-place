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
            // Read notifications
            $currentUser = checkAuth();
            
            if (isset($_GET['id'])) {
                // Get single notification
                $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ? AND (user_id = ? OR ? = 'admin')");
                $stmt->execute([$_GET['id'], $currentUser['id'], $currentUser['role']]);
                $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($notification) {
                    // Mark as read when viewed
                    if (!$notification['is_read'] && $currentUser['id'] == $notification['user_id']) {
                        $updateStmt = $pdo->prepare("UPDATE notifications SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $updateStmt->execute([$_GET['id']]);
                    }
                    
                    echo json_encode(['status' => 'success', 'data' => $notification]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
                }
            } else {
                // Get user's notifications
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $type = $_GET['type'] ?? '';
                $is_read = $_GET['is_read'] ?? '';
                
                $offset = ($page - 1) * $limit;
                
                $whereConditions = [];
                $params = [];
                
                // Role-based filtering
                if ($currentUser['role'] === 'admin') {
                    // Admin can see all notifications or filter by user
                    if (isset($_GET['user_id'])) {
                        $whereConditions[] = "user_id = ?";
                        $params[] = $_GET['user_id'];
                    }
                } else {
                    $whereConditions[] = "user_id = ?";
                    $params[] = $currentUser['id'];
                }
                
                if ($type) {
                    $whereConditions[] = "type = ?";
                    $params[] = $type;
                }
                
                if ($is_read !== '') {
                    $whereConditions[] = "is_read = ?";
                    $params[] = $is_read;
                }
                
                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get notifications
                $stmt = $pdo->prepare("SELECT * FROM notifications 
                                     $whereClause
                                     ORDER BY created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $notifications,
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
            // Create notification (system or admin only)
            $currentUser = checkAuth();
            
            if ($currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Only admins can create notifications']);
                exit;
            }
            
            $requiredFields = ['user_id', 'type', 'title', 'message'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }
            
            // Validate notification type
            $validTypes = ['order', 'message', 'review', 'payment', 'system', 'promotion', 'gig_update'];
            if (!in_array($input['type'], $validTypes)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid notification type']);
                exit;
            }
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$input['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, data, action_url) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['user_id'],
                $input['type'],
                $input['title'],
                $input['message'],
                json_encode($input['data'] ?? []),
                $input['action_url'] ?? null
            ]);
            
            if ($result) {
                $notificationId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Notification created successfully', 'id' => $notificationId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create notification']);
            }
            break;
            
        case 'PUT':
            // Update notification (mark as read/unread)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Notification ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get notification details
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->execute([$input['id']]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
                exit;
            }
            
            // Check permissions (only notification owner or admin can update)
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $notification['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot update this notification']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['is_read'])) {
                $updateFields[] = "is_read = ?";
                $params[] = $input['is_read'];
                
                if ($input['is_read']) {
                    $updateFields[] = "read_at = CURRENT_TIMESTAMP";
                } else {
                    $updateFields[] = "read_at = NULL";
                }
            }
            
            // Admin can update other fields
            if ($currentUser['role'] === 'admin') {
                $allowedFields = ['title', 'message', 'data', 'action_url'];
                foreach ($allowedFields as $field) {
                    if (isset($input[$field])) {
                        if ($field === 'data') {
                            $updateFields[] = "$field = ?";
                            $params[] = json_encode($input[$field]);
                        } else {
                            $updateFields[] = "$field = ?";
                            $params[] = $input[$field];
                        }
                    }
                }
            }
            
            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }
            
            $params[] = $input['id'];
            
            $stmt = $pdo->prepare("UPDATE notifications SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Notification updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update notification']);
            }
            break;
            
        case 'DELETE':
            // Delete notification
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Notification ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get notification details
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
            $stmt->execute([$input['id']]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                echo json_encode(['status' => 'error', 'message' => 'Notification not found']);
                exit;
            }
            
            // Check permissions (only notification owner or admin can delete)
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $notification['user_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete this notification']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Notification deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete notification']);
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
