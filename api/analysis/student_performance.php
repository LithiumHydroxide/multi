<?php
require_once '../../config.php';
require_once __DIR__ . '/app/Middleware/ModuleAccessMiddleware.php';
ModuleAccessMiddleware::requireFeature('advanced_analytics');
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$studentId = $_GET['student_id'] ?? null;
if (!$studentId) { http_response_code(400); echo json_encode(['error'=>'student_id required']); exit; }

$pdo = getDBConnection();
// Fetch performance trend
$trend = $pdo->prepare("
    SELECT a.term, a.academic_year, sub.name as subject, ROUND(AVG(sc.marks_obtained/sc.max_marks*100),1) as avg
    FROM scores sc JOIN assessments a ON sc.assessment_id=a.id JOIN subjects sub ON a.subject_id=sub.id
    WHERE sc.student_id=? AND sc.school_id=? GROUP BY a.year, a.term, sub.name ORDER BY a.year, a.term
");
$trend->execute([$studentId, $schoolId]);

// Fetch assignment/assessment breakdown
$breakdown = $pdo->prepare("
    SELECT a.title, a.term, a.max_marks, sc.marks_obtained, sc.achievement_level, sc.remarks
    FROM scores sc JOIN assessments a ON sc.assessment_id=a.id
    WHERE sc.student_id=? AND sc.school_id=? ORDER BY a.date_conducted DESC
");
$breakdown->execute([$studentId, $schoolId]);

echo json_encode(['trend'=>$trend->fetchAll(), 'assignments'=>$breakdown->fetchAll()]);
?>