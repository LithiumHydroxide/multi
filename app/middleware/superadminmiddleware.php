<?php
/**
 * Super Admin Only Middleware
 * Ensures only role='super_admin' can access platform-wide management
 */
class SuperAdminMiddleware {
    
    public static function requireSuperAdmin() {
        AuthMiddleware::requireAuth();
        if (getAuthenticatedUserRole() !== 'super_admin') {
            http_response_code(403);
            die('<h1>Access Denied</h1><p>Platform administrator access required.</p><a href="/multi/views/auth/login.php">Login</a>');
        }
    }
    
    /**
     * Impersonate a school admin for support (with audit trail)
     */
    public static function impersonateSchool($schoolId) {
        self::requireSuperAdmin();
        
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id, name FROM schools WHERE id = ? AND status = 'active'");
        $stmt->execute([$schoolId]);
        $school = $stmt->fetch();
        
        if (!$school) {
            http_response_code(404);
            return ['success' => false, 'error' => 'School not found or inactive'];
        }
        
        // Log impersonation for audit
        $auditStmt = $pdo->prepare("INSERT INTO audit_logs (school_id, user_id, action, table_name, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $auditStmt->execute([
            $schoolId, 
            getAuthenticatedUserId(), 
            'impersonate_start', 
            'schools', 
            $schoolId, 
            null, 
            json_encode(['impersonated_by' => getAuthenticatedUserId(), 'at' => date('c')])
        ]);
        
        // Store original super admin session for return
        $_SESSION['impersonating'] = [
            'original_user_id' => $_SESSION['user_id'],
            'original_role' => $_SESSION['role'],
            'original_school_id' => $_SESSION['school_id'] ?? null,
            'school_id' => $schoolId,
            'started_at' => time()
        ];
        
        // Switch session to school admin context
        $_SESSION['school_id'] = $schoolId;
        $_SESSION['role'] = 'school_admin'; // Temporarily act as school admin
        $_SESSION['impersonated_school_name'] = $school['name'];
        
        return ['success' => true, 'school_name' => $school['name']];
    }
    
    /**
     * Exit impersonation mode
     */
    public static function stopImpersonation() {
        if (!isset($_SESSION['impersonating'])) return false;
        
        $pdo = getDBConnection();
        $auditStmt = $pdo->prepare("INSERT INTO audit_logs (school_id, user_id, action, table_name, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $auditStmt->execute([
            $_SESSION['impersonating']['school_id'], 
            $_SESSION['impersonating']['original_user_id'], 
            'impersonate_end', 
            'schools', 
            $_SESSION['impersonating']['school_id'], 
            null, 
            json_encode(['ended_at' => date('c')])
        ]);
        
        // Restore original session
        $_SESSION['user_id'] = $_SESSION['impersonating']['original_user_id'];
        $_SESSION['role'] = $_SESSION['impersonating']['original_role'];
        $_SESSION['school_id'] = $_SESSION['impersonating']['original_school_id'];
        unset($_SESSION['impersonating'], $_SESSION['impersonated_school_name']);
        
        return true;
    }
}
?>