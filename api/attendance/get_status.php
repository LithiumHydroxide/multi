<?php
require_once '../../config.php';
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$classId = $_GET['class_id'] ?? null;
$date = $_GET['date'] ?? date('Y-m-d');

if (!$schoolId || !$classId) { echo json_encode([]); exit; }

$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT a.student_id, a.status 
    FROM attendance a 
    JOIN students s ON a.student_id = s.id 
    WHERE a.school_id = ? AND s.class_id = ? AND a.date = ?
");
$stmt->execute([$schoolId, $classId, $date]);
// Returns: { "student_id": "status", ... }
echo json_encode($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
?>