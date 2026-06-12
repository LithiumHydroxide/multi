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
$stmt = $pdo->prepare("SELECT id, title, term, max_marks FROM assessments WHERE school_id = ? AND class_id = ? ORDER BY term, created_at DESC");
$stmt->execute([$schoolId, $classId]);
echo json_encode($stmt->fetchAll());
?>