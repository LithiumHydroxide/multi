<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
header('Content-Type: application/json');
$schoolId = getAuthenticatedSchoolId();
$userId = getAuthenticatedUserId();
$pdo = getDBConnection();
$action = $_GET['action'] ?? 'dashboard';

try {
    // 1. Dashboard Analytics & Alerts
    if ($action === 'dashboard') {
        // Stats
        $totalItems = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE school_id = $schoolId")->fetchColumn();
        $lowStock = $pdo->query("SELECT COUNT(*) FROM inventory_items WHERE school_id = $schoolId AND quantity_available <= minimum_stock AND status = 'active'")->fetchColumn();
        $assetsRepair = $pdo->query("SELECT COUNT(*) FROM assets WHERE school_id = $schoolId AND condition_status = 'Under Repair'")->fetchColumn();
        $booksIssued = $pdo->query("SELECT COUNT(*) FROM book_issues WHERE school_id = $schoolId AND status = 'issued'")->fetchColumn();
        $pendingReturns = $pdo->query("SELECT COUNT(*) FROM book_issues WHERE school_id = $schoolId AND status = 'issued' AND expected_return_date < CURDATE()")->fetchColumn();

        // Alerts
        $alerts = [];
        $lowStockItems = $pdo->query("SELECT item_name, quantity_available, minimum_stock FROM inventory_items WHERE school_id = $schoolId AND quantity_available <= minimum_stock AND status = 'active' LIMIT 3")->fetchAll();
        foreach ($lowStockItems as $item) {
            $alerts[] = ['type' => 'danger', 'msg' => "🔴 {$item['item_name']} below minimum stock ({$item['quantity_available']}/{$item['minimum_stock']})"];
        }
        if ($assetsRepair > 0) $alerts[] = ['type' => 'warning', 'msg' => "🟠 $assetsRepair asset(s) currently Under Repair"];
        if ($pendingReturns > 0) $alerts[] = ['type' => 'warning', 'msg' => "🟠 $pendingReturns overdue textbook return(s)"];

        // Chart Data: Inventory by Category
        $catData = $pdo->query("
            SELECT c.name, SUM(i.quantity_available) as total_qty 
            FROM inventory_items i 
            JOIN inventory_categories c ON i.category_id = c.id 
            WHERE i.school_id = $schoolId GROUP BY c.id
        ")->fetchAll();

        echo json_encode([
            'success' => true,
            'stats' => [
                'total_items' => $totalItems,
                'low_stock' => $lowStock,
                'assets_repair' => $assetsRepair,
                'books_issued' => $booksIssued,
                'pending_returns' => $pendingReturns
            ],
            'alerts' => $alerts,
            'category_chart' => $catData
        ]);
        exit;
    }

    // 2. List Items
    if ($action === 'list_items') {
        $stmt = $pdo->prepare("
            SELECT i.*, c.name as category_name 
            FROM inventory_items i 
            JOIN inventory_categories c ON i.category_id = c.id 
            WHERE i.school_id = ? 
            ORDER BY i.item_name ASC
        ");
        $stmt->execute([$schoolId]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>