<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../api/models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
        
        if (!$this->db) {
            throw new Exception("Database connection failed");
        }
        
        $this->user = new User($this->db);
    }

    public function register($data) {
        try {
            // Check if username already exists
            $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $this->db->prepare($checkQuery);
            $stmt->bindParam(':username', $data['username']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            $this->user->username = $data['username'];
            $this->user->email = $data['email'];
            $this->user->password = $data['password'];
            $this->user->first_name = $data['first_name'];
            $this->user->last_name = $data['last_name'];
            $this->user->role = $data['role'] ?? 'user';
            $this->user->is_private = $data['is_private'] ?? false;

            if($this->user->create()) {
                return ['success' => true, 'message' => 'User registered successfully', 'user_id' => $this->user->id];
            }
            return ['success' => false, 'message' => 'Registration failed'];
        } catch(Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function login($username, $password) {
        if($this->user->login($username, $password)) {
            // Ensure session is started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $this->user->id;
            $_SESSION['username'] = $this->user->username;
            $_SESSION['role'] = $this->user->role;
            $_SESSION['is_premium'] = $this->user->is_premium;
            
            // Debug: verify session was set
            error_log("Session set - User ID: " . $_SESSION['user_id'] . ", Username: " . $_SESSION['username']);
            
            return ['success' => true, 'message' => 'Login successful', 'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'role' => $this->user->role,
                'is_premium' => $this->user->is_premium
            ]];
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public function logout() {
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Log the logout action
        error_log("Logout called for user: " . ($_SESSION['user_id'] ?? 'unknown'));
        
        // Clear all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 3600,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destroy the session
        @session_destroy();
        
        error_log("Session destroyed");
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}
