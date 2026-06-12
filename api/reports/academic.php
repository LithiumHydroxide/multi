<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fixed Subject Performance Query
$subStmt = $pdo->prepare("
    SELECT sub.name, ROUND(AVG(sc.marks_obtained / a.max_marks * 100), 1) as avg_pct
    FROM scores sc
    JOIN assessments a ON sc.assessment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE sc.school_id = ?
    GROUP BY sub.id, sub.name
    ORDER BY avg_pct DESC
");
$subStmt->execute([$schoolId]);
$subjects = $subStmt->fetchAll();

// Fixed Attendance Query (safe division)
$attStmt = $pdo->prepare("
    SELECT 
        IFNULL(ROUND(COUNT(CASE WHEN status='present' THEN 1 END) / NULLIF(COUNT(*),0) * 100, 1), 0) as pct,
        COUNT(CASE WHEN status='absent' THEN 1 END) as absent
    FROM attendance WHERE school_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$attStmt->execute([$schoolId]);
$attendance = $attStmt->fetch() ?: ['pct' => 0, 'absent' => 0];

echo json_encode(['subjects' => $subjects, 'attendance' => $attendance]);
?>