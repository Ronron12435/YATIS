<?php
// Application Configuration
define('APP_NAME', 'YATIS');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/yatis/');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('RESUME_DIR', UPLOAD_DIR . 'resumes/');
define('PROFILE_DIR', UPLOAD_DIR . 'profiles/');
define('PROFILE_PHOTOS_DIR', UPLOAD_DIR . 'profile_photos/');
define('BUSINESS_DIR', UPLOAD_DIR . 'business/');
define('MENU_ITEMS_DIR', UPLOAD_DIR . 'menu_items/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Subscription Settings
define('FREE_GROUP_LIMIT', 50);
define('PREMIUM_GROUP_LIMIT', 500);

// Session Settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('UTC');
