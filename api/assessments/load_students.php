<?php
require_once '../../config.php';
header('Content-Type: application/json');

$classId = $_GET['class_id'] ?? null;
$schoolId = getAuthenticatedSchoolId();

if (!$classId || !$schoolId) {
    echo json_encode([]);
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT id, name, admission_number FROM students WHERE school_id = ? AND class_id = ? AND status = 'active' ORDER BY name");
$stmt->execute([$schoolId, $classId]);
echo json_encode($stmt->fetchAll());
?>