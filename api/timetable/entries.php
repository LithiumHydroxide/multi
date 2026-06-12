<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$userRole = getAuthenticatedUserRole();
$canEdit = in_array($userRole, ['school_admin', 'super_admin']);

// BLOCK NON-ADMINS FROM POST/DELETE
if (($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') && !$canEdit) {
    http_response_code(403);
    echo json_encode(['error' => 'Only administrators can modify the timetable.']);
    exit;
}

$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $classId = $_GET['class_id'] ?? null;
    if (!$classId) { echo json_encode(['error' => 'class_id required']); exit; }
    $stmt = $pdo->prepare("
        SELECT e.id as entry_id, e.slot_id, e.subject_id, e.teacher_id, e.room_number,
               s.day_of_week, s.period_number,
               sub.name as subject_name, u.name as teacher_name
        FROM timetable_entries e
        JOIN timetable_slots s ON e.slot_id = s.id
        LEFT JOIN subjects sub ON e.subject_id = sub.id
        LEFT JOIN teachers t ON e.teacher_id = t.id
        LEFT JOIN users u ON t.user_id = u.id
        WHERE e.school_id = ? AND e.class_id = ?
    ");
    $stmt->execute([$schoolId, $classId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $classId = $input['class_id'] ?? null;
    $slotId = $input['slot_id'] ?? null;
    $entryId = $input['entry_id'] ?? null;
    
    if (!$slotId || !$classId) { http_response_code(400); echo json_encode(['error' => 'Missing slot/class']); exit; }

    // Check for existing entry
    $check = $pdo->prepare("SELECT id FROM timetable_entries WHERE school_id=? AND class_id=? AND slot_id=?");
    $check->execute([$schoolId, $classId, $slotId]);
    $existing = $check->fetch();

    if (empty($input['subject_id']) || empty($input['teacher_id'])) {
        // DELETE MODE
        if ($existing) {
            $pdo->prepare("DELETE FROM timetable_entries WHERE id=?")->execute([$existing['id']]);
        }
        echo json_encode(['success' => true, 'action' => 'removed']); exit;
    }

    // UPDATE or INSERT MODE
    $conflicts = [];
    if ($input['teacher_id']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable_entries WHERE school_id=? AND teacher_id=? AND slot_id=? AND id!=?");
        $stmt->execute([$schoolId, $input['teacher_id'], $slotId, $existing['id'] ?? 0]);
        if ($stmt->fetchColumn() > 0) $conflicts[] = "Teacher already booked.";
    }
    if ($input['room']) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM timetable_entries WHERE school_id=? AND room_number=? AND slot_id=? AND id!=?");
        $stmt->execute([$schoolId, $input['room'], $slotId, $existing['id'] ?? 0]);
        if ($stmt->fetchColumn() > 0) $conflicts[] = "Room already booked.";
    }

    if (!empty($conflicts)) { echo json_encode(['error' => 'Conflict', 'conflicts' => $conflicts]); exit; }

    if ($existing) {
        $stmt = $pdo->prepare("UPDATE timetable_entries SET subject_id=?, teacher_id=?, room_number=? WHERE id=?");
        $stmt->execute([$input['subject_id'], $input['teacher_id'], $input['room'], $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO timetable_entries (school_id, class_id, slot_id, subject_id, teacher_id, room_number) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$schoolId, $classId, $slotId, $input['subject_id'], $input['teacher_id'], $input['room']]);
    }
    echo json_encode(['success' => true, 'action' => 'saved']);
    exit;
}
?>