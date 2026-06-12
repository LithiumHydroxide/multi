<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    $classes = $pdo->prepare("SELECT id, name, grade_level, stream_code FROM classes WHERE school_id = ? ORDER BY grade_level, name");
    $classes->execute([$schoolId]);
    $subjects = $pdo->prepare("SELECT id, name, code, learning_area, phase FROM subjects WHERE school_id = ? ORDER BY phase, learning_area, name");
    $subjects->execute([$schoolId]);
    echo json_encode(['classes' => $classes->fetchAll(), 'subjects' => $subjects->fetchAll()]);
    exit;
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'];
    
    if ($type === 'class') {
        $grade = null;
        if (isset($input['grade']) && trim($input['grade']) !== '') {
            $grade = (int)$input['grade'];
        }
        // Allow 0-12 (PP1 to Grade 12). Null = custom/unknown grade (e.g., "Mixed Stream" or non-standard classes)
        if ($grade !== null && ($grade < 0 || $grade > 12)) {
            http_response_code(400); echo json_encode(['error' => 'Grade level must be between 0 and 12']); exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO classes (school_id, name, grade_level, stream_code, capacity) VALUES (?, ?, ?, ?, 40)");
        $stmt->execute([$schoolId, $input['name'], $grade, strtoupper(trim($input['stream'] ?? 'A'))]);
    } elseif ($type === 'subject') {
        $name = trim($input['name'] ?? '');
        $code = strtoupper(trim($input['code'] ?? ''));
        $learning_area = trim($input['learning_area'] ?? '');
        $phase = trim($input['phase'] ?? '');
        
        if (empty($name) || empty($code) || empty($learning_area)) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject name, code, and learning area are required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO subjects (school_id, name, code, learning_area, phase) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$schoolId, $name, $code, $learning_area, $phase ?: null]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to add subject: ' . $e->getMessage()]);
            exit;
        }
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400); echo json_encode(['error' => 'Invalid action']);
?>