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
        SELECT u.id, u.name, u.email, u.status, t.tsc_number, t.specialization,
               GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as subjects,
               GROUP_CONCAT(DISTINCT CASE 
                   WHEN c.class_teacher_id = t.id THEN CONCAT(c.name, ' (Class Teacher)')
                   WHEN c.assistant_teacher_id = t.id THEN CONCAT(c.name, ' (Assistant)')
               END SEPARATOR ' | ') as class_assignments
        FROM users u
        LEFT JOIN teachers t ON u.id = t.user_id AND u.school_id = t.school_id
        LEFT JOIN class_subject_teacher cst ON t.id = cst.teacher_id
        LEFT JOIN subjects s ON cst.subject_id = s.id
        LEFT JOIN classes c ON (c.class_teacher_id = t.id OR c.assistant_teacher_id = t.id) AND c.school_id = t.school_id
        WHERE u.school_id = ? AND u.role = 'teacher'
        GROUP BY u.id ORDER BY u.name
    ");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name']); $email = strtolower(trim($input['email']));
    $pass = $input['password']; $tsc = trim($input['tsc_number'] ?? '');
    $spec = trim($input['specialization'] ?? '');
    if (!$name || !$email || strlen($pass) < 8) { http_response_code(400); echo json_encode(['error'=>'Name, email, 8+ char password required']); exit; }

    $pdo->beginTransaction();
    try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (school_id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'teacher', 'active')")
            ->execute([$schoolId, $name, $email, $hash]);
        $uid = $pdo->lastInsertId();
        if ($tsc || $spec) $pdo->prepare("INSERT INTO teachers (user_id, school_id, tsc_number, specialization) VALUES (?, ?, ?, ?)")
            ->execute([$uid, $schoolId, $tsc, $spec]);
        $pdo->commit(); echo json_encode(['success'=>true]);
    } catch (Exception $e) { $pdo->rollBack(); http_response_code(500); echo json_encode(['error'=>'Failed']); }
    exit;
}

if ($action === 'view') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT u.id, u.name, u.email, u.status, t.tsc_number, t.specialization FROM users u LEFT JOIN teachers t ON u.id = t.user_id WHERE u.id = ? AND u.school_id = ? AND u.role = 'teacher' LIMIT 1");
    $stmt->execute([$id, $schoolId]);
    echo json_encode($stmt->fetch());
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $tsc = trim($input['tsc_number'] ?? '');
    $spec = trim($input['specialization'] ?? '');

    if (!$id || !$name || !$email) { http_response_code(400); echo json_encode(['error'=>'ID, Name, and Email required']); exit; }

    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND school_id = ?")
            ->execute([$name, $email, $id, $schoolId]);
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ? AND school_id = ?");
        $stmt->execute([$id, $schoolId]);
        $teacher = $stmt->fetch();
        if ($teacher) {
            $pdo->prepare("UPDATE teachers SET tsc_number = ?, specialization = ? WHERE id = ? AND school_id = ?")
                ->execute([$tsc, $spec, $teacher['id'], $schoolId]);
        } else {
            $pdo->prepare("INSERT INTO teachers (user_id, school_id, tsc_number, specialization) VALUES (?, ?, ?, ?)")
                ->execute([$id, $schoolId, $tsc, $spec]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update teacher']);
    }
    exit;
}

if ($action === 'toggle') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo->prepare("UPDATE users SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id=? AND school_id=?")
        ->execute([$input['id'], $schoolId]);
    echo json_encode(['success'=>true]); exit;
}

if ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    // Soft delete or hard delete logic here
    $pdo->prepare("DELETE FROM teachers WHERE id = ? AND school_id = ?")->execute([$input['id'], $schoolId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400); echo json_encode(['error'=>'Invalid action']);
?>