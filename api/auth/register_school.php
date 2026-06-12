<?php
require_once '../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$schoolName = trim($input['school_name'] ?? '');
$county = trim($input['county'] ?? '');
$adminName = trim($input['admin_name'] ?? '');
$adminEmail = strtolower(trim($input['admin_email'] ?? ''));
$adminPass = $input['admin_password'] ?? '';

if (!$schoolName || !$adminName || !$adminEmail || !$adminPass) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required']);
    exit;
}

if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email address']);
    exit;
}

if (strlen($adminPass) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 8 characters']);
    exit;
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Create School
    $stmt = $pdo->prepare("INSERT INTO schools (name, county, contact_email, status) VALUES (?, ?, ?, 'trial')");
    $stmt->execute([$schoolName, $county, $adminEmail]);
    $schoolId = $pdo->lastInsertId();

    // 2. Create Admin User
    $hash = password_hash($adminPass, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (school_id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'school_admin', 'active')");
    $stmt->execute([$schoolId, $adminName, $adminEmail, $hash]);

    // 3. Activate Free Subscription
    $stmt = $pdo->prepare("INSERT INTO school_subscriptions (school_id, plan_id, status, start_date, end_date, auto_renew) VALUES (?, 1, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 0)");
    $stmt->execute([$schoolId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'School registered successfully. You can now login.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed', 'debug' => APP_ENV === 'local' ? $e->getMessage() : null]);
}
?>