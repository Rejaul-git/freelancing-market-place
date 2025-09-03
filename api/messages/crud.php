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
            // Read messages/conversations
            $currentUser = checkAuth();
            
            if (isset($_GET['conversation_id'])) {
                // Get messages in a conversation
                $conversationId = $_GET['conversation_id'];
                
                // Check if user is part of this conversation
                $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
                $stmt->execute([$conversationId, $currentUser['id'], $currentUser['id']]);
                $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$conversation && $currentUser['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Access denied to this conversation']);
                    exit;
                }
                
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 50;
                $offset = ($page - 1) * $limit;
                
                // Get messages
                $stmt = $pdo->prepare("SELECT m.*, u.username as sender_name, u.img as sender_img
                                     FROM messages m
                                     LEFT JOIN users u ON m.sender_id = u.id
                                     WHERE m.conversation_id = ?
                                     ORDER BY m.created_at ASC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([$conversationId, $limit, $offset]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Mark messages as read for current user
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?");
                $stmt->execute([$conversationId, $currentUser['id']]);
                
                echo json_encode(['status' => 'success', 'data' => $messages]);
                
            } elseif (isset($_GET['conversations'])) {
                // Get user's conversations
                $page = $_GET['page'] ?? 1;
                $limit = $_GET['limit'] ?? 20;
                $offset = ($page - 1) * $limit;
                
                $whereCondition = $currentUser['role'] === 'admin' ? '' : 'WHERE c.buyer_id = ? OR c.seller_id = ?';
                $params = $currentUser['role'] === 'admin' ? [] : [$currentUser['id'], $currentUser['id']];
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM conversations c $whereCondition");
                $countStmt->execute($params);
                $total = $countStmt->fetchColumn();
                
                // Get conversations with last message
                $stmt = $pdo->prepare("SELECT c.*, 
                                     buyer.username as buyer_name, buyer.img as buyer_img,
                                     seller.username as seller_name, seller.img as seller_img,
                                     g.title as gig_title,
                                     lm.content as last_message, lm.created_at as last_message_time,
                                     lm_sender.username as last_sender_name,
                                     COUNT(CASE WHEN m.is_read = 0 AND m.sender_id != ? THEN 1 END) as unread_count
                                     FROM conversations c
                                     LEFT JOIN users buyer ON c.buyer_id = buyer.id
                                     LEFT JOIN users seller ON c.seller_id = seller.id
                                     LEFT JOIN gigs g ON c.gig_id = g.id
                                     LEFT JOIN messages m ON c.id = m.conversation_id
                                     LEFT JOIN messages lm ON c.last_message_id = lm.id
                                     LEFT JOIN users lm_sender ON lm.sender_id = lm_sender.id
                                     $whereCondition
                                     GROUP BY c.id
                                     ORDER BY c.updated_at DESC
                                     LIMIT ? OFFSET ?");
                $stmt->execute([...([$currentUser['id']]), ...$params, $limit, $offset]);
                $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'status' => 'success',
                    'data' => $conversations,
                    'pagination' => [
                        'total' => (int)$total,
                        'page' => (int)$page,
                        'limit' => (int)$limit,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
                
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Invalid request parameters']);
            }
            break;
            
        case 'POST':
            // Create message or conversation
            $currentUser = checkAuth();
            
            if (isset($input['start_conversation'])) {
                // Start new conversation
                $requiredFields = ['recipient_id', 'gig_id', 'message'];
                foreach ($requiredFields as $field) {
                    if (!isset($input[$field]) || empty($input[$field])) {
                        echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                        exit;
                    }
                }
                
                // Check if conversation already exists
                $stmt = $pdo->prepare("SELECT id FROM conversations WHERE gig_id = ? AND ((buyer_id = ? AND seller_id = ?) OR (buyer_id = ? AND seller_id = ?))");
                $stmt->execute([$input['gig_id'], $currentUser['id'], $input['recipient_id'], $input['recipient_id'], $currentUser['id']]);
                $existingConversation = $stmt->fetch();
                
                if ($existingConversation) {
                    echo json_encode(['status' => 'error', 'message' => 'Conversation already exists', 'conversation_id' => $existingConversation['id']]);
                    exit;
                }
                
                // Get gig details to determine buyer/seller
                $stmt = $pdo->prepare("SELECT user_id FROM gigs WHERE id = ?");
                $stmt->execute([$input['gig_id']]);
                $gig = $stmt->fetch();
                
                if (!$gig) {
                    echo json_encode(['status' => 'error', 'message' => 'Gig not found']);
                    exit;
                }
                
                $buyerId = $currentUser['id'] == $gig['user_id'] ? $input['recipient_id'] : $currentUser['id'];
                $sellerId = $currentUser['id'] == $gig['user_id'] ? $currentUser['id'] : $input['recipient_id'];
                
                // Create conversation
                $stmt = $pdo->prepare("INSERT INTO conversations (buyer_id, seller_id, gig_id) VALUES (?, ?, ?)");
                $result = $stmt->execute([$buyerId, $sellerId, $input['gig_id']]);
                
                if ($result) {
                    $conversationId = $pdo->lastInsertId();
                    
                    // Create first message
                    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
                    $messageResult = $stmt->execute([$conversationId, $currentUser['id'], $input['message']]);
                    
                    if ($messageResult) {
                        $messageId = $pdo->lastInsertId();
                        
                        // Update conversation with last message
                        $stmt = $pdo->prepare("UPDATE conversations SET last_message_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt->execute([$messageId, $conversationId]);
                        
                        echo json_encode(['status' => 'success', 'message' => 'Conversation started', 'conversation_id' => $conversationId]);
                    } else {
                        echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to create conversation']);
                }
                
            } else {
                // Send message to existing conversation
                $requiredFields = ['conversation_id', 'content'];
                foreach ($requiredFields as $field) {
                    if (!isset($input[$field]) || empty($input[$field])) {
                        echo json_encode(['status' => 'error', 'message' => "Field $field is required"]);
                        exit;
                    }
                }
                
                // Check if user is part of this conversation
                $stmt = $pdo->prepare("SELECT * FROM conversations WHERE id = ? AND (buyer_id = ? OR seller_id = ?)");
                $stmt->execute([$input['conversation_id'], $currentUser['id'], $currentUser['id']]);
                $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$conversation && $currentUser['role'] !== 'admin') {
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => 'Access denied to this conversation']);
                    exit;
                }
                
                // Create message
                $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)");
                $result = $stmt->execute([$input['conversation_id'], $currentUser['id'], $input['content']]);
                
                if ($result) {
                    $messageId = $pdo->lastInsertId();
                    
                    // Update conversation with last message
                    $stmt = $pdo->prepare("UPDATE conversations SET last_message_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$messageId, $input['conversation_id']]);
                    
                    echo json_encode(['status' => 'success', 'message' => 'Message sent', 'message_id' => $messageId]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to send message']);
                }
            }
            break;
            
        case 'PUT':
            // Update message (mark as read, edit content)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Message ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get message details
            $stmt = $pdo->prepare("SELECT m.*, c.buyer_id, c.seller_id FROM messages m 
                                 LEFT JOIN conversations c ON m.conversation_id = c.id 
                                 WHERE m.id = ?");
            $stmt->execute([$input['id']]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$message) {
                echo json_encode(['status' => 'error', 'message' => 'Message not found']);
                exit;
            }
            
            // Check permissions
            $canUpdate = false;
            if ($currentUser['role'] === 'admin') {
                $canUpdate = true;
            } elseif (isset($input['is_read']) && ($currentUser['id'] == $message['buyer_id'] || $currentUser['id'] == $message['seller_id'])) {
                $canUpdate = true; // Can mark as read
            } elseif (isset($input['content']) && $currentUser['id'] == $message['sender_id']) {
                $canUpdate = true; // Can edit own message
            }
            
            if (!$canUpdate) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot update this message']);
                exit;
            }
            
            $updateFields = [];
            $params = [];
            
            if (isset($input['is_read'])) {
                $updateFields[] = "is_read = ?";
                $params[] = $input['is_read'];
            }
            
            if (isset($input['content']) && $currentUser['id'] == $message['sender_id']) {
                $updateFields[] = "content = ?";
                $params[] = $input['content'];
                $updateFields[] = "updated_at = CURRENT_TIMESTAMP";
            }
            
            if (empty($updateFields)) {
                echo json_encode(['status' => 'error', 'message' => 'No fields to update']);
                exit;
            }
            
            $params[] = $input['id'];
            
            $stmt = $pdo->prepare("UPDATE messages SET " . implode(', ', $updateFields) . " WHERE id = ?");
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Message updated successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to update message']);
            }
            break;
            
        case 'DELETE':
            // Delete message (sender or admin only)
            if (!isset($input['id'])) {
                echo json_encode(['status' => 'error', 'message' => 'Message ID is required']);
                exit;
            }
            
            $currentUser = checkAuth();
            
            // Get message details
            $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = ?");
            $stmt->execute([$input['id']]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$message) {
                echo json_encode(['status' => 'error', 'message' => 'Message not found']);
                exit;
            }
            
            // Check permissions (only sender or admin can delete)
            if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $message['sender_id']) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete this message']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $result = $stmt->execute([$input['id']]);
            
            if ($result) {
                echo json_encode(['status' => 'success', 'message' => 'Message deleted successfully']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete message']);
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
