<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
$pdo = getDBConnection();

$revData = $pdo->query("
    SELECT sp.name, sp.billing_cycle, sp.price, COUNT(ss.id) as school_count,
           SUM(CASE WHEN sp.billing_cycle='monthly' THEN sp.price*12
                    WHEN sp.billing_cycle='termly' THEN sp.price*3
                    ELSE sp.price END) as annual_proj
    FROM school_subscriptions ss JOIN subscription_plans sp ON ss.plan_id = sp.id
    WHERE ss.status = 'active' GROUP BY sp.id
")->fetchAll();

$totalMRR = array_sum(array_map(fn($r)=>$r['annual_proj']/12, $revData));
$totalSchools = array_sum(array_map(fn($r)=>$r['school_count'], $revData));
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Revenue & Plans | CBC Manager</title>
<?= getBrandingCSS(getAuthenticatedSchoolId()) ?>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1100px;margin:0 auto}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-bottom:24px}.card{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}.card h3{font-size:0.85rem;color:var(--muted);margin-bottom:8px}.card .val{font-size:1.8rem;font-weight:700}.chart{display:flex;align-items:flex-end;gap:16px;height:180px;padding:20px 0}.bar{flex:1;background:var(--primary);border-radius:4px 4px 0 0;position:relative;transition:0.3s}.bar span{position:absolute;top:-22px;left:50%;transform:translateX(-50%);font-size:0.75rem;font-weight:600;white-space:nowrap}table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}th{background:#f1f5f9;font-weight:600;color:var(--muted)}</style></head><body>
<div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
    <h1 style="margin:0">💰 Revenue & Subscription Tracking</h1>
    <a href="/multi/views/super_admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
</div>
<div class="grid">
<div class="card"><h3>Active Paying Schools</h3><div class="val"><?= $totalSchools ?></div></div>
<div class="card"><h3>Projected MRR</h3><div class="val">KES <?= number_format($totalMRR, 0) ?></div></div>
<div class="card"><h3>Projected ARR</h3><div class="val">KES <?= number_format($totalMRR * 12, 0) ?></div></div>
<div class="card"><h3>Plan Tiers</h3><div class="val"><?= count($revData) ?></div></div>
</div>
<div class="card"><h3 style="margin-bottom:16px">Revenue Distribution by Plan</h3><div class="chart" id="revChart"></div></div>
<div class="card" style="margin-top:20px"><h3 style="margin-bottom:16px">Active Subscriptions Breakdown</h3>
<table><thead><tr><th>Plan</th><th>Schools</th><th>Billing Cycle</th><th>Monthly Price</th><th>Projected Annual</th></tr></thead><tbody>
<?php foreach($revData as $r): ?>
<tr><td><strong><?= htmlspecialchars($r['name']) ?></strong></td><td><?= $r['school_count'] ?></td><td><?= ucfirst($r['billing_cycle']) ?></td><td>KES <?= number_format($r['price']) ?></td><td>KES <?= number_format($r['annual_proj']) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
</div>
<script>
const data=<?= json_encode($revData) ?>;
const max=Math.max(...data.map(d=>d.school_count),1);
document.getElementById('revChart').innerHTML=data.map(d=>`<div class="bar" style="height:${(d.school_count/max)*100}%"><span>${d.name}<br>${d.school_count} schools</span></div>`).join('');
</script></body></html>