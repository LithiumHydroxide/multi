<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$planId = $input['plan_id'] ?? null;
$modules = $input['modules'] ?? []; // ['mis' => true, 'payments' => false]

if (!$planId) {
    http_response_code(400);
    echo json_encode(['error' => 'Plan ID required']);
    exit;
}

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Validate plan exists
$planStmt = $pdo->prepare("SELECT id, max_students FROM subscription_plans WHERE id = ?");
$planStmt->execute([$planId]);
$plan = $planStmt->fetch();

if (!$plan) {
    http_response_code(404);
    echo json_encode(['error' => 'Invalid plan']);
    exit;
}

// Build features JSON based on plan + selected modules
$features = json_decode($planStmt->fetchColumn(1) ? '{}' : '{}', true); // Start empty
// Default: all plans get manual timetable & basic MIS
$features['manual_timetable'] = true;
$features['mis_core'] = true;

// If payments module selected, enable payment features
if (!empty($modules['payments'])) {
    $features['payment_gateway'] = true;
}

// AI timetable only if premium/enterprise (plan ID 3 in our seed)
if ($planId == 3) {
    $features['ai_timetable'] = true;
    $features['advanced_analytics'] = true;
    $features['parent_portal'] = true;
}

$featuresJson = json_encode($features);

// Upsert subscription
$stmt = $pdo->prepare("
    INSERT INTO school_subscriptions (school_id, plan_id, status, start_date, end_date, auto_renew, current_student_count)
    VALUES (?, ?, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1, 0)
    ON DUPLICATE KEY UPDATE plan_id = VALUES(plan_id), status = 'active', end_date = VALUES(end_date), updated_at = CURRENT_TIMESTAMP
");
$stmt->execute([$schoolId, $planId]);

// Note: In Phase 3, you'll hook M-Pesa here before setting status='active'

echo json_encode([
    'success' => true,
    'message' => 'Subscription activated successfully',
    'features' => $features
]);
?>