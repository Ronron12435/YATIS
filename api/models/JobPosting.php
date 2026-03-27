<?php
class JobPosting {
    private $conn;
    private $table = 'job_postings';

    public $id;
    public $employer_id;
    public $business_id;
    public $title;
    public $description;
    public $requirements;
    public $salary_range;
    public $location;
    public $job_type;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (employer_id, business_id, title, description, requirements, salary_range, location, job_type, status) 
                  VALUES (:employer_id, :business_id, :title, :description, :requirements, :salary_range, :location, :job_type, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':employer_id', $this->employer_id);
        $stmt->bindParam(':business_id', $this->business_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':requirements', $this->requirements);
        $stmt->bindParam(':salary_range', $this->salary_range);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':job_type', $this->job_type);
        $stmt->bindParam(':status', $this->status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT jp.*, u.username as employer_name 
                  FROM " . $this->table . " jp
                  INNER JOIN users u ON jp.employer_id = u.id
                  WHERE jp.status = 'open'
                  ORDER BY jp.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getByEmployerId($employer_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE employer_id = :employer_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':employer_id', $employer_id);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function updateStatus($status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }

    public function search($keyword, $location = null) {
        $query = "SELECT jp.*, u.username as employer_name 
                  FROM " . $this->table . " jp
                  INNER JOIN users u ON jp.employer_id = u.id
                  WHERE jp.status = 'open' AND (jp.title LIKE :keyword OR jp.description LIKE :keyword)";
        
        if($location) {
            $query .= " AND jp.location LIKE :location";
        }
        
        $query .= " ORDER BY jp.created_at DESC LIMIT 20";
        
        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(':keyword', $keyword);
        
        if($location) {
            $location = "%{$location}%";
            $stmt->bindParam(':location', $location);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
