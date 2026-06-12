<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();

// Allow both teachers and school_admins to access student lists
$role = getAuthenticatedUserRole();
if (!in_array($role, ['teacher', 'school_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $classId = $_GET['class_id'] ?? null;
        
        $sql = "SELECT s.id, s.name, s.admission_number, s.gender, s.dob, s.status, s.class_id, 
                       c.name as class_name, 
                       tu.name as class_teacher_name,
                       p.name as class_prefect_name,
                       s.created_at
                FROM students s 
                LEFT JOIN classes c ON s.class_id = c.id 
                LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
                LEFT JOIN users tu ON ct.user_id = tu.id
                LEFT JOIN students p ON c.prefect_id = p.id
                WHERE s.school_id = ?";
        $params = [$schoolId];
        
        // Filter by status
        $status = $_GET['status'] ?? 'active';
        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        // Filter by class if provided
        if ($classId) {
            $sql .= " AND s.class_id = ?";
            $params[] = $classId;
        }
        
        $sql .= " ORDER BY s.name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        exit;
    }
    
    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>