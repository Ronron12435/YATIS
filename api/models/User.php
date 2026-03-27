<?php
class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $username;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $profile_picture;
    public $bio;
    public $is_private;
    public $role;
    public $is_premium;
    public $premium_expires_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (username, email, password, first_name, last_name, role, is_private) 
                  VALUES (:username, :email, :password, :first_name, :last_name, :role, :is_private)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':is_private', $this->is_private);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username OR email = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->role = $row['role'];
                $this->is_premium = $row['is_premium'];
                return true;
            }
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET first_name = :first_name, 
                      last_name = :last_name, 
                      bio = :bio, 
                      is_private = :is_private,
                      profile_picture = :profile_picture
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':is_private', $this->is_private);
        $stmt->bindParam(':profile_picture', $this->profile_picture);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function upgradeToPremium($duration_months = 1) {
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));
        
        $query = "UPDATE " . $this->table . " 
                  SET is_premium = 1, premium_expires_at = :expires_at 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':expires_at', $expires_at);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function search($keyword) {
        $query = "SELECT id, username, first_name, last_name, profile_picture, is_private, role 
                  FROM " . $this->table . " 
                  WHERE (username LIKE :keyword OR first_name LIKE :keyword OR last_name LIKE :keyword)
                  LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getAllUsers($limit = 50) {
        $query = "SELECT id, username, first_name, last_name, profile_picture, is_private, role 
                  FROM " . $this->table . " 
                  ORDER BY username ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
