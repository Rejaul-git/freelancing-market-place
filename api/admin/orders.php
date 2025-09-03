<?php
session_start();
require_once '../config/db.php';
require_once '../config/cors.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    // Preflight for CORS
    exit;
}


switch ($method) {
    case 'GET':
        // Read params
        $search = isset($_GET['search']) ? $_GET['search'] : "";
        $status = isset($_GET['status']) ? $_GET['status'] : "";
        $user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;  // নতুন line
        $role = isset($_GET['role']) ? $_GET['role'] : "";  // নতুন line

        // Build query
        $query = "SELECT * FROM orders WHERE 1=1 ";
        $params = [];

        if ($search !== "") {
            $query .= " AND (gig_title LIKE :search OR buyer_id LIKE :search OR seller_id LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if ($status !== "" && $status !== "all") {
            $query .= " AND status = :status";
            $params[':status'] = $status;
        }

        // if ($user_id !== null) {
        //     $query .= " AND seller_id = :user_id";  // seller_id ফিল্টার user_id দিয়ে
        //     $params[':user_id'] = $user_id;
        // }
        if ($user_id !== null && $role === 'seller') {
            $query .= " AND seller_id = :user_id";  // seller_id ফিল্টার user_id দিয়ে
            $params[':user_id'] = $user_id;
        } elseif ($user_id !== null && $role === 'buyer') {
            $query .= " AND buyer_id = :user_id";  // buyer_id ফিল্টার user_id দিয়ে
            $params[':user_id'] = $user_id;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["status" => "success", "data" => $orders]);
        break;

    case 'PUT':
        // Update status
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing parameters"]);
            exit;
        }

        $id = $input['id'];
        $status = $input['status'];

        $allowedStatuses = ['active', 'pending', 'canceled'];
        if (!in_array($status, $allowedStatuses)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Invalid status"]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute([':status' => $status, ':id' => $id]);

        if ($success) {
            echo json_encode(["status" => "success", "message" => "Order status updated"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to update order"]);
        }
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents("php://input"), true);
        if (!isset($input['id'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing order ID"]);
            exit;
        }

        $id = $input['id'];
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = :id");
        $success = $stmt->execute([':id' => $id]);

        if ($success) {
            echo json_encode(["status" => "success", "message" => "Order deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to delete order"]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method not allowed"]);
        break;
}
