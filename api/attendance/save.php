<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$schoolId = getAuthenticatedSchoolId();
$userId   = getAuthenticatedUserId();

// Extract data using correct keys
$date     = $input['date'] ?? date('Y-m-d');
$classId  = $input['class_id'] ?? null;
$records  = $input['records'] ?? []; // Key changed from 'attendance' to 'records'

if (!$classId || empty($records)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing class or attendance records']);
    exit;
}

$pdo = getDBConnection();
$saved = 0;

try {
    $pdo->beginTransaction();
    
    // Use ON DUPLICATE KEY UPDATE to prevent errors when updating existing records
    $stmt = $pdo->prepare("
        INSERT INTO attendance (school_id, student_id, class_id, date, status, marked_by)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)
    ");

    foreach ($records as $rec) {
        if (!isset($rec['student_id']) || !isset($rec['status'])) continue;
        $stmt->execute([
            $schoolId, 
            $rec['student_id'], 
            $classId, 
            $date, 
            $rec['status'], 
            $userId
        ]);
        $saved++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'saved' => $saved]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>