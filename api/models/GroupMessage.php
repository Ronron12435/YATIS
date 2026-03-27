<?php
class GroupMessage {
    private $conn;
    private $table = 'group_messages';
    
    public $id;
    public $group_id;
    public $sender_id;
    public $content;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (group_id, sender_id, content) 
                  VALUES (:group_id, :sender_id, :content)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->content = htmlspecialchars(strip_tags($this->content));
        
        $stmt->bindParam(':group_id', $this->group_id);
        $stmt->bindParam(':sender_id', $this->sender_id);
        $stmt->bindParam(':content', $this->content);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function getByGroup($group_id, $limit = 50, $offset = 0) {
        $query = "SELECT gm.*, u.first_name, u.last_name, u.profile_picture,
                  CONCAT(u.first_name, ' ', u.last_name) as sender_name
                  FROM " . $this->table . " gm
                  JOIN users u ON gm.sender_id = u.id
                  WHERE gm.group_id = :group_id
                  ORDER BY gm.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getNewMessages($group_id, $since_timestamp) {
        $query = "SELECT gm.*, u.first_name, u.last_name, u.profile_picture,
                  CONCAT(u.first_name, ' ', u.last_name) as sender_name
                  FROM " . $this->table . " gm
                  JOIN users u ON gm.sender_id = u.id
                  WHERE gm.group_id = :group_id AND gm.created_at > :since
                  ORDER BY gm.created_at ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $group_id, PDO::PARAM_INT);
        $stmt->bindParam(':since', $since_timestamp);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
