<?php
class Post {
    private $conn;
    private $table = 'posts';

    public $id;
    public $user_id;
    public $content;
    public $image;
    public $privacy;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, content, image, privacy) 
                  VALUES (:user_id, :content, :image, :privacy)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':image', $this->image);
        $stmt->bindParam(':privacy', $this->privacy);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getFeed($user_id, $limit = 20, $offset = 0) {
        $query = "SELECT p.*, u.username, u.first_name, u.last_name, u.profile_picture
                  FROM " . $this->table . " p
                  INNER JOIN users u ON p.user_id = u.id
                  WHERE p.privacy = 'public' 
                  OR p.user_id = :user_id
                  OR (p.privacy = 'friends' AND p.user_id IN (
                      SELECT friend_id FROM friendships WHERE user_id = :user_id AND status = 'accepted'
                      UNION
                      SELECT user_id FROM friendships WHERE friend_id = :user_id AND status = 'accepted'
                  ))
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET content = :content, privacy = :privacy 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':content', $this->content);
        $stmt->bindParam(':privacy', $this->privacy);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':user_id', $this->user_id);
        
        return $stmt->execute();
    }
}
