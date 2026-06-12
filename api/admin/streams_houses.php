<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

try {
// Update School Type
if ($action === 'update_school_type' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$schoolType = $input['school_type'] ?? 'day';
if (!in_array($schoolType, ['day', 'boarding', 'mixed'])) {
throw new Exception('Invalid school type');
}
$stmt = $pdo->prepare("UPDATE schools SET school_type = ? WHERE id = ?");
$stmt->execute([$schoolType, $schoolId]);
echo json_encode(['success' => true]);
exit;
}

// Assign Stream to Class
if ($action === 'assign_stream' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$classId = $input['class_id'] ?? null;
$streamCode = strtoupper(trim($input['stream_code'] ?? ''));
if (!$classId || empty($streamCode)) {
throw new Exception('Class ID and stream code are required');
}
$stmt = $pdo->prepare("UPDATE classes SET stream_code = ? WHERE id = ? AND school_id = ?");
$stmt->execute([$streamCode, $classId, $schoolId]);
echo json_encode(['success' => true]);
exit;
}

// Remove Stream from Class
if ($action === 'remove_stream' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$classId = $input['class_id'] ?? null;
if (!$classId) {
throw new Exception('Class ID is required');
}
$stmt = $pdo->prepare("UPDATE classes SET stream_code = NULL WHERE id = ? AND school_id = ?");
$stmt->execute([$classId, $schoolId]);
echo json_encode(['success' => true]);
exit;
}

// List Streams
if ($action === 'list_streams') {
$stmt = $pdo->prepare("
SELECT c.id as class_id, c.name as class_name, c.stream_code
FROM classes c
WHERE c.school_id = ? AND c.stream_code IS NOT NULL
ORDER BY c.grade_level, c.name
");
$stmt->execute([$schoolId]);
echo json_encode($stmt->fetchAll());
exit;
}

// List Students (for assignment)
if ($action === 'list_students') {
$classId = $_GET['class_id'] ?? null;
if ($classId) {
$stmt = $pdo->prepare("
SELECT id, name, admission_number, house_id
FROM students
WHERE school_id = ? AND class_id = ? AND status = 'active'
ORDER BY name
");
$stmt->execute([$schoolId, $classId]);
} else {
$stmt = $pdo->prepare("
SELECT id, name, admission_number, house_id, class_id
FROM students
WHERE school_id = ? AND status = 'active'
ORDER BY name
");
$stmt->execute([$schoolId]);
}
echo json_encode($stmt->fetchAll());
exit;
}

// Assign Students to House
if ($action === 'assign_students' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$houseId = $input['house_id'] ?? null;
$studentIds = $input['student_ids'] ?? [];
if (!$houseId || empty($studentIds)) {
throw new Exception('House ID and student IDs are required');
}
$placeholders = implode(',', array_fill(0, count($studentIds), '?'));
$stmt = $pdo->prepare("UPDATE students SET house_id = ? WHERE id IN ($placeholders) AND school_id = ?");
$params = array_merge([$houseId], $studentIds, [$schoolId]);
$stmt->execute($params);
echo json_encode(['success' => true, 'assigned' => $stmt->rowCount()]);
exit;
}

// Award House Points
if ($action === 'award_points' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$houseId = $input['house_id'] ?? null;
$points = (int)($input['points'] ?? 0);
$eventName = trim($input['event_name'] ?? '');
$reason = trim($input['reason'] ?? '');
$userId = getAuthenticatedUserId();
if (!$houseId || $points === 0 || empty($eventName)) {
throw new Exception('House, points, and event are required');
}
$stmt = $pdo->prepare("
INSERT INTO house_points (school_id, house_id, points, reason, awarded_by, event_name, awarded_date)
VALUES (?, ?, ?, ?, ?, ?, CURDATE())
");
$stmt->execute([$schoolId, $houseId, $points, $reason, $userId, $eventName]);
echo json_encode(['success' => true]);
exit;
}

// Save House
if ($action === 'save_house' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$houseId = $input['house_id'] ?? null;
$name = trim($input['name'] ?? '');
$code = strtoupper(trim($input['code'] ?? ''));
$color = $input['color'] ?? '#3b82f6';
$type = $input['type'] ?? 'both';
$capacity = !empty($input['capacity']) ? (int)$input['capacity'] : null;
$patronName = trim($input['patron_name'] ?? '');
if (empty($name) || empty($code)) {
throw new Exception('Name and code are required');
}
if ($houseId) {
// Update existing house
$stmt = $pdo->prepare("
UPDATE houses SET
name = ?, code = ?, color = ?, type = ?,
capacity = ?, patron_name = ?
WHERE id = ? AND school_id = ?
");
$stmt->execute([$name, $code, $color, $type, $capacity, $patronName, $houseId, $schoolId]);
} else {
// Create new house
$stmt = $pdo->prepare("
INSERT INTO houses (school_id, name, code, color, type, capacity, patron_name)
VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([$schoolId, $name, $code, $color, $type, $capacity, $patronName]);
}
echo json_encode(['success' => true]);
exit;
}

// List Houses
if ($action === 'list_houses') {
$stmt = $pdo->prepare("
SELECT h.*,
(SELECT COUNT(*) FROM students WHERE house_id = h.id AND school_id = ?) as student_count
FROM houses h
WHERE h.school_id = ?
ORDER BY h.name
");
$stmt->execute([$schoolId, $schoolId]);
echo json_encode($stmt->fetchAll());
exit;
}

// Delete House
if ($action === 'delete_house' && $_SERVER['REQUEST_METHOD'] === 'POST') {
$input = json_decode(file_get_contents('php://input'), true);
$houseId = $input['id'] ?? 0;
$stmt = $pdo->prepare("DELETE FROM houses WHERE id = ? AND school_id = ?");
$stmt->execute([$houseId, $schoolId]);
echo json_encode(['success' => true]);
exit;
}

// List House Points
if ($action === 'list_house_points') {
$stmt = $pdo->prepare("
SELECT h.name, h.code, h.color,
COALESCE(SUM(hp.points), 0) as total_points,
COUNT(hp.id) as entries
FROM houses h
LEFT JOIN house_points hp ON h.id = hp.house_id AND hp.school_id = ?
WHERE h.school_id = ?
GROUP BY h.id
ORDER BY total_points DESC
");
$stmt->execute([$schoolId, $schoolId]);
echo json_encode($stmt->fetchAll());
exit;
}

echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
http_response_code(400);
echo json_encode(['error' => $e->getMessage()]);
}
?>