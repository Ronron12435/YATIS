<?php
class JobApplication {
    private $conn;
    private $table = 'job_applications';

    public $id;
    public $job_id;
    public $user_id;
    public $resume_path;
    public $cover_letter;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (job_id, user_id, resume_path, cover_letter, status) 
                  VALUES (:job_id, :user_id, :resume_path, :cover_letter, :status)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':job_id', $this->job_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':resume_path', $this->resume_path);
        $stmt->bindParam(':cover_letter', $this->cover_letter);
        $stmt->bindParam(':status', $this->status);
        
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function getByJobId($job_id) {
        $query = "SELECT ja.*, 
                  u.username, 
                  u.first_name, 
                  u.last_name, 
                  u.email,
                  CONCAT(u.first_name, ' ', u.last_name) as applicant_name,
                  IF(ja.interview_date IS NOT NULL AND DATE(ja.interview_date) <= CURDATE(), 1, 0) as can_hire
                  FROM " . $this->table . " ja
                  INNER JOIN users u ON ja.user_id = u.id
                  WHERE ja.job_id = :job_id
                  ORDER BY ja.applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':job_id', $job_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUserId($user_id) {
        $query = "SELECT ja.*, jp.title, jp.location, u.username as employer_name 
                  FROM " . $this->table . " ja
                  INNER JOIN job_postings jp ON ja.job_id = jp.id
                  INNER JOIN users u ON jp.employer_id = u.id
                  WHERE ja.user_id = :user_id
                  ORDER BY ja.applied_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
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
}
