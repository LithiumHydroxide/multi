<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fetch dashboard stats
$statsStmt = $pdo->prepare("
SELECT 
    (SELECT COUNT(*) FROM inventory_items WHERE school_id = ? AND status = 'active') as total_items,
    (SELECT COUNT(*) FROM inventory_items WHERE school_id = ? AND quantity_available <= minimum_stock AND status = 'active') as low_stock,
    (SELECT COUNT(*) FROM assets WHERE school_id = ? AND condition_status = 'Under Repair') as assets_repair,
    (SELECT COUNT(*) FROM book_issues WHERE school_id = ? AND status = 'issued') as books_issued,
    (SELECT COUNT(*) FROM book_issues WHERE school_id = ? AND status = 'issued' AND expected_return_date < CURDATE()) as pending_returns
");
$statsStmt->execute([$schoolId, $schoolId, $schoolId, $schoolId, $schoolId]);
$stats = $statsStmt->fetch();

// Fetch alerts
$alertsStmt = $pdo->prepare("
SELECT item_name, quantity_available, minimum_stock 
FROM inventory_items 
WHERE school_id = ? AND quantity_available <= minimum_stock AND status = 'active'
LIMIT 5
");
$alertsStmt->execute([$schoolId]);
$lowStockItems = $alertsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root {
--shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
--shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
--radius: 16px;
--radius-sm: 12px;
--transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
body { background: var(--bg, #f8fafc); color: var(--text, #0f172a); padding: 32px; }
.container { max-width: 1400px; margin: 0 auto; }

/* Header */
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
.header h1 { font-size: 1.75rem; font-weight: 700; color: var(--text, #0f172a); }
.header p { color: var(--text-muted, #64748b); margin-top: 4px; font-size: 0.95rem; }
.btn { padding: 10px 20px; background: var(--primary, #1e40af); color: white; border: none; border-radius: var(--radius-sm); cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: var(--transition); display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
.btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: var(--shadow-md); }
.btn-secondary { background: var(--card, #fff); color: var(--text, #0f172a); border: 1px solid var(--border, #e2e8f0); }
.btn-secondary:hover { background: var(--bg, #f8fafc); border-color: var(--primary, #1e40af); }

/* Stats Grid */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
.stat-card {
background: var(--card, #fff);
padding: 24px;
border-radius: var(--radius);
box-shadow: var(--shadow-sm);
border: 1px solid var(--border, #e2e8f0);
transition: var(--transition);
}
.stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light, #3b82f6); }
.stat-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-bg, rgba(30, 64, 175, 0.08)); color: var(--primary, #1e40af); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 12px; }
.stat-card h3 { font-size: 0.8rem; color: var(--text-muted, #64748b); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
.stat-value { font-size: 1.6rem; font-weight: 700; color: var(--text, #0f172a); }
.stat-value.warning { color: var(--warning, #f59e0b); }
.stat-value.danger { color: var(--danger, #ef4444); }

/* Alerts Section */
.alerts-section { background: var(--card, #fff); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border, #e2e8f0); margin-bottom: 24px; }
.alerts-section h2 { font-size: 1.15rem; font-weight: 600; color: var(--text, #0f172a); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.alert-item { padding: 12px; border-left: 4px solid var(--danger, #ef4444); background: var(--danger-bg, #fee2e2); border-radius: 0 8px 8px 0; margin-bottom: 8px; font-size: 0.9rem; }
.alert-item:last-child { margin-bottom: 0; }
.no-alerts { color: var(--text-muted, #64748b); text-align: center; padding: 20px; font-style: italic; }

/* Quick Actions */
.actions-section { background: var(--card, #fff); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border, #e2e8f0); }
.actions-section h2 { font-size: 1.15rem; font-weight: 600; color: var(--text, #0f172a); margin-bottom: 20px; }
.actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
.action-card {
background: var(--bg, #f8fafc);
padding: 20px 16px;
border-radius: var(--radius-sm);
border: 1px solid var(--border, #e2e8f0);
text-align: center;
text-decoration: none;
color: var(--text, #0f172a);
transition: var(--transition);
display: flex;
flex-direction: column;
align-items: center;
gap: 12px;
cursor: pointer;
}
.action-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary, #1e40af); background: rgba(30, 64, 175, 0.02); }
.action-icon { font-size: 1.5rem; }
.action-card h4 { font-size: 0.95rem; font-weight: 600; margin: 0; }
.action-card p { font-size: 0.78rem; color: var(--text-muted, #64748b); margin: 0; line-height: 1.4; }

@media (max-width: 768px) {
.stats-grid { grid-template-columns: 1fr; }
.actions-grid { grid-template-columns: 1fr; }
.header { flex-direction: column; align-items: flex-start; }
}
</style>
</head>
<body>
<div class="container">
<div class="header">
<div>
<h1>📦 Inventory & Asset Management</h1>
<p>Track stock levels, manage assets, and monitor textbook issuance</p>
</div>
<div style="display:flex; gap:12px;">
<a href="/multi/views/admin/dashboard.php" class="btn btn-secondary">← Back</a>
<button class="btn" onclick="alert('Add Item modal coming soon!')">+ Add Item</button>
</div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
<div class="stat-card">
<div class="stat-icon"></div>
<h3>Total Items</h3>
<div class="stat-value"><?= number_format($stats['total_items'] ?? 0) ?></div>
</div>
<div class="stat-card">
<div class="stat-icon" style="background: var(--danger-bg, #fee2e2); color: var(--danger, #ef4444);">⚠️</div>
<h3>Low Stock Alerts</h3>
<div class="stat-value danger"><?= number_format($stats['low_stock'] ?? 0) ?></div>
</div>
<div class="stat-card">
<div class="stat-icon" style="background: var(--warning-bg, #fef3c7); color: var(--warning, #f59e0b);">🔧</div>
<h3>Assets Under Repair</h3>
<div class="stat-value warning"><?= number_format($stats['assets_repair'] ?? 0) ?></div>
</div>
<div class="stat-card">
<div class="stat-icon">📚</div>
<h3>Books Issued</h3>
<div class="stat-value"><?= number_format($stats['books_issued'] ?? 0) ?></div>
</div>
<div class="stat-card">
<div class="stat-icon" style="background: var(--warning-bg, #fef3c7); color: var(--warning, #f59e0b);">⏳</div>
<h3>Pending Returns</h3>
<div class="stat-value warning"><?= number_format($stats['pending_returns'] ?? 0) ?></div>
</div>
</div>

<!-- Alerts Section -->
<?php if (!empty($lowStockItems)): ?>
<div class="alerts-section">
<h2>️ Low Stock Alerts</h2>
<?php foreach ($lowStockItems as $item): ?>
<div class="alert-item">
<strong><?= htmlspecialchars($item['item_name']) ?></strong> - Only <?= $item['quantity_available'] ?> remaining (Minimum: <?= $item['minimum_stock'] ?>)
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="alerts-section">
<h2>✅ Inventory Status</h2>
<div class="no-alerts">All items are adequately stocked. No alerts at this time.</div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="actions-section">
<h2>⚡ Quick Actions</h2>
<div class="actions-grid">
<div class="action-card" onclick="alert('Stock Take feature coming soon!')">
<div class="action-icon">📋</div>
<h4>Start Stock Take</h4>
<p>Conduct physical inventory count</p>
</div>
<div class="action-card" onclick="alert('Issue Textbook feature coming soon!')">
<div class="action-icon">📚</div>
<h4>Issue Textbook</h4>
<p>Assign books to students</p>
</div>
<div class="action-card" onclick="alert('Register Asset feature coming soon!')">
<div class="action-icon">💻</div>
<h4>Register Asset</h4>
<p>Add high-value equipment</p>
</div>
<div class="action-card" onclick="alert('Full Report feature coming soon!')">
<div class="action-icon">📊</div>
<h4>View Full Report</h4>
<p>Detailed inventory analytics</p>
</div>
</div>
</div>
</div>
</body>
</html>