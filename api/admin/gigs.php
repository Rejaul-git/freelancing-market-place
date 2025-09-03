<?php
// session_start();
// header("Content-Type: application/json");
// header("Access-Control-Allow-Origin: http://localhost:5173");
// header("Access-Control-Allow-Methods: GET");
// header("Access-Control-Allow-Headers: Content-Type");
// header("Access-Control-Allow-Credentials: true");

// require_once '../config/db.php';

// // Check if user is admin
// if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
//     http_response_code(403);
//     echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
//     exit;
// }

// try {
//     $stmt = $pdo->query("SELECT g.id, g.title, g.description, g.price, g.image_url, g.created_at,
//                         u.username as seller_name,
//                         COUNT(o.id) as orders_count
//                         FROM gigs g
//                         LEFT JOIN users u ON g.user_id = u.id
//                         LEFT JOIN orders o ON g.id = o.gig_id
//                         GROUP BY g.id
//                         ORDER BY g.created_at DESC");
//     $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

//     echo json_encode(['status' => 'success', 'data' => $gigs]);

// } catch (PDOException $e) {
//     echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
// }


session_start();
require_once '../config/cors.php';
require_once '../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents("php://input"), true);

try {
    switch ($method) {
        // ================== GET ==================
        case 'GET':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';

            $where = [];
            $params = [];

            if ($search) {
                $where[] = "(g.title LIKE ? OR u.username LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($status && $status !== 'all') {
                $where[] = "g.status = ?";
                $params[] = $status;
            }

            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

            $sql = "SELECT 
                        g.*,
                        u.username AS seller_name,
                        CONCAT('https://marketplace.brainstone.xyz/api/uploads/', g.cover) AS image_url,
                        (SELECT COUNT(*) FROM orders o WHERE o.gig_id = g.id) AS orders_count
                    FROM gigs g
                    LEFT JOIN users u ON g.user_id = u.id
                    $whereClause
                    ORDER BY g.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $gigs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'data' => $gigs
            ]);
            break;

        // ================== UPDATE STATUS ==================
        case 'PUT':
            if (!isset($input['id'], $input['status'])) {
                echo json_encode(['status' => 'error', 'message' => 'Gig ID and status required']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE gigs SET status = ?, updated_at = NOW() WHERE id = ?");
            $res = $stmt->execute([$input['status'], $input['id']]);

            echo json_encode(
                $res
                    ? ['status' => 'success', 'message' => 'Status updated']
                    : ['status' => 'error', 'message' => 'Failed to update status']
            );
            break;

        // ================== DELETE ==================
        case 'DELETE':
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Gig ID required']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM gigs WHERE id = ?");
            $res = $stmt->execute([$input['id']]);

            echo json_encode(
                $res
                    ? ['status' => 'success', 'message' => 'Gig deleted successfully']
                    : ['status' => 'error', 'message' => 'Failed to delete gig']
            );
            break;

        default:
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
