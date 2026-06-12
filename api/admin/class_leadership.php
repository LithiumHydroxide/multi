<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

try {
    // 1. List all classes with their current leadership
    if ($action === 'list') {
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, c.grade_level, c.stream_code,
                   ct_u.name as class_teacher, ast_u.name as assistant_teacher,
                   p.name as prefect_name, p.admission_number as prefect_adm
            FROM classes c
            LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
            LEFT JOIN users ct_u ON ct.user_id = ct_u.id
            LEFT JOIN teachers ast ON c.assistant_teacher_id = ast.id
            LEFT JOIN users ast_u ON ast.user_id = ast_u.id
            LEFT JOIN students p ON c.prefect_id = p.id
            WHERE c.school_id = ?
        ");
        $stmt->execute([$schoolId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 2. Get dropdown options for Teachers and Students
    if ($action === 'dropdowns') {
        $teachers = $pdo->prepare("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.school_id = ? ORDER BY u.name");
        $teachers->execute([$schoolId]);
        
        $students = $pdo->prepare("SELECT id, name, admission_number FROM students WHERE school_id = ? AND status = 'active' ORDER BY name");
        $students->execute([$schoolId]);
        
        echo json_encode(['teachers' => $teachers->fetchAll(), 'students' => $students->fetchAll()]);
        exit;
    }

    // 3. Get students by class (for prefect dropdown)
    if ($action === 'get_students_by_class') {
        $classId = $_GET['class_id'] ?? null;
        if (!$classId) { 
            echo json_encode([]); 
            exit; 
        }
        
        $stmt = $pdo->prepare("
            SELECT id, name, admission_number 
            FROM students 
            WHERE school_id = ? AND class_id = ? AND status = 'active' 
            ORDER BY name
        ");
        $stmt->execute([$schoolId, $classId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 4. Update Class Leadership (With Validation)
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $classId = $input['class_id'] ?? null;
        $ctId = $input['class_teacher_id'] ?: null;
        $asId = $input['assistant_teacher_id'] ?: null;
        $pId = $input['prefect_id'] ?: null;

        if (!$classId) { 
            echo json_encode(['success' => false, 'error' => 'Class ID required']); 
            exit; 
        }

        // --- VALIDATION: Check Teacher Availability ---
        // A teacher cannot be Class Teacher OR Assistant in any other class
        if ($ctId || $asId) {
            $teacherIdToCheck = $ctId ?: $asId;
            
            $stmt = $pdo->prepare("
                SELECT id FROM classes 
                WHERE school_id = ? AND id != ? AND (class_teacher_id = ? OR assistant_teacher_id = ?)
            ");
            $stmt->execute([$schoolId, $classId, $teacherIdToCheck, $teacherIdToCheck]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'This teacher is already assigned as a Class Teacher or Assistant in another class.']);
                exit;
            }
        }

        // --- VALIDATION: Prevent Same Teacher for CT and AT in Same Class ---
        if ($ctId && $asId && $ctId == $asId) {
             echo json_encode(['success' => false, 'error' => 'A teacher cannot be both Class Teacher and Assistant for the same class.']);
             exit;
        }

        // --- VALIDATION: Check Prefect Availability ---
        // A student cannot be a Prefect in any other class
        if ($pId) {
            // First, verify the student belongs to this class
            $stmt = $pdo->prepare("
                SELECT id FROM students 
                WHERE id = ? AND school_id = ? AND class_id = ?
            ");
            $stmt->execute([$pId, $schoolId, $classId]);
            
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Selected prefect must be an active student in this class.']);
                exit;
            }
            
            // Check if student is already a prefect in another class
            $stmt = $pdo->prepare("
                SELECT id FROM classes 
                WHERE school_id = ? AND id != ? AND prefect_id = ?
            ");
            $stmt->execute([$schoolId, $classId, $pId]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'This student is already a Prefect in another class.']);
                exit;
            }
        }

        // --- SAVE TO DATABASE ---
        $stmt = $pdo->prepare("
            UPDATE classes 
            SET class_teacher_id=?, assistant_teacher_id=?, prefect_id=? 
            WHERE id=? AND school_id=?
        ");
        $stmt->execute([$ctId, $asId, $pId, $classId, $schoolId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'System error: ' . $e->getMessage()]);
}
?>