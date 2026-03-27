<?php
class Friendship {
    private $conn;
    private $table = 'friendships';

    public $id;
    public $user_id;
    public $friend_id;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function sendRequest() {
        // Check if a friendship already exists (in either direction)
        $checkQuery = "SELECT id FROM " . $this->table . " 
                      WHERE (user_id = :user_id AND friend_id = :friend_id) 
                         OR (user_id = :friend_id AND friend_id = :user_id)";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $this->user_id);
        $checkStmt->bindParam(':friend_id', $this->friend_id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return false; // Friendship or request already exists
        }
        
        $query = "INSERT INTO " . $this->table . " (user_id, friend_id, status) VALUES (:user_id, :friend_id, 'pending')";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':friend_id', $this->friend_id);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function acceptRequest() {
        $query = "UPDATE " . $this->table . " SET status = 'accepted' WHERE id = :id AND status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    public function getFriends($user_id) {
        $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_picture 
                  FROM users u
                  WHERE u.id IN (
                      SELECT friend_id FROM " . $this->table . " WHERE user_id = :user_id AND status = 'accepted'
                      UNION
                      SELECT user_id FROM " . $this->table . " WHERE friend_id = :user_id AND status = 'accepted'
                  )
                  ORDER BY u.first_name, u.last_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingRequests($user_id) {
        $query = "SELECT f.id, f.user_id, u.username, u.first_name, u.last_name, u.profile_picture 
                  FROM " . $this->table . " f
                  INNER JOIN users u ON f.user_id = u.id
                  WHERE f.friend_id = :user_id AND f.status = 'pending'
                  ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSentRequests($user_id) {
        $query = "SELECT f.id, f.friend_id as user_id, u.username, u.first_name, u.last_name, u.profile_picture 
                  FROM " . $this->table . " f
                  INNER JOIN users u ON f.friend_id = u.id
                  WHERE f.user_id = :user_id AND f.status = 'pending'
                  ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
