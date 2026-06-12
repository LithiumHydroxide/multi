<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Auto-generate default 8-period Mon-Fri slots if missing
$check = $pdo->prepare("SELECT COUNT(*) FROM timetable_slots WHERE school_id = ?");
$check->execute([$schoolId]);
if ($check->fetchColumn() == 0) {
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    $times = [
        ['08:00','08:40'], ['08:40','09:20'], ['09:20','10:00'], ['10:20','11:00'],
        ['11:00','11:40'], ['11:40','12:20'], ['13:10','13:50'], ['13:50','14:30']
    ];
    $stmt = $pdo->prepare("INSERT INTO timetable_slots (school_id, day_of_week, period_number, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
    foreach ($days as $day) {
        foreach ($times as $i => $t) {
            $stmt->execute([$schoolId, $day, $i+1, $t[0], $t[1]]);
        }
    }
}

$stmt = $pdo->prepare("SELECT id, day_of_week, period_number, start_time, end_time FROM timetable_slots WHERE school_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), period_number");
$stmt->execute([$schoolId]);
echo json_encode($stmt->fetchAll());
?>