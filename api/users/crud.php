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
            $search = $_GET['search'] ?? '';
            $role = $_GET['role'] ?? '';
            $status = $_GET['status'] ?? '';

            $whereConditions = [];
            $params = [];

            if ($search) {
                $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($role) {
                $whereConditions[] = "role = ?";
                $params[] = $role;
            }
            if ($status) {
                $whereConditions[] = "status = ?";
                $params[] = $status;
            }

            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            $stmt = $pdo->prepare("SELECT id, username, email, img, country, phone, des, role, status, last_login, email_verified, created_at 
                                   FROM users $whereClause 
                                   ORDER BY created_at DESC");
            $stmt->execute($params);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $users,
                'total' => count($users)
            ]);
            break;

        case 'POST':
            $requiredFields = ['username', 'email', 'password', 'country'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
                exit;
            }
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, img, country, phone, des, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['username'],
                $input['email'],
                $hashedPassword,
                $input['img'] ?? null,
                $input['country'],
                $input['phone'] ?? null,
                $input['des'] ?? null,
                $input['role'] ?? 'buyer',
                $input['status'] ?? 'active'
            ]);
            echo json_encode($result ? ['status' => 'success', 'message' => 'User created successfully'] : ['status' => 'error', 'message' => 'Failed to create user']);
            break;

        case 'PUT':
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
                exit;
            }
            $updateFields = [];
            $params = [];
            $allowedFields = ['username', 'email', 'img', 'country', 'phone', 'des', 'role', 'status'];
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $input[$field];
                }
            }
            if (isset($input['password']) && !empty($input['password'])) {
                $updateFields[] = "password = ?";
                $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
            }
            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }
            $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            $params[] = $input['id'];
            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            echo json_encode($result ? ['status' => 'success', 'message' => 'User updated successfully'] : ['status' => 'error', 'message' => 'Failed to update user']);
            break;

        case 'DELETE':
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'User ID is required']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            echo json_encode($result ? ['status' => 'success', 'message' => 'User deleted successfully'] : ['status' => 'error', 'message' => 'Failed to delete user']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
