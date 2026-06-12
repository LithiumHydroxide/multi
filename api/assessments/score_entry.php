<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope(); 
require_once '../../app/Helpers/CBCGradingHelper.php';

// 1. Auth & Multi-Tenancy Check
$schoolId = getAuthenticatedSchoolId();
$userId   = getAuthenticatedUserId();

if (!$schoolId || !$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login first.']);
    exit;
}

// 2. Parse Request
$input = json_decode(file_get_contents('php://input'), true);
$assessmentId = $input['assessment_id'] ?? null;
$scores       = $input['scores'] ?? []; // Array of ['student_id'=>1, 'marks'=>85]

if (!$assessmentId || empty($scores)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing assessment_id or scores array']);
    exit;
}

$pdo = getDBConnection();

// 3. Verify Assessment belongs to logged-in school
$stmt = $pdo->prepare("SELECT id, max_marks FROM assessments WHERE id = ? AND school_id = ?");
$stmt->execute([$assessmentId, $schoolId]);
$assessment = $stmt->fetch();

if (!$assessment) {
    http_response_code(404);
    echo json_encode(['error' => 'Assessment not found or access denied']);
    exit;
}

// 4. Bulk Insert with Auto-Grading
$pdo->beginTransaction();
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO scores (school_id, student_id, assessment_id, marks_obtained, achievement_level, graded_by)
        VALUES (:school_id, :student_id, :assessment_id, :marks, :level, :graded_by)
        ON DUPLICATE KEY UPDATE 
            marks_obtained = VALUES(marks_obtained),
            achievement_level = VALUES(achievement_level),
            graded_by = VALUES(graded_by),
            updated_at = CURRENT_TIMESTAMP
    ");

    $savedCount = 0;
    foreach ($scores as $score) {
        if (!isset($score['student_id']) || !isset($score['marks'])) continue;
        
        // Clamp marks to valid range
        $marks = max(0, min($score['marks'], $assessment['max_marks']));
        $level = CBCGradingHelper::getAchievementLevel($marks, $assessment['max_marks']);
        
        $insertStmt->execute([
            ':school_id'      => $schoolId,
            ':student_id'     => $score['student_id'],
            ':assessment_id'  => $assessmentId,
            ':marks'          => $marks,
            ':level'          => $level,
            ':graded_by'      => $userId
        ]);
        $savedCount++;
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => "{$savedCount} scores saved and auto-graded successfully.",
        'assessment_id' => $assessmentId
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Score Entry Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save scores. Please try again.']);
}
?>