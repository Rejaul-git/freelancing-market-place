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
            // Read payments
            $currentUser = checkAuth();

            if (isset($_GET['id'])) {
                // Get single payment
                $stmt = $pdo->prepare("SELECT p.*, o.gig_title, 
                                     buyer.username as buyer_name, seller.username as seller_name
                                     FROM payments p
                                     LEFT JOIN orders o ON p.order_id = o.id
                                     LEFT JOIN users buyer ON p.buyer_id = buyer.id
                                     LEFT JOIN users seller ON p.seller_id = seller.id
                                     WHERE p.id = ?");
                $stmt->execute([$_GET['id']]);
                $payment = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($payment) {
                    // Check access permissions
                    if (
                        $currentUser['role'] !== 'admin' &&
                        $currentUser['id'] != $payment['buyer_id'] &&
                        $currentUser['id'] != $payment['seller_id']
                    ) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                        exit;
                    }

                    echo json_encode(['status' => 'success', 'data' => $payment]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                }
            } else {
                // Get payments with filters
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $status = $_GET['status'] ?? '';
                $user_type = $_GET['user_type'] ?? ''; // 'buyer' or 'seller'
                $method_filter = $_GET['method'] ?? '';

                $offset = ($page - 1) * $limit;

                $whereConditions = [];
                $params = [];

                // Role-based filtering
                if ($currentUser['role'] === 'admin') {
                    // Admin can see all payments
                    if (isset($_GET['user_id'])) {
                        $whereConditions[] = "(p.buyer_id = ? OR p.seller_id = ?)";
                        $params[] = $_GET['user_id'];
                        $params[] = $_GET['user_id'];
                    }
                } elseif ($user_type === 'seller' || $currentUser['role'] === 'seller') {
                    $whereConditions[] = "p.seller_id = ?";
                    $params[] = $currentUser['id'];
                } else {
                    // Default to buyer payments
                    $whereConditions[] = "p.buyer_id = ?";
                    $params[] = $currentUser['id'];
                }

                if ($status) {
                    $whereConditions[] = "p.status = ?";
                    $params[] = $status;
                }

                if ($method_filter) {
                    $whereConditions[] = "p.payment_method = ?";
                    $params[] = $method_filter;
                }

                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payments p $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Get payments
                $stmt = $pdo->prepare("SELECT p.*, o.gig_title,
                                     buyer.username as buyer_name, seller.username as seller_name
                                     FROM payments p
                                     LEFT JOIN orders o ON p.order_id = o.id
                                     LEFT JOIN users buyer ON p.buyer_id = buyer.id
                                     LEFT JOIN users seller ON p.seller_id = seller.id
                                     $whereClause
                                     ORDER BY p.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'data' => $payments,
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
            // Create payment (system/admin only - typically handled by payment gateway)
            $currentUser = checkAuth();

            $requiredFields = ['order_id', 'buyer_id', 'seller_id', 'amount', 'payment_method'];
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

            // Check if order exists and belongs to the current user
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
            $stmt->execute([$input['order_id'], $currentUser['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Order not found or access denied']);
                exit;
            }

            // Check if payment already exists for this order
            $stmt = $pdo->prepare("SELECT id FROM payments WHERE order_id = ?");
            $stmt->execute([$input['order_id']]);
            $existingPayment = $stmt->fetch();

            if ($existingPayment) {
                echo json_encode(['status' => 'error', 'message' => 'Payment already exists for this order']);
                exit;
            }

            // Calculate platform fee (e.g., 5%)
            $platformFee = $input['amount'] * 0.05;
            $sellerAmount = $input['amount'] - $platformFee;

            $stmt = $pdo->prepare("INSERT INTO payments (order_id, buyer_id, seller_id, amount, platform_fee, seller_amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['order_id'],
                $input['buyer_id'],
                $input['seller_id'],
                $input['amount'],
                $platformFee,
                $sellerAmount,
                $input['payment_method'],
                $input['transaction_id'] ?? null,
                $input['status'] ?? 'pending'
            ]);

            if ($result) {
                $paymentId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'Payment created successfully', 'id' => $paymentId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create payment']);
            }
            break;

        case 'PUT':
            // Update payment (admin only - typically for status updates)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Payment ID is required']);
                exit;
            }

            $currentUser = checkAuth('admin');

            // Get payment details
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$input['id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            $updateFields = [];
            $params = [];

            $allowedFields = ['status', 'transaction_id', 'payment_date', 'notes'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'status') {
                        $validStatuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];
                        if (!in_array($input[$field], $validStatuses)) {
                            echo json_encode(['status' => 'error', 'message' => 'Invalid payment status']);
                            exit;
                        }

                        // If marking as completed, set payment_date
                        if ($input[$field] === 'completed' && !$payment['payment_date']) {
                            $updateFields[] = "payment_date = CURRENT_TIMESTAMP";
                        }
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

            $stmt = $pdo->prepare("UPDATE payments SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);

            if ($result) {
                // If payment is completed, update order payment status
                if (isset($input['status']) && $input['status'] === 'completed') {
                    $updateOrder = $pdo->prepare("UPDATE orders SET payment_status = 'completed' WHERE id = ?");
                    $updateOrder->execute([$payment['order_id']]);
                }

                echo json_encode(['status' => 'success', 'message' => 'Payment updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update payment']);
            }
            break;

        case 'DELETE':
            // Delete payment (admin only - rarely used)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Payment ID is required']);
                exit;
            }

            $currentUser = checkAuth('admin');

            // Get payment details
            $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
            $stmt->execute([$input['id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                echo json_encode(['status' => 'error', 'message' => 'Payment not found']);
                exit;
            }

            // Only allow deletion of failed or cancelled payments
            if (!in_array($payment['status'], ['failed', 'cancelled'])) {
                echo json_encode(['status' => 'error', 'message' => 'Can only delete failed or cancelled payments']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            $result = $stmt->execute([$input['id']]);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Payment deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete payment']);
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
