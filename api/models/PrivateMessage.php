<?php
class PrivateMessage {
    private $conn;
    private $table = 'private_messages';
    
    public $id;
    public $sender_id;
    public $receiver_id;
    public $content;
    public $is_read;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (sender_id, receiver_id, content, is_read) 
                  VALUES (:sender_id, :receiver_id, :content, FALSE)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->content = htmlspecialchars(strip_tags($this->content));
        
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':receiver_id', $this->receiver_id);
        $stmt->bindParam(':content', $this->content);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function getConversation($user1_id, $user2_id, $limit = 50, $offset = 0) {
        $query = "SELECT pm.*, u.first_name, u.last_name, u.profile_picture,
                  CONCAT(u.first_name, ' ', u.last_name) as sender_name
                  FROM " . $this->table . " pm
                  JOIN users u ON pm.sender_id = u.id
                  WHERE (pm.sender_id = :user1 AND pm.receiver_id = :user2)
                     OR (pm.sender_id = :user2 AND pm.receiver_id = :user1)
                  ORDER BY pm.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $user1_id, PDO::PARAM_INT);
        $stmt->bindParam(':user2', $user2_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getNewMessages($user1_id, $user2_id, $since_timestamp) {
        $query = "SELECT pm.*, u.first_name, u.last_name, u.profile_picture,
                  CONCAT(u.first_name, ' ', u.last_name) as sender_name
                  FROM " . $this->table . " pm
                  JOIN users u ON pm.sender_id = u.id
                  WHERE ((pm.sender_id = :user1 AND pm.receiver_id = :user2)
                     OR (pm.sender_id = :user2 AND pm.receiver_id = :user1))
                     AND pm.created_at > :since
                  ORDER BY pm.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $user1_id, PDO::PARAM_INT);
        $stmt->bindParam(':user2', $user2_id, PDO::PARAM_INT);
        $stmt->bindParam(':since', $since_timestamp);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAsRead($receiver_id, $sender_id) {
        $query = "UPDATE " . $this->table . " 
                  SET is_read = TRUE 
                  WHERE receiver_id = :receiver_id AND sender_id = :sender_id AND is_read = FALSE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':receiver_id', $receiver_id, PDO::PARAM_INT);
        $stmt->bindParam(':sender_id', $sender_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
    
    public function getUnreadCounts($user_id) {
        $query = "SELECT pm.sender_id, u.first_name, u.last_name, u.profile_picture,
                  CONCAT(u.first_name, ' ', u.last_name) as user_name,
                  COUNT(*) as unread_count,
                  MAX(pm.content) as last_message,
                  MAX(pm.created_at) as last_message_time
                  FROM " . $this->table . " pm
                  JOIN users u ON pm.sender_id = u.id
                  WHERE pm.receiver_id = :user_id AND pm.is_read = FALSE
                    AND EXISTS (
                        SELECT 1 FROM friendships f 
                        WHERE ((f.user_id = :user_id AND f.friend_id = pm.sender_id) 
                            OR (f.friend_id = :user_id AND f.user_id = pm.sender_id))
                        AND f.status = 'accepted'
                    )
                  GROUP BY pm.sender_id, u.first_name, u.last_name, u.profile_picture
                  ORDER BY last_message_time DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
