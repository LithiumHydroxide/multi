<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
$pdo = getDBConnection();

$logs = $pdo->query("
    SELECT al.*, u.name as user_name, s.name as school_name
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN schools s ON al.school_id = s.id
    ORDER BY al.created_at DESC LIMIT 100
")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Audit Logs | CBC Manager</title>
<?= getBrandingCSS(getAuthenticatedSchoolId()) ?>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1200px;margin:0 auto}.header{display:flex;justify-content:space-between;margin-bottom:16px}table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:0.85rem}th{background:#f1f5f9;font-weight:600;color:var(--muted)}code{background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:0.8rem}.badge{padding:3px 8px;border-radius:12px;font-size:0.7rem;font-weight:600;background:#dbeafe;color:#1e40af}.clear{padding:8px 14px;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;cursor:pointer}.search{margin-bottom:12px}</style></head><body>
<div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;flex-wrap:wrap">
    <h1 style="margin:0">🔍 Platform Audit Logs</h1>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button class="clear" onclick="clearLogs()" style="white-space:nowrap">🗑️ Clear Old Logs (>30d)</button>
        <a href="/multi/views/super_admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>
</div>
<div class="search"><input type="text" id="search" placeholder="Filter by action, school, or user..." style="padding:10px;width:100%;border:1px solid var(--border);border-radius:8px"></div>
<table><thead><tr><th>Timestamp</th><th>User</th><th>School</th><th>Action</th><th>Table</th><th>Details</th></tr></thead><tbody id="rows">
<?php foreach($logs as $l): ?>
<tr data-search="<?= strtolower($l['action'].' '.$l['school_name'].' '.$l['user_name']) ?>">
<td><?= date('d M Y, H:i', strtotime($l['created_at'])) ?></td>
<td><?= htmlspecialchars($l['user_name'] ?: 'System') ?></td>
<td><?= htmlspecialchars($l['school_name'] ?: 'Platform') ?></td>
<td><span class="badge"><?= htmlspecialchars($l['action']) ?></span></td>
<td><?= $l['table_name'] ?: '-' ?></td>
<td><code><?= htmlspecialchars(json_decode($l['new_value'] ?: '{}')->message ?? 'Record modified') ?></code></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<script>
document.getElementById('search').addEventListener('input',e=>{const t=e.target.value.toLowerCase();document.querySelectorAll('#rows tr').forEach(r=>r.style.display=r.dataset.search.includes(t)?'':'none');});
async function clearLogs(){if(!confirm('Delete logs older than 30 days?'))return;await fetch('/multi/api/admin/system.php?action=clear_audit',{method:'POST'});location.reload();}
</script></body></html>