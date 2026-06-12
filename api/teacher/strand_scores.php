<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher'); // Or school_admin
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

try {
    // 1. Get Strands for a Subject
    if ($action === 'get_strands') {
        $subjectId = $_GET['subject_id'] ?? null;
        $gradeLevel = $_GET['grade_level'] ?? 7; // Default to G7 if not specified

        $stmt = $pdo->prepare("
            SELECT id, name, code 
            FROM cbc_strands 
            WHERE subject_id = ? AND grade_level = ? 
            ORDER BY name
        ");
        $stmt->execute([$subjectId, $gradeLevel]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // 2. Save Strand Scores
    if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $studentId = $input['student_id'] ?? null;
        $strandId = $input['strand_id'] ?? null;
        $marks = $input['marks_obtained'] ?? null;
        $maxMarks = $input['max_marks'] ?? 100;
        $level = $input['achievement_level'] ?? null;
        $term = $input['term'] ?? 1;
        $year = date('Y');

        if (!$studentId || !$strandId) {
            echo json_encode(['error' => 'Missing data']); exit;
        }

        // Upsert logic: Update if exists, Insert if new
        $stmt = $pdo->prepare("
            INSERT INTO strand_scores 
            (student_id, strand_id, marks_obtained, max_marks, achievement_level, assessment_term, assessment_year)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            marks_obtained = VALUES(marks_obtained),
            max_marks = VALUES(max_marks),
            achievement_level = VALUES(achievement_level)
        ");
        
        $stmt->execute([$studentId, $strandId, $marks, $maxMarks, $level, $term, $year]);
        
        echo json_encode(['success' => true]);
        exit;
    }

    // 3. Batch Save (Save all students at once)
    if ($action === 'batch_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $records = $input['records'] ?? [];
        $strandId = $input['strand_id'] ?? null;
        $term = $input['term'] ?? 1;
        $year = date('Y');

        if (!$strandId || empty($records)) {
            echo json_encode(['error' => 'Missing strand or records']); exit;
        }

        $pdo->beginTransaction();
        try {
            foreach ($records as $row) {
                $stmt = $pdo->prepare("
                    INSERT INTO strand_scores 
                    (student_id, strand_id, marks_obtained, max_marks, achievement_level, assessment_term, assessment_year)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    marks_obtained = VALUES(marks_obtained),
                    achievement_level = VALUES(achievement_level)
                ");
                $stmt->execute([
                    $row['student_id'], 
                    $strandId, 
                    $row['marks'] ?? null, 
                    100, 
                    $row['level'] ?? null, 
                    $term, 
                    $year
                ]);
            }
            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>