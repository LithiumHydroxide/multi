<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

try {
    // List strands for a subject
    if ($action === 'list') {
        $subjectId = $_GET['subject_id'] ?? null;
        $gradeLevel = $_GET['grade_level'] ?? null;
        
        $sql = "SELECT cs.*, s.name as subject_name 
                FROM cbc_strands cs 
                JOIN subjects s ON cs.subject_id = s.id 
                WHERE cs.subject_id = ?";
        $params = [$subjectId];
        
        if ($gradeLevel) {
            $sql .= " AND cs.grade_level = ?";
            $params[] = $gradeLevel;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Create strand
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $subjectId = $input['subject_id'] ?? null;
        $name = trim($input['name'] ?? '');
        $code = strtoupper(trim($input['code'] ?? ''));
        $gradeLevel = $input['grade_level'] ?? 7;
        $description = trim($input['description'] ?? '');

        if (!$subjectId || empty($name) || empty($code)) {
            echo json_encode(['error' => 'Subject, name, and code are required']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO cbc_strands (subject_id, name, code, grade_level, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$subjectId, $name, $code, $gradeLevel, $description]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        exit;
    }

    // Update strand
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $strandId = $input['id'] ?? null;
        $name = trim($input['name'] ?? '');
        $code = strtoupper(trim($input['code'] ?? ''));
        $description = trim($input['description'] ?? '');

        if (!$strandId || empty($name) || empty($code)) {
            echo json_encode(['error' => 'ID, name, and code are required']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE cbc_strands SET name = ?, code = ?, description = ?
            WHERE id = ? AND subject_id IN (
                SELECT id FROM subjects WHERE school_id = ?
            )
        ");
        $stmt->execute([$name, $code, $description, $strandId, $schoolId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // Delete strand
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $strandId = $input['id'] ?? null;

        $stmt = $pdo->prepare("
            DELETE cs FROM cbc_strands cs
            JOIN subjects s ON cs.subject_id = s.id
            WHERE cs.id = ? AND s.school_id = ?
        ");
        $stmt->execute([$strandId, $schoolId]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // List available pathways for school
    if ($action === 'list_pathways') {
        $stmt = $pdo->prepare("SELECT * FROM cbc_pathways WHERE school_id = ? AND is_active = 1 ORDER BY name");
        $stmt->execute([$schoolId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>