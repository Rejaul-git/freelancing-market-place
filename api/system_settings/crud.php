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
            // Read system settings
            if (isset($_GET['key'])) {
                // Get single setting by key (public settings can be accessed by anyone)
                $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_key = ?");
                $stmt->execute([$_GET['key']]);
                $setting = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($setting) {
                    // Check if setting is public or user has admin access
                    if ($setting['is_public'] || (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin')) {
                        echo json_encode(['status' => 'success', 'data' => $setting]);
                    } else {
                        http_response_code(403);
                        echo json_encode(['status' => 'error', 'message' => 'Access denied to this setting']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Setting not found']);
                }
            } elseif (isset($_GET['public'])) {
                // Get all public settings (no authentication required)
                $stmt = $pdo->prepare("SELECT setting_key, setting_value, description FROM system_settings WHERE is_public = 1 ORDER BY setting_key");
                $stmt->execute();
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['status' => 'success', 'data' => $settings]);
            } else {
                // Get all settings (admin only)
                $currentUser = checkAuth('admin');
                
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $category = $_GET['category'] ?? '';
                $search = $_GET['search'] ?? '';
                
                $offset = ($page - 1) * $limit;
                
                $whereConditions = [];
                $params = [];
                
                if ($category) {
                    $whereConditions[] = "category = ?";
                    $params[] = $category;
                }
                
                if ($search) {
                    $whereConditions[] = "(setting_key LIKE ? OR description LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }
                
                $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings $whereClause");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get settings
                $stmt = $pdo->prepare("SELECT * FROM system_settings 
                                     $whereClause
                                     ORDER BY category, setting_key
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...$params, $limit, $offset]);
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $settings,
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
            // Create system setting (admin only)
            $currentUser = checkAuth('admin');
            
            $requiredFields = ['setting_key', 'setting_value'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field])) {
                    echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                    exit;
                }
            }
            
            // Check if setting key already exists
            $stmt = $pdo->prepare("SELECT id FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$input['setting_key']]);
            $existingSetting = $stmt->fetch();
            
            if ($existingSetting) {
                echo json_encode(['status' => 'error', 'message' => 'Setting key already exists']);
                exit;
            }
            
            // Validate setting key format (alphanumeric, underscore, dot)
            if (!preg_match('/^[a-zA-Z0-9_.]+$/', $input['setting_key'])) {
                echo json_encode(['status' => 'error', 'message' => 'Setting key can only contain letters, numbers, underscore, and dot']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, description, category, data_type, is_public) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $input['setting_key'],
                $input['setting_value'],
                $input['description'] ?? null,
                $input['category'] ?? 'general',
                $input['data_type'] ?? 'string',
                $input['is_public'] ?? 0
            ]);
            
            if ($result) {
                $settingId = $pdo->lastInsertId();
                echo json_encode(['status' => 'success', 'message' => 'System setting created successfully', 'id' => $settingId]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to create system setting']);
            }
            break;
            
        case 'PUT':
            // Update system setting (admin only)
            if (!isset($input['id']) && !isset($input['setting_key'])) {
                echo json_encode(['status' => 'error', 'message' => 'Setting ID or key is required']);
                exit;
            }
            
            $currentUser = checkAuth('admin');
            
            // Get setting by ID or key
            if (isset($input['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE id = ?");
                $stmt->execute([$input['id']]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_key = ?");
                $stmt->execute([$input['setting_key']]);
            }
            
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$setting) {
                echo json_encode(['status' => 'error', 'message' => 'System setting not found']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            $allowedFields = ['setting_value', 'description', 'category', 'data_type', 'is_public'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    // Validate data type
                    if ($field === 'data_type') {
                        $validTypes = ['string', 'integer', 'boolean', 'json', 'text'];
                        if (!in_array($input[$field], $validTypes)) {
                            echo json_encode(['status' => 'error', 'message' => 'Invalid data type']);
                            exit;
                        }
                    }
                    
                    // Validate setting value based on data type
                    if ($field === 'setting_value' && isset($input['data_type'])) {
                        $dataType = $input['data_type'];
                        if ($dataType === 'integer' && !is_numeric($input[$field])) {
                            echo json_encode(['status' => 'error', 'message' => 'Setting value must be numeric for integer type']);
                            exit;
                        } elseif ($dataType === 'boolean' && !in_array(strtolower($input[$field]), ['true', 'false', '1', '0'])) {
                            echo json_encode(['status' => 'error', 'message' => 'Setting value must be true/false or 1/0 for boolean type']);
                            exit;
                        } elseif ($dataType === 'json') {
                            json_decode($input[$field]);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                echo json_encode(['status' => 'error', 'message' => 'Setting value must be valid JSON for json type']);
                                exit;
                            }
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
            $params[] = $setting['id'];
            
            $stmt = $pdo->prepare("UPDATE system_settings SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'System setting updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update system setting']);
            }
            break;
            
        case 'DELETE':
            // Delete system setting (admin only)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Setting ID is required']);
                exit;
            }
            
            $currentUser = checkAuth('admin');
            
            // Get setting details
            $stmt = $pdo->prepare("SELECT * FROM system_settings WHERE id = ?");
            $stmt->execute([$input['id']]);
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$setting) {
                echo json_encode(['status' => 'error', 'message' => 'System setting not found']);
                exit;
            }
            
            // Prevent deletion of critical system settings
            $criticalSettings = ['site_name', 'site_url', 'admin_email', 'maintenance_mode'];
            if (in_array($setting['setting_key'], $criticalSettings)) {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete critical system setting']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM system_settings WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'System setting deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete system setting']);
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
