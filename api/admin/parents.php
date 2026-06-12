<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

try {
    // List Parents
    if ($action === 'list') {
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(sp.student_id) as student_count
            FROM parents p
            LEFT JOIN student_parents sp ON p.id = sp.parent_id
            WHERE p.school_id = ?
            GROUP BY p.id ORDER BY p.last_name
        ");
        $stmt->execute([$schoolId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Get Students Filtered by Class
    if ($action === 'get_students_by_class') {
        $classId = $_GET['class_id'] ?? null;
        if ($classId) {
            $stmt = $pdo->prepare("
                SELECT id, name, admission_number 
                FROM students 
                WHERE school_id = ? AND class_id = ? AND status = 'active' 
                ORDER BY name
            ");
            $stmt->execute([$schoolId, $classId]);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, name, admission_number 
                FROM students 
                WHERE school_id = ? AND status = 'active' 
                ORDER BY name
            ");
            $stmt->execute([$schoolId]);
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Create Parent
    if ($action === 'create') {
        $input = json_decode(file_get_contents('php://input'), true);
        $fname = trim($input['first_name'] ?? '');
        $lname = trim($input['last_name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $relation = $input['relation'] ?? 'Guardian';
        $studentId = $input['student_id'] ?? null;

        if (empty($fname) || empty($lname) || empty($phone)) {
            echo json_encode(['error' => 'Name and Phone are required']); exit;
        }

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO parents (school_id, first_name, last_name, phone, email, relation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$schoolId, $fname, $lname, $phone, $email, $relation]);
        $parentId = $pdo->lastInsertId();

        if ($studentId) {
            $linkStmt = $pdo->prepare("INSERT IGNORE INTO student_parents (student_id, parent_id) VALUES (?, ?)");
            $linkStmt->execute([$studentId, $parentId]);
        }

        $pdo->commit();
        echo json_encode(['success' => true]);
        exit;
    }

    // Assign Parent to Existing Student
    if ($action === 'assign') {
        $input = json_decode(file_get_contents('php://input'), true); // ✅ FIXED: Added $input definition
        $pdo->prepare("INSERT IGNORE INTO student_parents (student_id, parent_id) VALUES (?, ?)")
            ->execute([$input['student_id'], $input['parent_id']]);
        echo json_encode(['success' => true]);
        exit;
    }

    // Delete Parent
    if ($action === 'delete') {
        $input = json_decode(file_get_contents('php://input'), true); // ✅ FIXED: Added $input definition
        $pdo->prepare("DELETE FROM parents WHERE id = ? AND school_id = ?")
            ->execute([$input['id'], $schoolId]);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>