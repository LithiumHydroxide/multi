<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $staffType = $_GET['staff_type'] ?? 'all';
    
    if ($staffType === 'teaching') {
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.status, u.staff_type, t.tsc_number, t.specialization,
                   GROUP_CONCAT(DISTINCT s.name SEPARATOR ', ') as subjects
            FROM users u
            LEFT JOIN teachers t ON u.id = t.user_id
            LEFT JOIN class_subject_teacher cst ON t.id = cst.teacher_id
            LEFT JOIN subjects s ON cst.subject_id = s.id
            WHERE u.school_id = ? AND u.staff_type = 'teaching'
            GROUP BY u.id ORDER BY u.name
        ");
        $stmt->execute([$schoolId]);
    } elseif ($staffType === 'non_teaching') {
        $stmt = $pdo->prepare("
            SELECT id, name, email, status, staff_type, job_role, details_json
            FROM users
            WHERE school_id = ? AND staff_type = 'non_teaching'
            ORDER BY name
        ");
        $stmt->execute([$schoolId]);
    } else {
        // All staff
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.status, u.staff_type, 
                   COALESCE(t.tsc_number, u.job_role) as role_info,
                   COALESCE(t.specialization, u.details_json) as details
            FROM users u
            LEFT JOIN teachers t ON u.id = t.user_id
            WHERE u.school_id = ? AND u.role IN ('teacher', 'staff')
            ORDER BY u.name
        ");
        $stmt->execute([$schoolId]);
    }
    
    $rows = $stmt->fetchAll();
    
    // Decode JSON details for non-teaching staff
    foreach ($rows as &$row) {
        if (isset($row['details_json'])) {
            $row['details'] = json_decode($row['details_json'], true) ?: [];
            unset($row['details_json']);
        }
    }
    
    echo json_encode($rows);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $pass = $input['password'] ?? '';
    $staffType = $input['staff_type'] ?? 'non_teaching';
    $jobRole = trim($input['job_role'] ?? '');
    $tsc = trim($input['tsc_number'] ?? '');
    $spec = trim($input['specialization'] ?? '');
    
    // Extract details[...] keys into a JSON object
    $details = [];
    foreach ($input as $key => $value) {
        if (strpos($key, 'details[') === 0) {
            $cleanKey = str_replace(['details[', ']'], '', $key);
            if (!empty($value)) {
                $details[$cleanKey] = $value;
            }
        }
    }

    if (!$name || !$email || strlen($pass) < 8) {
        http_response_code(400); 
        echo json_encode(['error' => 'Name, email, and 8+ char password required']); 
        exit;
    }

    // Determine role based on staff type
    $role = ($staffType === 'teaching') ? 'teacher' : 'staff';
    
    // For teaching staff, job_role can be specialization
    if ($role === 'teacher' && empty($jobRole)) {
        $jobRole = !empty($spec) ? $spec : 'Teacher';
    }

    $pdo->beginTransaction();
    try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        // 1. Create User
        $stmt = $pdo->prepare("
            INSERT INTO users (school_id, name, email, password_hash, role, staff_type, job_role, details_json, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$schoolId, $name, $email, $hash, $role, $staffType, $jobRole, json_encode($details)]);
        $userId = $pdo->lastInsertId();

        // 2. If teaching, also create record in teachers table
        if ($role === 'teacher') {
            $stmtT = $pdo->prepare("INSERT INTO teachers (user_id, school_id, tsc_number, specialization) VALUES (?, ?, ?, ?)");
            $stmtT->execute([$userId, $schoolId, $tsc, $spec]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'view') {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT id, name, email, status, staff_type, job_role, details_json FROM users WHERE id = ? AND school_id = ? AND staff_type = 'non_teaching' LIMIT 1");
    $stmt->execute([$id, $schoolId]);
    $row = $stmt->fetch();
    if ($row) {
        $row['details'] = json_decode($row['details_json'], true) ?: [];
        unset($row['details_json']);
    }
    echo json_encode($row);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = $input['id'] ?? 0;
    $name = trim($input['name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $jobRole = trim($input['job_role'] ?? '');

    if (!$id || !$name || !$email || !$jobRole) { http_response_code(400); echo json_encode(['error' => 'ID, Name, Email and Job Role required']); exit; }

    $details = [];
    foreach ($input as $key => $value) {
        if (strpos($key, 'details[') === 0) {
            $cleanKey = str_replace(['details[', ']'], '', $key);
            if (!empty($value)) {
                $details[$cleanKey] = $value;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, job_role = ?, details_json = ? WHERE id = ? AND school_id = ? AND staff_type = 'non_teaching'");
        $stmt->execute([$name, $email, $jobRole, json_encode($details), $id, $schoolId]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update staff']);
    }
    exit;
}

if ($action === 'toggle_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo->prepare("UPDATE users SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND school_id = ?")
        ->execute([$input['user_id'] ?? 0, $schoolId]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid action']);
?>