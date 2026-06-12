<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('super_admin');
header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

if ($action === 'clear_audit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->exec("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
