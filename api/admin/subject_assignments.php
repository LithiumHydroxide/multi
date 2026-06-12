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
        SELECT 
            cst.id,
            c.id as class_id,
            c.name as class_name,
            sub.id as subject_id,
            sub.name as subject_name,
            t.id as teacher_id,
            u.name as teacher_name
        FROM class_subject_teacher cst
        JOIN classes c ON cst.class_id = c.id
        JOIN subjects sub ON cst.subject_id = sub.id
        JOIN teachers t ON cst.teacher_id = t.id
        JOIN users u ON t.user_id = u.id
        WHERE cst.school_id = ?
        ORDER BY c.name, sub.name
    ");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'get_subjects' && isset($_GET['class_id'])) {
    $classId = $_GET['class_id'];
    $stmt = $pdo->prepare("
        SELECT id, name FROM subjects 
        WHERE school_id = ? 
        ORDER BY name
    ");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $classId = $input['class_id'] ?? null;
    $subjectId = $input['subject_id'] ?? null;
    $teacherId = $input['teacher_id'] ?? null;

    if (!$classId || !$subjectId || !$teacherId) {
        http_response_code(400);
        echo json_encode(['error' => 'class_id, subject_id, and teacher_id required']);
        exit;
    }

    try {
        // Check if assignment already exists
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM class_subject_teacher 
            WHERE school_id = ? AND class_id = ? AND subject_id = ? AND teacher_id = ?
        ");
        $check->execute([$schoolId, $classId, $subjectId, $teacherId]);
        if ($check->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'This assignment already exists']);
            exit;
        }

        // Also check if a different teacher is assigned to this class-subject combo
        $existing = $pdo->prepare("
            SELECT COUNT(*) FROM class_subject_teacher 
            WHERE school_id = ? AND class_id = ? AND subject_id = ?
        ");
        $existing->execute([$schoolId, $classId, $subjectId]);
        if ($existing->fetchColumn() > 0) {
            // Replace the existing assignment
            $pdo->prepare("DELETE FROM class_subject_teacher WHERE school_id = ? AND class_id = ? AND subject_id = ?")
                ->execute([$schoolId, $classId, $subjectId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO class_subject_teacher (school_id, class_id, subject_id, teacher_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$schoolId, $classId, $subjectId, $teacherId]);
        echo json_encode(['success' => true, 'message' => 'Subject assigned to teacher']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $assignmentId = $input['id'] ?? null;

    if (!$assignmentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Assignment ID required']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM class_subject_teacher WHERE id = ? AND school_id = ?");
        $stmt->execute([$assignmentId, $schoolId]);
        echo json_encode(['success' => true, 'message' => 'Assignment deleted']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete: ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
