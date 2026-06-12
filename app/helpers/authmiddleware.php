<?php
class AuthMiddleware {
    
    public static function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /multi/views/auth/login.php');
            exit;
        }
        // Session timeout: 30 minutes
        if (time() - ($_SESSION['last_activity'] ?? 0) > 1800) {
            session_unset();
            session_destroy();
            header('Location: /multi/views/auth/login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    public static function requireRole($allowedRoles) {
        self::requireAuth();
        if (!in_array($_SESSION['role'], (array)$allowedRoles)) {
            http_response_code(403);
            echo '<h1>Access Denied</h1><p>You do not have permission to view this page.</p>';
            echo '<a href="/multi/api/auth/logout.php">Logout</a>';
            exit;
        }
    }

    public static function requireSchoolScope() {
        self::requireAuth();
        if ($_SESSION['role'] !== 'super_admin' && !$_SESSION['school_id']) {
            http_response_code(403);
            die('School context required. Contact platform admin.');
        }
    }
}

// Safe global helpers
if (!function_exists('getAuthenticatedUserId')) {
    function getAuthenticatedUserId() { return $_SESSION['user_id'] ?? null; }
}
if (!function_exists('getAuthenticatedSchoolId')) {
    function getAuthenticatedSchoolId() { return $_SESSION['school_id'] ?? null; }
}
if (!function_exists('getAuthenticatedUserRole')) {
    function getAuthenticatedUserRole() { return $_SESSION['role'] ?? null; }
}
?>