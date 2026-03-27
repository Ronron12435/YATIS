<?php
class Auth {
    public static function check() {
        if(!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }

    public static function checkRole($allowed_roles) {
        self::check();
        
        if(!in_array($_SESSION['role'], $allowed_roles)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }

    public static function isPremium() {
        return isset($_SESSION['is_premium']) && $_SESSION['is_premium'] == 1;
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    public static function getRole() {
        return $_SESSION['role'] ?? null;
    }
}
