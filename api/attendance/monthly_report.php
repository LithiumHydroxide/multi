<?php
require_once '../../config.php';
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$classId = $_GET['class_id'] ?? null;
$month = $_GET['month'] ?? date('Y-m');

if (!$schoolId || !$classId) { echo json_encode([]); exit; }

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT 
        s.id, s.name, s.admission_number,
        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN a.status = 'late' THEN 1 END) as late,
        COUNT(a.id) as total_days
    FROM students s
    LEFT JOIN attendance a ON s.id = a.student_id 
        AND a.class_id = s.class_id 
        AND DATE_FORMAT(a.date, '%Y-%m') = ?
    WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'
    GROUP BY s.id
    ORDER BY s.name
");
$stmt->execute([$month, $schoolId, $classId]);
$data = $stmt->fetchAll();

foreach ($data as &$row) {
    $total = $row['total_days'] ?: 1;
    $row['attendance_pct'] = round(($row['present'] / $total) * 100, 1);
}
echo json_encode($data);
?>