<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$step = (int)($input['step'] ?? 0);
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    if ($step === 1) {
        // Save settings
        $stmt = $pdo->prepare("UPDATE schools SET contact_phone=?, county=county WHERE id=?");
        $stmt->execute([$input['phone'] ?? null, $schoolId]);
        
        $stmt2 = $pdo->prepare("
            INSERT INTO school_settings (school_id, system_type, primary_color, secondary_color, motto, address, contact_phone)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE system_type=?, primary_color=?, secondary_color=?, motto=?, address=?, contact_phone=?
        ");
        $stmt2->execute([
            $schoolId, $input['system_type'], $input['primary'], $input['secondary'], $input['motto'], $input['address'], $input['phone'],
            $input['system_type'], $input['primary'], $input['secondary'], $input['motto'], $input['address'], $input['phone']
        ]);
        $pdo->prepare("UPDATE schools SET onboarding_step=2 WHERE id=?")->execute([$schoolId]);
    }

    elseif ($step === 2) {
        // Create classes & subjects
        $stmt = $pdo->prepare("INSERT INTO classes (school_id, name, grade_level, stream_code) VALUES (?, ?, 7, ?)");
        $stmt->execute([$schoolId, $input['class_name'], $input['stream_code']]);
        
        $subjects = array_map('trim', explode(',', $input['subjects']));
        // Use INSERT IGNORE to skip duplicates safely
        $stmtSub = $pdo->prepare("INSERT IGNORE INTO subjects (school_id, name, code) VALUES (?, ?, ?)");
        
        foreach ($subjects as $i => $sub) {
            if(empty($sub)) continue;
            $cleanName = preg_replace('/[^a-zA-Z]/', '', $sub);
            $base = strtoupper(substr($cleanName, 0, 3));
            $code = $base . ($i + 1);
            $stmtSub->execute([$schoolId, $sub, $code]);
        }
        $pdo->prepare("UPDATE schools SET onboarding_step=3 WHERE id=?")->execute([$schoolId]);
    }

    elseif ($step === 3) {
        // Create user & staff record
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $role = $input['role'] === 'teacher' ? 'teacher' : 'staff';
        
        // Check if email exists to avoid crash
        $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkUser->execute([$input['email']]);
        if(!$checkUser->fetch()) {
            $pdo->prepare("INSERT INTO users (school_id, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, 'active')")
                ->execute([$schoolId, $input['name'], $input['email'], $hash, $role]);
            $uid = $pdo->lastInsertId();
            
            if ($input['role'] === 'teacher') {
                $pdo->prepare("INSERT IGNORE INTO teachers (user_id, school_id, tsc_number, specialization) VALUES (?, ?, ?, ?)")
                    ->execute([$uid, $schoolId, $input['specialization'], $input['specialization']]);
            }
        }
        $pdo->prepare("UPDATE schools SET onboarding_step=4 WHERE id=?")->execute([$schoolId]);
    }

    elseif ($step === 4) {
        // Mark complete
        $pdo->prepare("UPDATE schools SET onboarding_step=5, onboarding_completed=1 WHERE id=?")->execute([$schoolId]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Setup failed: ' . $e->getMessage()]);
}
?>