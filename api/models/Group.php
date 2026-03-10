<?php
class Group {
    private $conn;
    private $table = 'groups';

    public $id;
    public $name;
    public $description;
    public $creator_id;
    public $member_limit;
    public $privacy;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (name, description, creator_id, member_limit, privacy) 
                  VALUES (:name, :description, :creator_id, :member_limit, :privacy)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':creator_id', $this->creator_id);
        $stmt->bindParam(':member_limit', $this->member_limit);
        $stmt->bindParam(':privacy', $this->privacy);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            
            // Add creator as admin member
            $memberQuery = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'admin')";
            $memberStmt = $this->conn->prepare($memberQuery);
            $memberStmt->bindParam(':group_id', $this->id);
            $memberStmt->bindParam(':user_id', $this->creator_id);
            $memberStmt->execute();
            
            return true;
        }
        return false;
    }

    public function addMember($user_id) {
        // Check member limit
        $countQuery = "SELECT COUNT(*) as count FROM group_members WHERE group_id = :group_id";
        $countStmt = $this->conn->prepare($countQuery);
        $countStmt->bindParam(':group_id', $this->id);
        $countStmt->execute();
        $count = $countStmt->fetch()['count'];
        
        if($count >= $this->member_limit) {
            return false;
        }
        
        $query = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'member')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $this->id);
        $stmt->bindParam(':user_id', $user_id);
        
        return $stmt->execute();
    }

    public function getMembers() {
        $query = "SELECT u.id, u.username, u.first_name, u.last_name, u.profile_picture, gm.role
                  FROM group_members gm
                  INNER JOIN users u ON gm.user_id = u.id
                  WHERE gm.group_id = :group_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':group_id', $this->id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function updateMemberLimit($new_limit) {
        $query = "UPDATE " . $this->table . " SET member_limit = :member_limit WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':member_limit', $new_limit);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
}
