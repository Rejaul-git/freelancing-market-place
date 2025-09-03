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
            // Read earnings
            $currentUser = checkAuth();
            
            if (isset($_GET['id'])) {
                // Get single earning record
                $stmt = $pdo->prepare("SELECT e.*, o.gig_title, p.transaction_id
                                     FROM earnings e
                                     LEFT JOIN orders o ON e.order_id = o.id
                                     LEFT JOIN payments p ON e.payment_id = p.id
                                     WHERE e.id = ?");
                $stmt->execute([$_GET['id']]);
                $earning = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($earning) {
                    // Check access permissions
                    if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $earning['seller_id']) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                        exit;
                    }
                    
                    echo json_encode(['status' => 'success', 'data' => $earning]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Earning record not found']);
                }
            } elseif (isset($_GET['summary'])) {
                // Get earnings summary for seller
                $sellerId = $_GET['seller_id'] ?? $currentUser['id'];
                
                // Check permissions
                if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $sellerId) {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                    exit;
                }
                
                $stmt = $pdo->prepare("SELECT 
                                     SUM(amount) as total_earnings,
                                     SUM(CASE WHEN status = 'available' THEN amount ELSE 0 END) as available_balance,
                                     SUM(CASE WHEN status = 'withdrawn' THEN amount ELSE 0 END) as withdrawn_amount,
                                     SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                                     COUNT(*) as total_transactions
                                     FROM earnings WHERE seller_id = ?");
                $stmt->execute([$sellerId]);
                $summary = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'data' => $summary]);
            } else {
                // Get earnings with filters
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $status = $_GET['status'] ?? '';
                $sellerId = $_GET['seller_id'] ?? '';
                
                $offset = ($page - 1) * $limit;
                
                $whereConditions = [];
                $params = [];
                
                // Role-based filtering
                if ($currentUser['role'] === 'admin') {
                    if ($sellerId) {
                        $whereConditions[] = "e.seller_id = ?";
                        $params[] = $sellerId;
                    }
                } else {
                    $whereConditions[] = "e.seller_id = ?";
                    $params[] = $currentUser['id'];
                }
                
                if ($status) {
                    $whereConditions[] = "e.status = ?";
                    $params[] = $status;
                }
                
                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM earnings e $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get earnings
                $stmt = $pdo->prepare("SELECT e.*, o.gig_title, p.transaction_id
                                     FROM earnings e
                                     LEFT JOIN orders o ON e.order_id = o.id
                                     LEFT JOIN payments p ON e.payment_id = p.id
                                     $whereClause
                                     ORDER BY e.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $earnings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $earnings,
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
            // Create earning record (system/admin only - typically automated)
            $currentUser = checkAuth('admin');
            
            $requiredFields = ['seller_id', 'order_id', 'payment_id', 'amount'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }
            
            // Validate amount
            if ($input['amount'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Amount must be greater than 0']);
                exit;
            }
            
            // Check if earning record already exists for this payment
            $stmt = $pdo->prepare("SELECT id FROM earnings WHERE payment_id = ?");
            $stmt->execute([$input['payment_id']]);
            $existingEarning = $stmt->fetch();
            
            if ($existingEarning) {
                echo json_encode(['status' => 'error', 'message' => 'Earning record already exists for this payment']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO earnings (seller_id, order_id, payment_id, amount, status) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['seller_id'],
                $input['order_id'],
                $input['payment_id'],
                $input['amount'],
                $input['status'] ?? 'pending'
            ]);
            
            if ($result) {
                $earningId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Earning record created successfully', 'id' => $earningId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create earning record']);
            }
            break;
            
        case 'PUT':
            // Update earning status (admin only)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Earning ID is required']);
                exit;
            }
            
            $currentUser = checkAuth('admin');
            
            // Get earning details
            $stmt = $pdo->prepare("SELECT * FROM earnings WHERE id = ?");
            $stmt->execute([$input['id']]);
            $earning = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$earning) {
                echo json_encode(['status' => 'error', 'message' => 'Earning record not found']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['status'])) {
                $validStatuses = ['pending', 'available', 'withdrawn', 'on_hold'];
                if (!in_array($input['status'], $validStatuses)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid earning status']);
                    exit;
                }
                
                $updateFields[] = "status = ?";
                $params[] = $input['status'];
                
                // Set available_date when status changes to available
                if ($input['status'] === 'available' && $earning['status'] !== 'available') {
                    $updateFields[] = "available_date = CURRENT_TIMESTAMP";
                }
            }
            
            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }
            
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $input['id'];
            
            $stmt = $pdo->prepare("UPDATE earnings SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Earning record updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update earning record']);
            }
            break;
            
        case 'DELETE':
            // Delete earning record (admin only - rarely used)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Earning ID is required']);
                exit;
            }
            
            $currentUser = checkAuth('admin');
            
            // Get earning details
            $stmt = $pdo->prepare("SELECT * FROM earnings WHERE id = ?");
            $stmt->execute([$input['id']]);
            $earning = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$earning) {
                echo json_encode(['status' => 'error', 'message' => 'Earning record not found']);
                exit;
            }
            
            // Only allow deletion of pending earnings
            if ($earning['status'] !== 'pending') {
                echo json_encode(['status' => 'error', 'message' => 'Can only delete pending earning records']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM earnings WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Earning record deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete earning record']);
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
