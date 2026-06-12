<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();
header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$userRole = getAuthenticatedUserRole();
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !in_array($userRole, ['school_admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Period configuration is restricted to administrators.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT day_of_week, period_number, start_time, end_time FROM timetable_slots WHERE school_id=? ORDER BY period_number");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetchAll());
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $pdo->prepare("DELETE FROM timetable_slots WHERE school_id=?")->execute([$schoolId]);
    $stmt = $pdo->prepare("INSERT INTO timetable_slots (school_id, day_of_week, period_number, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    foreach ($input['slots'] as $s) $stmt->execute([$schoolId, $s['day'], $s['period'], $s['start'], $s['end']]);
    echo json_encode(['success'=>true]);
}
?>