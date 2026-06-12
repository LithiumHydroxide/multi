<?php
/**
 * Database Migration Helper
 * Run pending database migrations
 */
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('super_admin');

header('Content-Type: application/json');

$pdo = getDBConnection();
$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    $issues = [];
    
    // Check classes columns
    $result = $pdo->query("DESCRIBE `classes`");
    $columns = array_column($result->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    $missing_columns = [];
    if (!in_array('class_teacher_id', $columns)) $missing_columns[] = 'class_teacher_id';
    if (!in_array('assistant_teacher_id', $columns)) $missing_columns[] = 'assistant_teacher_id';
    if (!in_array('prefect_id', $columns)) $missing_columns[] = 'prefect_id';
    
    echo json_encode([
        'needs_migration' => !empty($missing_columns),
        'missing_columns' => $missing_columns,
        'current_columns' => $columns
    ]);
    exit;
}

if ($action === 'run_migration') {
    try {
        // Check if columns already exist
        $result = $pdo->query("DESCRIBE `classes`");
        $columns = array_column($result->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        $executed = [];
        
        // Add class_teacher_id
        if (!in_array('class_teacher_id', $columns)) {
            $pdo->exec("ALTER TABLE `classes` ADD COLUMN `class_teacher_id` INT(10) UNSIGNED DEFAULT NULL AFTER `capacity`");
            $executed[] = "Added class_teacher_id column";
        }
        
        // Add assistant_teacher_id
        if (!in_array('assistant_teacher_id', $columns)) {
            $pdo->exec("ALTER TABLE `classes` ADD COLUMN `assistant_teacher_id` INT(10) UNSIGNED DEFAULT NULL AFTER `class_teacher_id`");
            $executed[] = "Added assistant_teacher_id column";
        }
        
        // Add prefect_id
        if (!in_array('prefect_id', $columns)) {
            $pdo->exec("ALTER TABLE `classes` ADD COLUMN `prefect_id` INT(10) UNSIGNED DEFAULT NULL AFTER `assistant_teacher_id`");
            $executed[] = "Added prefect_id column";
        }
        
        // Add foreign keys if they don't exist
        $constraints = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'classes' AND COLUMN_NAME = 'class_teacher_id'")->fetchAll();
        if (empty($constraints)) {
            $pdo->exec("ALTER TABLE `classes` ADD CONSTRAINT `fk_class_teacher` FOREIGN KEY (`class_teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL");
            $executed[] = "Added class_teacher foreign key";
        }
        
        $constraints = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'classes' AND COLUMN_NAME = 'assistant_teacher_id'")->fetchAll();
        if (empty($constraints)) {
            $pdo->exec("ALTER TABLE `classes` ADD CONSTRAINT `fk_assistant_teacher` FOREIGN KEY (`assistant_teacher_id`) REFERENCES `teachers`(`id`) ON DELETE SET NULL");
            $executed[] = "Added assistant_teacher foreign key";
        }
        
        $constraints = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'classes' AND COLUMN_NAME = 'prefect_id'")->fetchAll();
        if (empty($constraints)) {
            $pdo->exec("ALTER TABLE `classes` ADD CONSTRAINT `fk_prefect` FOREIGN KEY (`prefect_id`) REFERENCES `students`(`id`) ON DELETE SET NULL");
            $executed[] = "Added prefect foreign key";
        }
        
        echo json_encode([
            'success' => true,
            'executed' => $executed,
            'message' => count($executed) . ' changes applied'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
