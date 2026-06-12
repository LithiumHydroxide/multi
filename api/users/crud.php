<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.email, u.status, u.role, t.tsc_number, t.specialization,
               GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as assigned_classes
        FROM users u
        LEFT JOIN teachers t ON u.id = t.user_id AND u.school_id = t.school_id
        LEFT JOIN class_subject_teacher cst ON t.id = cst.teacher_id
        LEFT JOIN classes c ON cst.class_id = c.id
        WHERE u.school_id = ? AND u.role = 'teacher'
        GROUP BY u.id ORDER BY u.name
    ");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $pass = $input['password'] ?? '';
    $tsc = trim($input['tsc_number'] ?? '');
    $spec = trim($input['specialization'] ?? '');
    
    if (!$name || !$email || !$pass) { http_response_code(400); echo json_encode(['error' => 'Name, email, and password required']); exit; }
    if (strlen($pass) < 8) { http_response_code(400); echo json_encode(['error' => 'Password min 8 chars']); exit; }

    $pdo->beginTransaction();
    try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (school_id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'teacher', 'active')");
        $stmt->execute([$schoolId, $name, $email, $hash]);
        $userId = $pdo->lastInsertId();
        
        if ($tsc || $spec) {
            $stmt = $pdo->prepare("INSERT INTO teachers (user_id, school_id, tsc_number, specialization) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $schoolId, $tsc, $spec]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create teacher']);
    }
    exit;
}

if ($action === 'toggle_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $pdo->prepare("UPDATE users SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND school_id = ?")->execute([$userId, $schoolId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid action']);
?>