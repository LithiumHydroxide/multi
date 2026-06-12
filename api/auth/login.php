<?php
require_once '../../config.php';
require_once '../../app/Helpers/AuthMiddleware.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT id, school_id, name, password_hash, role, status FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

if ($user['status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['error' => 'Account is inactive or suspended. Contact admin.']);
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Secure session regeneration
session_regenerate_id(true);

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['school_id'] = $user['school_id']; // NULL for super_admin
$_SESSION['role'] = $user['role'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['last_activity'] = time();

// Update last login timestamp
$updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$updateStmt->execute([$user['id']]);

// Role-based redirect
$redirect = match($user['role']) {
    'super_admin' => '/multi/views/super_admin/dashboard.php',
    'school_admin' => '/multi/views/admin/dashboard.php',
    'teacher' => '/multi/views/teacher/dashboard.php',
    default => '/multi/views/teacher/dashboard.php'
};

// After successful login, add this check:
if ($user['role'] === 'school_admin') {
    $schoolStmt = $pdo->prepare("SELECT onboarding_completed FROM schools WHERE id = ?");
    $schoolStmt->execute([$user['school_id']]);
    $school = $schoolStmt->fetch();
    
    if (!$school['onboarding_completed']) {
        $redirect = '/multi/views/admin/onboarding.php';
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'role' => $user['role'],
    'redirect' => $redirect
]);
?>