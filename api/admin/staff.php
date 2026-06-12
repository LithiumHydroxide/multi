<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    // Fetch Teaching Staff
    $teaching = $pdo->prepare("
        SELECT u.id, u.name, u.email, 'teaching' as type, t.job_role, t.tsc_number, t.details_json, u.status
        FROM users u JOIN staff_profiles t ON u.id = t.user_id 
        WHERE u.school_id = ? AND u.role IN ('teacher','staff')
    ");
    $teaching->execute([$schoolId]);
    
    // Fetch Non-Teaching Staff
    $nonTeaching = $pdo->prepare("
        SELECT id, full_name as name, phone, job_role, license_no, certifications as details, 'non_teaching' as type, status
        FROM staff_directory WHERE school_id = ?
    ");
    $nonTeaching->execute([$schoolId]);
    
    // Merge & format
    $list = $teaching->fetchAll();
    foreach ($nonTeaching->fetchAll() as $nt) {
        $nt['details_json'] = $nt['details'] ? json_encode(['certifications' => $nt['details'], 'license_no' => $nt['license_no']]) : null;
        $nt['phone'] = $nt['phone'];
        $list[] = $nt;
    }
    
    foreach ($list as &$row) {
        $row['details'] = json_decode($row['details_json'] ?? '{}', true) ?: [];
        unset($row['details_json']);
    }
    echo json_encode($list);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['staff_type'] ?? 'teaching';
    $role = trim($input['job_role'] ?? '');
    
    if (empty($role)) { http_response_code(400); echo json_encode(['error' => 'Job Role is required']); exit; }
    
    $pdo->beginTransaction();
    try {
        if ($type === 'teaching') {
            $email = strtolower(trim($input['email'] ?? ''));
            $pass = $input['password'] ?? '';
            $name = trim($input['name'] ?? '');
            $tsc = trim($input['tsc_number'] ?? '');
            
            if (!$name || !$email || strlen($pass) < 8) { http_response_code(400); echo json_encode(['error' => 'Name, valid email, and 8+ char password required for teaching staff']); exit; }
            
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (school_id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 'teacher', 'active')")
               ->execute([$schoolId, $name, $email, $hash]);
            $uid = $pdo->lastInsertId();
            
            $pdo->prepare("INSERT INTO staff_profiles (user_id, school_id, staff_type, job_role, tsc_number, details_json) VALUES (?, ?, 'teaching', ?, ?, ?)")
               ->execute([$uid, $schoolId, $role, $tsc, json_encode($input['details'] ?? [])]);
               
        } else {
            // Non-Teaching: Directory record only
            $pdo->prepare("INSERT INTO staff_directory (school_id, full_name, job_role, phone, emergency_contact, license_no, certifications, contract_type, start_date) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([
                   $schoolId,
                   trim($input['name']),
                   $role,
                   $input['phone'] ?? null,
                   $input['emergency_contact'] ?? null,
                   $input['details']['license_no'] ?? null,
                   $input['details']['certifications'] ?? null,
                   $input['contract_type'] ?? 'contract',
                   $input['start_date'] ?? null
               ]);
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

if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)$input['id'];
    $type = $input['type'];
    
    if ($type === 'teaching') {
        $pdo->prepare("UPDATE users SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    } else {
        $pdo->prepare("UPDATE staff_directory SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id = ? AND school_id = ?")->execute([$id, $schoolId]);
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid action']);
?>