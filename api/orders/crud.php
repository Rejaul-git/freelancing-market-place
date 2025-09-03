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
            // Read orders
            $currentUser = checkAuth();

            if (isset($_GET['id'])) {
                // Get single order
                $stmt = $pdo->prepare("SELECT o.*, g.title as gig_title, g.cover as gig_image,
                                     buyer.username as buyer_name, buyer.img as buyer_img,
                                     seller.username as seller_name, seller.img as seller_img
                                     FROM orders o
                                     LEFT JOIN gigs g ON o.gig_id = g.id
                                     LEFT JOIN users buyer ON o.buyer_id = buyer.id
                                     LEFT JOIN users seller ON o.seller_id = seller.id
                                     WHERE o.id = ?");
                $stmt->execute([$_GET['id']]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($order) {
                    // Check access permissions
                    if (
                        $currentUser['role'] !== 'admin' &&
                        $currentUser['id'] != $order['buyer_id'] &&
                        $currentUser['id'] != $order['seller_id']
                    ) {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
                        exit;
                    }

                    echo json_encode(['status' => 'success', 'data' => $order]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Order not found']);
                }
            } else {
                // Get orders with filters
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 10;
                $status = $_GET['status'] ?? '';
                $user_type = $_GET['user_type'] ?? ''; // 'buyer' or 'seller'

                $offset = ($page - 1) * $limit;

                $whereConditions = [];
                $params = [];

                // Role-based filtering
                if ($currentUser['role'] === 'admin') {
                    // Admin can see all orders
                } elseif ($user_type === 'seller' || $currentUser['role'] === 'seller') {
                    $whereConditions[] = "o.seller_id = ?";
                    $params[] = $currentUser['id'];
                } else {
                    // Default to buyer orders
                    $whereConditions[] = "o.buyer_id = ?";
                    $params[] = $currentUser['id'];
                }

                if ($status) {
                    $whereConditions[] = "o.status = ?";
                    $params[] = $status;
                }

                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();

                // Get orders
                $stmt = $pdo->prepare("SELECT o.*, g.title as gig_title, g.cover as gig_image,
                                     buyer.username as buyer_name, buyer.img as buyer_img,
                                     seller.username as seller_name, seller.img as seller_img
                                     FROM orders o
                                     LEFT JOIN gigs g ON o.gig_id = g.id
                                     LEFT JOIN users buyer ON o.buyer_id = buyer.id
                                     LEFT JOIN users seller ON o.seller_id = seller.id
                                     $whereClause
                                     ORDER BY o.created_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode([
                    'status' => 'success',
                    'data' => $orders,
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
            // Create order (buyers only)
            $currentUser = checkAuth();

            if ($currentUser['role'] !== 'buyer' && $currentUser['role'] !== 'admin') {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Only buyers can create orders']);
                exit;
            }

            $requiredFields = ['gig_id'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }

            // Get gig details
            $stmt = $pdo->prepare("SELECT * FROM gigs WHERE id = ? AND (status = 'active' OR status = 'pending')");
            $stmt->execute([$input['gig_id']]);
            $gig = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$gig) {
                echo json_encode(['status' => 'error', 'message' => 'Gig not found or inactive']);
                exit;
            }

            // Calculate deadline
            $deadline = date('Y-m-d', strtotime("+{$gig['delivery_time']} days"));
            $platform_fee = $gig['price'] * 0.1; // Example platform fee calculation (10% of gig price)

            $stmt = $pdo->prepare("INSERT INTO orders (gig_id, buyer_id, seller_id, gig_title, gig_image, price, deadline, requirements, status, payment_status, platform_fee) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['gig_id'],
                $currentUser['id'],
                $gig['user_id'],
                $gig['title'],
                $gig['cover'],
                $gig['price'],
                $deadline,
                $input['requirements'] ?? null,
                'pending',
                'pending',
                $platform_fee
            ]);

            if ($result) {
                $orderId = $pdo->lastInsertId();

                // Update gig sales count
                $updateSales = $pdo->prepare("UPDATE gigs SET sales = sales + 1 WHERE id = ?");
                $updateSales->execute([$input['gig_id']]);

                echo json_encode(['status' => 'success', 'message' => 'Order created successfully', 'id' => $orderId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create order']);
            }
            break;

        case 'PUT':
            // Update order
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
                exit;
            }

            $currentUser = checkAuth();

            // Get order details for permission check
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$input['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Order not found']);
                exit;
            }

            // Check permissions
            $canUpdate = false;
            if ($currentUser['role'] === 'admin') {
                $canUpdate = true;
            } elseif ($currentUser['id'] == $order['seller_id']) {
                $canUpdate = true;
            } elseif ($currentUser['id'] == $order['buyer_id'] && isset($input['requirements'])) {
                $canUpdate = true; // Buyers can only update requirements
            }

            if (!$canUpdate) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot update this order']);
                exit;
            }

            $updateFields = [];
            $params = [];

            // Define what each role can update
            if ($currentUser['role'] === 'admin') {
                $allowedFields = ['status', 'payment_status', 'deadline', 'requirements', 'delivery_note'];
            } elseif ($currentUser['id'] == $order['seller_id']) {
                $allowedFields = ['status', 'delivery_note'];
                // Sellers can mark as delivered
                if (isset($input['status']) && $input['status'] === 'delivered') {
                    $updateFields[] = "delivery_date = CURRENT_TIMESTAMP";
                }
            } else {
                $allowedFields = ['requirements']; // Buyers can only update requirements
            }

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
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

            $stmt = $pdo->prepare("UPDATE orders SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);

            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Order updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update order']);
            }
            break;

        case 'DELETE':
            // Delete order (admin only or cancel by buyer if pending)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Order ID is required']);
                exit;
            }

            $currentUser = checkAuth();

            // Get order details
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$input['id']]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                echo json_encode(['status' => 'error', 'message' => 'Order not found']);
                exit;
            }

            // Check permissions
            if ($currentUser['role'] !== 'admin') {
                if ($currentUser['id'] != $order['buyer_id'] || $order['status'] !== 'pending') {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Cannot delete this order']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $result = $stmt->execute([$input['id']]);

            if ($result) {
                // Decrease gig sales count
                $updateSales = $pdo->prepare("UPDATE gigs SET sales = sales - 1 WHERE id = ? AND sales > 0");
                $updateSales->execute([$order['gig_id']]);

                echo json_encode(['status' => 'success', 'message' => 'Order deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete order']);
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
