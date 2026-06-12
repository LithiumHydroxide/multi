<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

try {
    // List Terms
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT * FROM school_calendar WHERE school_id = ? AND year = YEAR(CURDATE()) ORDER BY term");
        $stmt->execute([$schoolId]);
        echo json_encode($stmt->fetchAll());
        exit;
    }

    // Update Term
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $termId = $input['term_id'] ?? null;
        $startDate = $input['start_date'] ?? '';
        $endDate = $input['end_date'] ?? '';
        $isCurrent = $input['is_current'] ?? 0;

        if (!$termId || !$startDate || !$endDate) {
            echo json_encode(['error' => 'Missing fields']);
            exit;
        }

        // If setting to current, deactivate others first
        if ($isCurrent == 1) {
            $pdo->prepare("UPDATE school_calendar SET is_current = 0 WHERE school_id = ? AND year = YEAR(CURDATE())")->execute([$schoolId]);
        }

        $stmt = $pdo->prepare("UPDATE school_calendar SET start_date = ?, end_date = ?, is_current = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$startDate, $endDate, $isCurrent, $termId, $schoolId]);

        echo json_encode(['success' => true]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>