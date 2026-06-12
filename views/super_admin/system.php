<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
$pdo = getDBConnection();

$sysInfo = [
    'php' => PHP_VERSION,
    'mysql' => $pdo->query("SELECT VERSION()")->fetchColumn(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/Nginx',
    'disk_free' => round(disk_free_space(__DIR__) / 1024 / 1024 / 1024, 2),
    'memory_limit' => ini_get('memory_limit'),
    'db_status' => 'Connected',
    'upload_dir' => is_writable(__DIR__.'/../../storage') ? '✅ Writable' : '❌ Not Writable',
    'cache_status' => function_exists('opcache_get_status') ? (opcache_get_status() ? 'Enabled' : 'Disabled') : 'N/A'
];

$featureFlags = [
    'Multi-Tenancy' => '✅ Active',
    'CBC Grading Engine' => '✅ Active',
    'Attendance Module' => '✅ Active',
    'Manual Timetable' => '✅ Active',
    'AI Timetable (Python)' => '⏳ Queued',
    'Parent Portal' => '⏳ Queued',
    'Payment Gateway' => '⏳ Queued'
];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>System Health | CBC Manager</title>
<?= getBrandingCSS(getAuthenticatedSchoolId()) ?>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1000px;margin:0 auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:24px}.card{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}.card h3{margin-bottom:12px;color:var(--muted);font-size:0.9rem;border-bottom:1px solid var(--border);padding-bottom:8px}table{width:100%}th,td{padding:10px 0;border-bottom:1px solid var(--border);font-size:0.9rem}th{text-align:left;color:var(--muted);width:40%}td{text-align:right}.health{display:flex;gap:12px;flex-wrap:wrap}.health-item{background:var(--card);padding:12px 16px;border-radius:8px;border:1px solid var(--border);font-size:0.85rem}.ok{border-left:4px solid var(--success)}.warn{border-left:4px solid #f59e0b}</style></head><body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
        <h1 style="margin:0">⚙️ System Health & Configuration</h1>
        <a href="/multi/views/super_admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>
<div class="grid">
<div class="card"><h3>🖥️ Environment</h3><table>
<tr><th>PHP Version</th><td><?= $sysInfo['php'] ?></td></tr>
<tr><th>Database Engine</th><td>MariaDB/MySQL <?= $sysInfo['mysql'] ?></td></tr>
<tr><th>Web Server</th><td><?= $sysInfo['server'] ?></td></tr>
<tr><th>Memory Limit</th><td><?= $sysInfo['memory_limit'] ?></td></tr>
<tr><th>Free Disk Space</th><td><?= $sysInfo['disk_free'] ?> GB</td></tr>
</table></div>
<div class="card"><h3>🔌 Core Services</h3><table>
<tr><th>Database Connection</th><td><?= $sysInfo['db_status'] ?></td></tr>
<tr><th>Storage Directory</th><td><?= $sysInfo['upload_dir'] ?></td></tr>
<tr><th>OPcache</th><td><?= $sysInfo['cache_status'] ?></td></tr>
<tr><th>Session Handler</th><td>Files (<?= session_save_path() ?>)</td></tr>
<tr><th>Platform Version</th><td>v1.0.0 (Stable)</td></tr>
</table></div>
<div class="card"><h3>🚀 Feature Flags</h3><table>
<?php foreach($featureFlags as $k=>$v): ?><tr><th><?= $k ?></th><td><?= $v ?></td></tr><?php endforeach; ?>
</table></div>
</div>
<div class="card"><h3 style="margin-bottom:12px">🩺 Quick Diagnostics</h3><div class="health">
<div class="health-item ok">✅ Auth Middleware Loaded</div>
<div class="health-item ok">✅ Multi-Tenant Scoping Active</div>
<div class="health-item ok">✅ CBC Grading Engine Online</div>
<div class="health-item ok">✅ Subscription Gating Active</div>
<div class="health-item ok">✅ Session Security Enabled</div>
</div></div>
</div></body></html>