<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

try {
    // 1. List Rules for a Grade
    if ($action === 'list') {
        $gradeLevel = $_GET['grade_level'] ?? 9;
        $stmt = $pdo->prepare("SELECT id, level_code, min_percentage, max_percentage FROM cbc_grading_rules WHERE school_id = ? AND grade_level = ? ORDER BY CAST(SUBSTRING(level_code, 3, 1) AS UNSIGNED) ASC");
        $stmt->execute([$schoolId, $gradeLevel]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 2. Save Rule
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $gradeLevel = $input['grade_level'] ?? 9;
        $levelCode = strtoupper($input['level_code'] ?? '');
        $min = $input['min_percentage'] ?? 0;
        $max = $input['max_percentage'] ?? 100;

        // Upsert logic
        $stmt = $pdo->prepare("
            INSERT INTO cbc_grading_rules (school_id, grade_level, level_code, min_percentage, max_percentage)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE min_percentage=?, max_percentage=?
        ");
        $stmt->execute([$schoolId, $gradeLevel, $levelCode, $min, $max, $min, $max]);
        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500); echo json_encode(['error' => $e->getMessage()]);
}
?>