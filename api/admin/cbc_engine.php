<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher'); // Or school_admin

header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'get_report';

/**
 * Core Logic: Convert raw score to CBC Level (EE1 - BE2)
 * Based on official KNEC descriptors for JSS (Grades 7-9)
 */
function getCbcLevel($score) {
    $score = (float)$score;
    if ($score >= 80) return ['code' => 'EE1', 'label' => 'Exceeding Expectations (High)', 'color' => '#059669'];
    if ($score >= 70) return ['code' => 'EE2', 'label' => 'Exceeding Expectations', 'color' => '#10b981'];
    if ($score >= 60) return ['code' => 'ME1', 'label' => 'Meeting Expectations (High)', 'color' => '#3b82f6'];
    if ($score >= 50) return ['code' => 'ME2', 'label' => 'Meeting Expectations', 'color' => '#6366f1'];
    if ($score >= 40) return ['code' => 'AE1', 'label' => 'Approaching Expectations (High)', 'color' => '#f59e0b'];
    if ($score >= 30) return ['code' => 'AE2', 'label' => 'Approaching Expectations', 'color' => '#d97706'];
    if ($score >= 20) return ['code' => 'BE1', 'label' => 'Below Expectations (High)', 'color' => '#ef4444'];
    return ['code' => 'BE2', 'label' => 'Below Expectations', 'color' => '#b91c1c'];
}

// 1. Get Report Data for a Student
if ($action === 'get_report' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $studentId = $input['student_id'] ?? 0;
    $term = $input['term'] ?? 1;
    $year = $input['year'] ?? date('Y');

    if (!$studentId) { echo json_encode(['error' => 'Student ID required']); exit; }

    // Fetch Student & Class Info
    $stmt = $pdo->prepare("
        SELECT s.name, s.admission_number, c.name as class_name, c.grade_level
        FROM students s
        JOIN classes c ON s.class_id = c.id
        WHERE s.id = ? AND s.school_id = ?
    ");
    $stmt->execute([$studentId, $schoolId]);
    $student = $stmt->fetch();

    if (!$student) { echo json_encode(['error' => 'Student not found']); exit; }

    // Fetch Scores for this Term/Year
    $stmt = $pdo->prepare("
        SELECT 
            sub.name as subject_name,
            sub.code as subject_code,
            a.assessment_type,
            sc.marks_obtained,
            a.max_marks
        FROM scores sc
        JOIN assessments a ON sc.assessment_id = a.id
        JOIN subjects sub ON a.subject_id = sub.id
        WHERE sc.student_id = ? 
        AND sc.school_id = ?
        AND a.term = ?
        AND a.academic_year = ?
        ORDER BY sub.name
    ");
    $stmt->execute([$studentId, $schoolId, $term, $year]);
    $rawScores = $stmt->fetchAll();

    // Process Data & Apply 20-20-60 Logic
    $subjects = [];
    $grouped = [];
    foreach ($rawScores as $row) {
        if (!isset($grouped[$row['subject_name']])) {
            $grouped[$row['subject_name']] = ['kpsea' => 0, 'formative' => 0, 'summative' => 0, 'max' => 0];
        }
        // Aggregate scores by type (Assuming max_marks is consistent per type)
        if ($row['assessment_type'] === 'kpsea') $grouped[$row['subject_name']]['kpsea'] += $row['marks_obtained'];
        if ($row['assessment_type'] === 'formative') $grouped[$row['subject_name']]['formative'] += $row['marks_obtained'];
        if ($row['assessment_type'] === 'summative') $grouped[$row['subject_name']]['summative'] += $row['marks_obtained'];
        $grouped[$row['subject_name']]['max'] = $row['max_marks'];
    }

    // Calculate Final Grades
    foreach ($grouped as $name => $data) {
        // 20% KPSEA + 20% Formative + 60% Summative
        // Normalize to 100% scale if inputs are different, assuming 100 max for simplicity here
        $weighted = ($data['kpsea'] * 0.2) + ($data['formative'] * 0.2) + ($data['summative'] * 0.6);
        $level = getCbcLevel($weighted);

        $subjects[] = [
            'name' => $name,
            'score' => round($weighted, 1),
            'level' => $level['code'],
            'remark' => $level['label'],
            'color' => $level['color']
        ];
    }

    echo json_encode([
        'success' => true,
        'student' => $student,
        'term' => $term,
        'year' => $year,
        'subjects' => $subjects
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>