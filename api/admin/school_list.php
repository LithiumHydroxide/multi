<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
header('Content-Type: application/json');

$pdo = getDBConnection();
$stmt = $pdo->query("
    SELECT 
        s.id, s.name, s.county, s.contact_email, s.status, s.created_at,
        sp.name as plan_name,
        (SELECT COUNT(*) FROM students WHERE school_id = s.id AND status = 'active') as student_count
    FROM schools s
    LEFT JOIN school_subscriptions ss ON s.id = ss.school_id AND ss.status = 'active'
    LEFT JOIN subscription_plans sp ON ss.plan_id = sp.id
    ORDER BY s.created_at DESC
");
echo json_encode($stmt->fetchAll());
?>