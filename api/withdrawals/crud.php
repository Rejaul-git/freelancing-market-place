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
            // Read withdrawals
            $currentUser = checkAuth();
            
            if (isset($_GET['id'])) {
                // Get single withdrawal
                $stmt = $pdo->prepare("SELECT w.*, u.username as seller_name
                                     FROM withdrawals w
                                     LEFT JOIN users u ON w.seller_id = u.id
                                     WHERE w.id = ?");
                $stmt->execute([$_GET['id']]);
                $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($withdrawal) {
                    // Check access permissions
                    if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $withdrawal['seller_id']) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                        exit;
                    }
                    
                    echo json_encode(['status' => 'success', 'data' => $withdrawal]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Withdrawal not found']);
                }
            } else {
                // Get withdrawals with filters
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
                        $whereConditions[] = "w.seller_id = ?";
                        $params[] = $sellerId;
                    }
                } else {
                    $whereConditions[] = "w.seller_id = ?";
                    $params[] = $currentUser['id'];
                }
                
                if ($status) {
                    $whereConditions[] = "w.status = ?";
                    $params[] = $status;
                }
                
                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals w $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get withdrawals
                $stmt = $pdo->prepare("SELECT w.*, u.username as seller_name
                                     FROM withdrawals w
                                     LEFT JOIN users u ON w.seller_id = u.id
                                     $whereClause
                                     ORDER BY w.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $withdrawals,
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
            // Create withdrawal request (sellers only)
            $currentUser = checkAuth();
            
            if ($currentUser['role'] !== 'seller' && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Only sellers can request withdrawals']);
                exit;
            }
            
            $requiredFields = ['amount', 'payment_method'];
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
            
            // Check minimum withdrawal amount (e.g., $10)
            $minWithdrawal = 10;
            if ($input['amount'] < $minWithdrawal) {
                echo json_encode(['status' => 'error', 'message' => "Minimum withdrawal amount is $$minWithdrawal"]);
                exit;
            }
            
            // Check available balance
            $stmt = $pdo->prepare("SELECT SUM(amount) as available_balance FROM earnings WHERE seller_id = ? AND status = 'available'");
            $stmt->execute([$currentUser['id']]);
            $balance = $stmt->fetchColumn() ?? 0;
            
            if ($input['amount'] > $balance) {
                echo json_encode(['status' => 'error', 'message' => 'Insufficient available balance']);
                exit;
            }
            
            // Check for pending withdrawals
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE seller_id = ? AND status = 'pending'");
            $stmt->execute([$currentUser['id']]);
            $pendingCount = $stmt->fetchColumn();
            
            if ($pendingCount > 0) {
                echo json_encode(['status' => 'error', 'message' => 'You have a pending withdrawal request. Please wait for it to be processed.']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO withdrawals (seller_id, amount, payment_method, payment_details, status) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $currentUser['id'],
                $input['amount'],
                $input['payment_method'],
                json_encode($input['payment_details'] ?? []),
                'pending'
            ]);
            
            if ($result) {
                $withdrawalId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Withdrawal request submitted successfully', 'id' => $withdrawalId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to submit withdrawal request']);
            }
            break;
            
        case 'PUT':
            // Update withdrawal (admin for status updates, seller for details before processing)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Withdrawal ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get withdrawal details
            $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
            $stmt->execute([$input['id']]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$withdrawal) {
                echo json_encode(['status' => 'error', 'message' => 'Withdrawal not found']);
                exit;
            }
            
            // Check permissions
            $canUpdate = false;
            if ($currentUser['role'] === 'admin') {
                $canUpdate = true;
            } elseif ($currentUser['id'] == $withdrawal['seller_id'] && $withdrawal['status'] === 'pending') {
                $canUpdate = true; // Sellers can update pending withdrawals
            }
            
            if (!$canUpdate) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot update this withdrawal']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            // Define what each role can update
            if ($currentUser['role'] === 'admin') {
                $allowedFields = ['status', 'admin_notes', 'processed_date'];
                
                if (isset($input['status'])) {
                    $validStatuses = ['pending', 'processing', 'completed', 'rejected'];
                    if (!in_array($input['status'], $validStatuses)) {
                        echo json_encode(['status' => 'error', 'message' => 'Invalid withdrawal status']);
                        exit;
                    }
                    
                    // If marking as completed, set processed_date and update earnings
                    if ($input['status'] === 'completed' && $withdrawal['status'] !== 'completed') {
                        $updateFields[] = "processed_date = CURRENT_TIMESTAMP";
                        
                        // Mark earnings as withdrawn (simplified - in real app would track specific earnings)
                        $updateEarnings = $pdo->prepare("UPDATE earnings SET status = 'withdrawn' WHERE seller_id = ? AND status = 'available' AND amount <= ?");
                        $updateEarnings->execute([$withdrawal['seller_id'], $withdrawal['amount']]);
                    }
                }
            } else {
                $allowedFields = ['payment_method', 'payment_details'];
            }
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'payment_details') {
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
            
            $stmt = $pdo->prepare("UPDATE withdrawals SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Withdrawal updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update withdrawal']);
            }
            break;
            
        case 'DELETE':
            // Cancel withdrawal (seller can cancel pending withdrawals, admin can delete any)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Withdrawal ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get withdrawal details
            $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
            $stmt->execute([$input['id']]);
            $withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$withdrawal) {
                echo json_encode(['status' => 'error', 'message' => 'Withdrawal not found']);
                exit;
            }
            
            // Check permissions
            if ($currentUser['role'] !== 'admin') {
                if ($currentUser['id'] != $withdrawal['seller_id'] || $withdrawal['status'] !== 'pending') {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Can only cancel pending withdrawal requests']);
                    exit;
                }
            }
            
            $stmt = $pdo->prepare("DELETE FROM withdrawals WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Withdrawal cancelled successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to cancel withdrawal']);
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
