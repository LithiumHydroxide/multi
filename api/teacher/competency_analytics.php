<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher');
header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

try {
$levelWeights = ['EE1'=>4, 'EE2'=>4, 'ME1'=>3, 'ME2'=>3, 'AE1'=>2, 'AE2'=>2, 'BE1'=>1, 'BE2'=>1];

// 1. Student Competency Radar Data
if ($action === 'student_radar') {
$studentId = $_GET['student_id'] ?? null;
$term = $_GET['term'] ?? 1;
if (!$studentId) { echo json_encode(['error' => 'Student ID required']); exit; }

$stmt = $pdo->prepare("
SELECT cs.competency_focus, ss.achievement_level, COUNT(*) as cnt
FROM strand_scores ss
JOIN cbc_strands cs ON ss.strand_id = cs.id
WHERE ss.student_id = ? AND ss.school_id = ? AND ss.assessment_term = ?
GROUP BY cs.competency_focus, ss.achievement_level
");
$stmt->execute([$studentId, $schoolId, $term]);
$raw = $stmt->fetchAll();

$competencies = array_fill_keys([
'communication','critical_thinking','creativity','collaboration',
'self_efficacy','digital_literacy','learning_to_learn','citizenship'
], ['mastery_pct' => 0, 'level' => 'Beginning']);

$compTotals = [];
foreach ($raw as $row) {
$comp = $row['competency_focus'];
$weight = $levelWeights[$row['achievement_level']] ?? 1;
if (!isset($compTotals[$comp])) $compTotals[$comp] = ['sum' => 0, 'count' => 0];
$compTotals[$comp]['sum'] += $weight * $row['cnt'];
$compTotals[$comp]['count'] += $row['cnt'];
}

foreach ($competencies as $comp => &$data) {
if (isset($compTotals[$comp]) && $compTotals[$comp]['count'] > 0) {
$pct = ($compTotals[$comp]['sum'] / (4 * $compTotals[$comp]['count'])) * 100;
$data['mastery_pct'] = round($pct, 1);
$data['level'] = $pct >= 75 ? 'Exceeding' : ($pct >= 50 ? 'Meeting' : ($pct >= 25 ? 'Approaching' : 'Beginning'));
}
}
unset($data);
echo json_encode(['success' => true, 'competencies' => $competencies]);
exit;
}

// 2. Class Competency Heatmap
if ($action === 'class_heatmap') {
$classId = $_GET['class_id'] ?? null;
$term = $_GET['term'] ?? 1;
if (!$classId) { echo json_encode(['error' => 'Class ID required']); exit; }

$stmt = $pdo->prepare("
SELECT cs.competency_focus,
AVG(CASE WHEN ss.achievement_level IN ('EE1','EE2','ME1','ME2') THEN 1 ELSE 0 END) * 100 as mastery_pct,
COUNT(DISTINCT ss.student_id) as students_assessed
FROM strand_scores ss
JOIN cbc_strands cs ON ss.strand_id = cs.id
JOIN students s ON ss.student_id = s.id
WHERE s.class_id = ? AND s.school_id = ? AND ss.assessment_term = ?
GROUP BY cs.competency_focus
");
$stmt->execute([$classId, $schoolId, $term]);
echo json_encode(['success' => true, 'heatmap' => $stmt->fetchAll()]);
exit;
}

echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
http_response_code(500);
echo json_encode(['error' => $e->getMessage()]);
}
?>