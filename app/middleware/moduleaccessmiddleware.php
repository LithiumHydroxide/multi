<?php
/**
 * Feature & Module Gating Middleware
 * Checks active subscription plan & enabled modules before allowing access
 */
class ModuleAccessMiddleware {
    
    /**
     * Check if current school has access to a specific module/feature
     * Usage: ModuleAccessMiddleware::requireFeature('ai_timetable');
     */
    public static function requireFeature($featureKey) {
        AuthMiddleware::requireAuth();
        
        $schoolId = getAuthenticatedSchoolId();
        if (!$schoolId) return; // Super admin bypass handled elsewhere
        
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT sp.features_json, ss.status 
            FROM school_subscriptions ss
            JOIN subscription_plans sp ON ss.plan_id = sp.id
            WHERE ss.school_id = ?
        ");
        $stmt->execute([$schoolId]);
        $sub = $stmt->fetch();
        
        if (!$sub || $sub['status'] !== 'active') {
            http_response_code(402);
            self::renderUpgradePrompt($featureKey, 'Your subscription is inactive or expired.');
            exit;
        }
        
        $features = json_decode($sub['features_json'], true) ?: [];
        if (empty($features[$featureKey])) {
            http_response_code(403);
            self::renderUpgradePrompt($featureKey);
            exit;
        }
    }
    
    /**
     * Render sleek upgrade prompt instead of raw error
     */
    private static function renderUpgradePrompt($feature, $message = null) {
        $upgradeMessages = [
            'ai_timetable' => 'AI Timetable Generator is a Premium feature. Upgrade to save 10+ hours on scheduling.',
            'advanced_analytics' => 'Predictive insights & strand analysis require a Premium plan.',
            'parent_portal' => 'Parent access & SMS notifications are available on Premium plans.'
        ];
        
        $msg = $message ?? ($upgradeMessages[$feature] ?? 'This feature requires a plan upgrade.');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Feature Locked</title>
        <style>body{font-family:system-ui;background:#f8fafc;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}.card{background:#fff;padding:32px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);max-width:420px;text-align:center}h1{color:#dc2626;font-size:1.2rem;margin-bottom:8px}p{color:#64748b;margin-bottom:20px}.btn{background:#1e40af;color:#fff;padding:10px 20px;border:none;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block}.btn:hover{background:#1d4ed8}</style></head>
        <body><div class="card"><h1>🔒 Feature Locked</h1><p><?= htmlspecialchars($msg) ?></p><a href="/multi/views/admin/subscription.php" class="btn">Upgrade Plan</a><br><br><a href="javascript:history.back()" style="color:#64748b;font-size:0.85rem;text-decoration:none">← Go Back</a></div></body></html>
        <?php
    }
}

// Global helper
if (!function_exists('getActiveModules')) {
    function getActiveModules() {
        $schoolId = getAuthenticatedSchoolId();
        if (!$schoolId) return ['mis' => true]; // Super admin gets all
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT sp.features_json FROM school_subscriptions ss JOIN subscription_plans sp ON ss.plan_id = sp.id WHERE ss.school_id = ? AND ss.status = 'active'");
        $stmt->execute([$schoolId]);
        $row = $stmt->fetch();
        return $row ? json_decode($row['features_json'], true) : [];
    }
}
?>