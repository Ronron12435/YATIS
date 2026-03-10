<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Friendship.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$friendship = new Friendship($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'send_request') {
            $friendship->user_id = Auth::getUserId();
            $friendship->friend_id = $data['friend_id'];
            
            if($friendship->sendRequest()) {
                echo json_encode(['success' => true, 'message' => 'Friend request sent']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send request. You may have already sent a request or are already friends.']);
            }
        } elseif($action === 'accept_request') {
            if(!isset($data['request_id'])) {
                echo json_encode(['success' => false, 'message' => 'Request ID is required', 'debug' => 'no_request_id']);
                exit;
            }
            
            $friendship->id = $data['request_id'];
            
            // Log the incoming request for debugging
            error_log("Accept request attempt - Request ID: " . $friendship->id . ", Current User: " . Auth::getUserId());
            
            // Check if request exists before attempting to accept
            $checkQuery = "SELECT f.*, u1.username as sender_name, u2.username as recipient_name 
                          FROM friendships f
                          LEFT JOIN users u1 ON f.user_id = u1.id
                          LEFT JOIN users u2 ON f.friend_id = u2.id
                          WHERE f.id = :id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':id', $friendship->id);
            $checkStmt->execute();
            $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            // Log what we found
            error_log("Database check result: " . json_encode($existingRequest));
            
            if(!$existingRequest) {
                // Request doesn't exist - let's check if it ever existed
                $allRequests = $db->query("SELECT id, user_id, friend_id, status FROM friendships ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                error_log("All friendships in database: " . json_encode($allRequests));
                
                echo json_encode([
                    'success' => false, 
                    'message' => 'Friend request not found in database. The request may have been deleted.',
                    'debug' => [
                        'requested_id' => $friendship->id,
                        'current_user' => Auth::getUserId(),
                        'note' => 'Request ID does not exist in friendships table',
                        'all_requests' => $allRequests
                    ]
                ]);
                exit;
            }
            
            // Check if users still exist
            if(!$existingRequest['sender_name'] || !$existingRequest['recipient_name']) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'One or both users no longer exist',
                    'debug' => 'orphaned_users'
                ]);
                exit;
            }
            
            if($existingRequest['status'] === 'accepted') {
                echo json_encode(['success' => false, 'message' => 'Friend request already accepted', 'debug' => 'already_accepted']);
                exit;
            }
            
            // Verify current user is the recipient
            if($existingRequest['friend_id'] != Auth::getUserId()) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You are not authorized to accept this request',
                    'debug' => [
                        'current_user' => Auth::getUserId(),
                        'recipient' => $existingRequest['friend_id']
                    ]
                ]);
                exit;
            }
            
            if($friendship->acceptRequest()) {
                echo json_encode(['success' => true, 'message' => 'Friend request accepted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to accept request. Please try again.', 'debug' => 'update_failed']);
            }
        } elseif($action === 'reject_request') {
            $query = "DELETE FROM friendships WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data['request_id']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Friend request rejected']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reject request']);
            }
        } elseif($action === 'unfriend') {
            $currentUserId = Auth::getUserId();
            $friendId = $data['friend_id'];
            
            // Delete the friendship (works both ways due to the query)
            $query = "DELETE FROM friendships 
                     WHERE (user_id = :user1 AND friend_id = :user2) 
                        OR (user_id = :user2 AND friend_id = :user1)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user1', $currentUserId);
            $stmt->bindParam(':user2', $friendId);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Successfully unfriended']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to unfriend']);
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            $friends = $friendship->getFriends(Auth::getUserId());
            echo json_encode(['success' => true, 'friends' => $friends]);
        } elseif($action === 'pending') {
            $requests = $friendship->getPendingRequests(Auth::getUserId());
            error_log("Pending requests for user " . Auth::getUserId() . ": " . json_encode($requests));
            echo json_encode(['success' => true, 'requests' => $requests]);
        } elseif($action === 'sent') {
            $requests = $friendship->getSentRequests(Auth::getUserId());
            echo json_encode(['success' => true, 'requests' => $requests]);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
