<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? '';

try {
    $pdo->beginTransaction();

    if ($action === 'general') {
        $stmt = $pdo->prepare("UPDATE schools SET name=?, county=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['county'], $schoolId]);
        
        $stmt2 = $pdo->prepare("
            INSERT INTO school_settings (school_id, system_type) 
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE system_type=VALUES(system_type)
        ");
        $stmt2->execute([$schoolId, $_POST['system_type']]);
    }

    elseif ($action === 'branding') {
        $logoPath = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
            $allowed = ['image/png', 'image/jpeg', 'image/webp'];
            if (in_array($_FILES['logo']['type'], $allowed) && $_FILES['logo']['size'] <= 2000000) {
                $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $logoName = "school_{$schoolId}_logo." . $ext;
                $dir = __DIR__ . '/../../storage/logos/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $logoName);
                $logoPath = "/multi/storage/logos/" . $logoName;
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO school_settings (school_id, primary_color, secondary_color, motto, logo_path)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                primary_color=VALUES(primary_color), 
                secondary_color=VALUES(secondary_color), 
                motto=VALUES(motto), 
                logo_path=IFNULL(VALUES(logo_path), logo_path)
        ");
        $stmt->execute([$schoolId, $_POST['primary'], $_POST['secondary'], $_POST['motto'], $logoPath]);
    }

    elseif ($action === 'prefs') {
        $stmt = $pdo->prepare("
            INSERT INTO school_settings (school_id, dashboard_layout, default_term, academic_year_start)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                dashboard_layout=VALUES(dashboard_layout), 
                default_term=VALUES(default_term), 
                academic_year_start=VALUES(academic_year_start)
        ");
        $stmt->execute([$schoolId, $_POST['dashboard_layout'], $_POST['default_term'], $_POST['academic_year_start']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed: ' . $e->getMessage()]);
}
?>