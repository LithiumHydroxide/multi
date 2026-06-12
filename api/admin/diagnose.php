<?php
/**
 * Diagnostic script to check system status
 * Access via: http://localhost/multi/api/admin/diagnose.php
 */
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');

header('Content-Type: application/json');

$pdo = getDBConnection();
$schoolId = getAuthenticatedSchoolId();
$issues = [];
$checks = [];

// Check 1: Classes table columns
try {
    $result = $pdo->query("DESCRIBE `classes`");
    $columns = array_column($result->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $checks['classes_columns'] = [
        'status' => 'ok',
        'class_teacher_id' => in_array('class_teacher_id', $columns),
        'assistant_teacher_id' => in_array('assistant_teacher_id', $columns),
        'prefect_id' => in_array('prefect_id', $columns),
        'all_columns' => $columns
    ];
    
    if (!in_array('class_teacher_id', $columns)) {
        $issues[] = "MISSING: class_teacher_id column in classes table - Run migration!";
    }
} catch (Exception $e) {
    $checks['classes_columns'] = ['status' => 'error', 'message' => $e->getMessage()];
    $issues[] = "Classes table check failed: " . $e->getMessage();
}

// Check 2: Teachers count
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM teachers WHERE school_id = $schoolId")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $checks['teachers_count'] = ['status' => 'ok', 'count' => $count];
    if ($count == 0) $issues[] = "WARNING: No teachers found in this school";
} catch (Exception $e) {
    $checks['teachers_count'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check 3: Students count
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM students WHERE school_id = $schoolId AND status = 'active'")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $checks['students_count'] = ['status' => 'ok', 'count' => $count];
    if ($count == 0) $issues[] = "WARNING: No active students found in this school";
} catch (Exception $e) {
    $checks['students_count'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check 4: Classes count
try {
    $count = $pdo->query("SELECT COUNT(*) as cnt FROM classes WHERE school_id = $schoolId")->fetch(PDO::FETCH_ASSOC)['cnt'];
    $checks['classes_count'] = ['status' => 'ok', 'count' => $count];
    if ($count == 0) $issues[] = "WARNING: No classes found in this school";
} catch (Exception $e) {
    $checks['classes_count'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check 5: Foreign keys
try {
    $fks = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'classes' AND COLUMN_NAME IN ('class_teacher_id', 'assistant_teacher_id', 'prefect_id')")->fetchAll(PDO::FETCH_ASSOC);
    $checks['foreign_keys'] = ['status' => 'ok', 'fks' => $fks];
} catch (Exception $e) {
    $checks['foreign_keys'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check 6: Test the actual query
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.name, c.grade_level, 
               ct_u.name as class_teacher, ast_u.name as assistant_teacher,
               p.name as prefect_name
        FROM classes c
        LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
        LEFT JOIN users ct_u ON ct.user_id = ct_u.id
        LEFT JOIN teachers ast ON c.assistant_teacher_id = ast.id
        LEFT JOIN users ast_u ON ast.user_id = ast_u.id
        LEFT JOIN students p ON c.prefect_id = p.id
        WHERE c.school_id = ?
    ");
    $stmt->execute([$schoolId]);
    $data = $stmt->fetchAll();
    $checks['query_test'] = ['status' => 'ok', 'rows_returned' => count($data)];
} catch (Exception $e) {
    $checks['query_test'] = ['status' => 'error', 'message' => $e->getMessage()];
    $issues[] = "Query test failed: " . $e->getMessage();
}

echo json_encode([
    'status' => empty($issues) ? 'healthy' : 'has_issues',
    'issues' => $issues,
    'checks' => $checks,
    'school_id' => $schoolId,
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
