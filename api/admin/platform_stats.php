<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
header('Content-Type: application/json');

$pdo = getDBConnection();

// Core metrics
$stats = [];

// Schools
$stats['schools'] = [
    'total' => $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn(),
    'active' => $pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'active'")->fetchColumn(),
    'trial' => $pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'trial'")->fetchColumn(),
    'suspended' => $pdo->query("SELECT COUNT(*) FROM schools WHERE status = 'suspended'")->fetchColumn(),
];

// Users
$stats['users'] = [
    'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'teachers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
    'admins' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'school_admin'")->fetchColumn(),
];

// Students & Academic Data
$stats['academic'] = [
    'students' => $pdo->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn(),
    'classes' => $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn(),
    'assessments' => $pdo->query("SELECT COUNT(*) FROM assessments")->fetchColumn(),
    'scores_recorded' => $pdo->query("SELECT COUNT(*) FROM scores")->fetchColumn(),
];

// Revenue (from subscriptions)
$revenue = $pdo->query("
    SELECT 
        SUM(CASE WHEN sp.billing_cycle = 'termly' THEN sp.price * 3 
                 WHEN sp.billing_cycle = 'annually' THEN sp.price 
                 ELSE sp.price END) as projected_annual,
        COUNT(DISTINCT ss.school_id) as paying_schools
    FROM school_subscriptions ss
    JOIN subscription_plans sp ON ss.plan_id = sp.id
    WHERE ss.status = 'active'
")->fetch();

$stats['revenue'] = [
    'paying_schools' => $revenue['paying_schools'] ?? 0,
    'projected_annual_mrr' => $revenue['projected_annual'] ?? 0,
    'plans' => $pdo->query("SELECT name, COUNT(*) as count FROM school_subscriptions ss JOIN subscription_plans sp ON ss.plan_id = sp.id WHERE ss.status = 'active' GROUP BY sp.name")->fetchAll()
];

// Recent activity
$stats['recent'] = [
    'new_schools_7d' => $pdo->query("SELECT COUNT(*) FROM schools WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'new_users_7d' => $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'scores_entered_24h' => $pdo->query("SELECT COUNT(*) FROM scores WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)")->fetchColumn(),
];

echo json_encode($stats);
?>