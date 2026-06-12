<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT * FROM school_settings WHERE school_id = ?");
    $stmt->execute([$schoolId]);
    echo json_encode($stmt->fetch() ?: []);
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $logoPath = null;
    
    // Handle Logo Upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === 0) {
        $allowed = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($_FILES['logo']['type'], $allowed)) { http_response_code(400); echo json_encode(['error'=>'Invalid image type']); exit; }
        if ($_FILES['logo']['size'] > 2000000) { http_response_code(400); echo json_encode(['error'=>'Max 2MB']); exit; }
        
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logoName = "school_{$schoolId}_logo." . $ext;
        $uploadDir = __DIR__ . '/../../storage/logos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName);
        $logoPath = "/multi/storage/logos/" . $logoName;
    }

    $stmt = $pdo->prepare("
        INSERT INTO school_settings (school_id, system_type, logo_path, primary_color, secondary_color, motto, address, contact_phone)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE system_type=?, logo_path=VALUES(logo_path), primary_color=?, secondary_color=?, motto=?, address=?, contact_phone=?
    ");
    $stmt->execute([
        $schoolId, $data['system'], $logoPath, $data['primary'], $data['secondary'], $data['motto'], $data['address'], $data['phone'],
        $data['system'], $data['primary'], $data['secondary'], $data['motto'], $data['address'], $data['phone']
    ]);
    echo json_encode(['success' => true]); exit;
}

if ($action === 'reset' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare("DELETE FROM school_settings WHERE school_id = ?")->execute([$schoolId]);
    echo json_encode(['success' => true]);
    exit;
}
?>