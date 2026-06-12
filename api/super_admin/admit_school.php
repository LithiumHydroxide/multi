<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pdo = getDBConnection();

// Validate required fields
$required = ['school_name', 'admin_email', 'admin_name', 'admin_password', 'plan_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

if (strlen($input['admin_password']) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Admin password must be at least 8 characters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Create School
    $stmt = $pdo->prepare("
        INSERT INTO schools (name, code, county, contact_email, contact_phone, status, onboarding_step, onboarding_completed)
        VALUES (?, ?, ?, ?, ?, 'trial', 0, 0)
    ");
    $stmt->execute([
        trim($input['school_name']),
        !empty($input['school_code']) ? strtoupper(trim($input['school_code'])) : null,
        trim($input['county'] ?? ''),
        strtolower(trim($input['admin_email'])),
        trim($input['contact_phone'] ?? '')
    ]);
    $schoolId = $pdo->lastInsertId();

    // 2. Create Admin User
    $hash = password_hash($input['admin_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (school_id, name, email, password_hash, role, status)
        VALUES (?, ?, ?, ?, 'school_admin', 'active')
    ");
    $stmt->execute([
        $schoolId,
        trim($input['admin_name']),
        strtolower(trim($input['admin_email'])),
        $hash
    ]);

    // 3. Create Default School Settings
    $stmt = $pdo->prepare("
        INSERT INTO school_settings (school_id, system_type, primary_color, secondary_color, dashboard_layout, default_term)
        VALUES (?, 'CBC', '#1e40af', '#f8fafc', 'default', 1)
    ");
    $stmt->execute([$schoolId]);

    // 4. Activate Subscription
    $stmt = $pdo->prepare("
        INSERT INTO school_subscriptions (school_id, plan_id, status, start_date, end_date, auto_renew)
        VALUES (?, ?, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0)
    ");
    $stmt->execute([$schoolId, (int)$input['plan_id']]);

    // 5. Create Default Academic Structure (Optional but helpful)
    // Add Grade 7-9 classes for CBC schools
    if ($input['plan_id'] >= 2) { // Standard or Premium
        $classes = [
            ['Grade 7 East', 7, '7E'],
            ['Grade 8 East', 8, '8E'],
            ['Grade 9 East', 9, '9E']
        ];
        $stmt = $pdo->prepare("INSERT INTO classes (school_id, name, grade_level, stream_code, capacity) VALUES (?, ?, ?, ?, 40)");
        foreach ($classes as $c) {
            $stmt->execute([$schoolId, $c[0], $c[1], $c[2]]);
        }
    }

    $pdo->commit();

    // 6. (Optional) Send welcome email with credentials
    // mail($input['admin_email'], 'Welcome to CBC Manager', "Your school has been admitted...\n\nLogin: {$input['admin_email']}\nPassword: {$input['admin_password']}");

    echo json_encode([
        'success' => true,
        'school_id' => $schoolId,
        'school_name' => trim($input['school_name']),
        'admin_email' => strtolower(trim($input['admin_email'])),
        'start_onboarding' => (bool)($input['start_onboarding'] ?? true)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Admit School Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to admit school: ' . ($e->getMessage())]);
}
?>