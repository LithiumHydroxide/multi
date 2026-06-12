<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$schoolId = $input['school_id'] ?? null;
$status = $input['status'] ?? null;

if (!$schoolId || !in_array($status, ['active', 'suspended', 'trial'])) {
    http_response_code(400); echo json_encode(['error' => 'Invalid input']); exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("UPDATE schools SET status = ? WHERE id = ?");
$stmt->execute([$status, $schoolId]);

// Log action
$auditStmt = $pdo->prepare("INSERT INTO audit_logs (school_id, user_id, action, table_name, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?, ?)");
$auditStmt->execute([
    $schoolId, getAuthenticatedUserId(), 'status_change', 'schools', $schoolId, null, json_encode(['new_status' => $status])
]);

echo json_encode(['success' => true]);
?>