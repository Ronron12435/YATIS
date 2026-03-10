<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/GroupMessage.php';
require_once __DIR__ . '/../models/PrivateMessage.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$groupMessage = new GroupMessage($db);
$privateMessage = new PrivateMessage($db);

$method = $_SERVER['REQUEST_METHOD'];
$currentUserId = Auth::getUserId();

try {
    if($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'send_group_message') {
            // Validate required fields
            if(empty($data['group_id']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Group ID and content are required']);
                exit;
            }
            
            $group_id = intval($data['group_id']);
            
            // Verify user is a member of the group
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
            $stmt->execute([':group_id' => $group_id, ':user_id' => $currentUserId]);
            
            if($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
                exit;
            }
            
            // Create message
            $groupMessage->group_id = $group_id;
            $groupMessage->sender_id = $currentUserId;
            $groupMessage->content = $data['content'];
            
            if($groupMessage->create()) {
                // Get sender information
                $stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $currentUserId]);
                $sender = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => [
                        'id' => $groupMessage->id,
                        'group_id' => $group_id,
                        'sender_id' => $currentUserId,
                        'content' => $groupMessage->content,
                        'created_at' => date('Y-m-d H:i:s'),
                        'sender_name' => $sender['first_name'] . ' ' . $sender['last_name'],
                        'profile_picture' => $sender['profile_picture']
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
            
        } elseif($action === 'send_private_message') {
            // Validate required fields
            if(empty($data['receiver_id']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Receiver ID and content are required']);
                exit;
            }
            
            $receiver_id = intval($data['receiver_id']);
            
            // Check if users are friends
            $stmt = $db->prepare("SELECT COUNT(*) FROM friendships 
                                 WHERE ((user_id = :user1 AND friend_id = :user2) 
                                     OR (user_id = :user2 AND friend_id = :user1))
                                 AND status = 'accepted'");
            $stmt->execute([':user1' => $currentUserId, ':user2' => $receiver_id]);
            
            if($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You can only message friends']);
                exit;
            }
            
            // Create message
            $privateMessage->sender_id = $currentUserId;
            $privateMessage->receiver_id = $receiver_id;
            $privateMessage->content = $data['content'];
            
            if($privateMessage->create()) {
                // Get sender information
                $stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :user_id");
                $stmt->execute([':user_id' => $currentUserId]);
                $sender = $stmt->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => [
                        'id' => $privateMessage->id,
                        'sender_id' => $currentUserId,
                        'receiver_id' => $receiver_id,
                        'content' => $privateMessage->content,
                        'is_read' => false,
                        'created_at' => date('Y-m-d H:i:s'),
                        'sender_name' => $sender['first_name'] . ' ' . $sender['last_name'],
                        'profile_picture' => $sender['profile_picture']
                    ]
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to send message']);
            }
            
        } elseif($action === 'mark_as_read') {
            // Validate required fields
            if(empty($data['other_user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Other user ID is required']);
                exit;
            }
            
            $other_user_id = intval($data['other_user_id']);
            $marked_count = $privateMessage->markAsRead($currentUserId, $other_user_id);
            
            echo json_encode([
                'success' => true,
                'marked_count' => $marked_count
            ]);
            
        } elseif($action === 'mark_group_as_read') {
            // Validate required fields
            if(empty($data['group_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Group ID is required']);
                exit;
            }
            
            $group_id = intval($data['group_id']);
            
            // Verify user is a member of the group
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
            $stmt->execute([':group_id' => $group_id, ':user_id' => $currentUserId]);
            
            if($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
                exit;
            }
            
            // Get the latest message ID in this group
            $stmt = $db->prepare("SELECT MAX(id) FROM group_messages WHERE group_id = :group_id");
            $stmt->execute([':group_id' => $group_id]);
            $last_message_id = $stmt->fetchColumn();
            
            if($last_message_id) {
                // Insert or update the read tracking
                $stmt = $db->prepare("
                    INSERT INTO group_message_reads (user_id, group_id, last_read_message_id, last_read_at)
                    VALUES (:user_id, :group_id, :last_message_id, NOW())
                    ON DUPLICATE KEY UPDATE 
                        last_read_message_id = :last_message_id,
                        last_read_at = NOW()
                ");
                $stmt->execute([
                    ':user_id' => $currentUserId,
                    ':group_id' => $group_id,
                    ':last_message_id' => $last_message_id
                ]);
                
                echo json_encode([
                    'success' => true,
                    'last_read_message_id' => $last_message_id
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'No messages to mark as read'
                ]);
            }
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'get_group_messages') {
            // Validate required fields
            if(empty($_GET['group_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Group ID is required']);
                exit;
            }
            
            $group_id = intval($_GET['group_id']);
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            $since = $_GET['since'] ?? null;
            
            // Verify user is a member of the group
            $stmt = $db->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id");
            $stmt->execute([':group_id' => $group_id, ':user_id' => $currentUserId]);
            
            if($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
                exit;
            }
            
            // Get messages
            if($since) {
                $messages = $groupMessage->getNewMessages($group_id, $since);
            } else {
                $messages = $groupMessage->getByGroup($group_id, $limit, $offset);
            }
            
            // Check if there are more messages
            $has_more = false;
            if(!$since && count($messages) === $limit) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM group_messages WHERE group_id = :group_id");
                $stmt->execute([':group_id' => $group_id]);
                $total = $stmt->fetchColumn();
                $has_more = ($offset + $limit) < $total;
            }
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'has_more' => $has_more
            ]);
            
        } elseif($action === 'get_private_messages') {
            // Validate required fields
            if(empty($_GET['other_user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Other user ID is required']);
                exit;
            }
            
            $other_user_id = intval($_GET['other_user_id']);
            
            // CRITICAL: Ensure current user is actually requesting THEIR OWN conversation
            // This prevents Test from seeing Jayson-Kelir messages
            // The conversation must include the current user as either sender or receiver
            
            // Check if users are friends
            $stmt = $db->prepare("SELECT COUNT(*) FROM friendships 
                                 WHERE ((user_id = :user1 AND friend_id = :user2) 
                                     OR (user_id = :user2 AND friend_id = :user1))
                                 AND status = 'accepted'");
            $stmt->execute([':user1' => $currentUserId, ':user2' => $other_user_id]);
            
            if($stmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You can only message friends']);
                exit;
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            $since = $_GET['since'] ?? null;
            
            // Get messages - this will ONLY return messages between currentUser and other_user
            // It's impossible to get messages between two OTHER users
            if($since) {
                $messages = $privateMessage->getNewMessages($currentUserId, $other_user_id, $since);
            } else {
                $messages = $privateMessage->getConversation($currentUserId, $other_user_id, $limit, $offset);
            }
            
            // Check if there are more messages
            $has_more = false;
            if(!$since && count($messages) === $limit) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM private_messages 
                                     WHERE (sender_id = :user1 AND receiver_id = :user2) 
                                        OR (sender_id = :user2 AND receiver_id = :user1)");
                $stmt->execute([':user1' => $currentUserId, ':user2' => $other_user_id]);
                $total = $stmt->fetchColumn();
                $has_more = ($offset + $limit) < $total;
            }
            
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'has_more' => $has_more
            ]);
            
        } elseif($action === 'get_unread_counts') {
            $unread_counts = $privateMessage->getUnreadCounts($currentUserId);
            
            echo json_encode([
                'success' => true,
                'unread_counts' => $unread_counts
            ]);
            
        } elseif($action === 'get_group_unread_counts') {
            // Get unread counts for all groups the user is a member of
            $stmt = $db->prepare("
                SELECT 
                    gm.group_id,
                    COUNT(msg.id) as unread_count
                FROM group_members gm
                LEFT JOIN group_message_reads gmr ON gmr.group_id = gm.group_id AND gmr.user_id = gm.user_id
                LEFT JOIN group_messages msg ON msg.group_id = gm.group_id 
                    AND (gmr.last_read_message_id IS NULL OR msg.id > gmr.last_read_message_id)
                    AND msg.sender_id != gm.user_id
                WHERE gm.user_id = :user_id
                GROUP BY gm.group_id
            ");
            $stmt->execute([':user_id' => $currentUserId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $unread_counts = [];
            foreach($results as $row) {
                $unread_counts[$row['group_id']] = intval($row['unread_count']);
            }
            
            echo json_encode([
                'success' => true,
                'unread_counts' => $unread_counts
            ]);
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
