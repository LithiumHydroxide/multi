<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Reports & Analytics | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1000px;margin:0 auto}.header{display:flex;justify-content:space-between;margin-bottom:20px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}.card{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}.card h3{font-size:0.85rem;color:var(--muted);margin-bottom:8px}.card .val{font-size:1.8rem;font-weight:700}.section{background:var(--card);padding:20px;border-radius:12px;margin-bottom:16px}.chart{display:flex;align-items:flex-end;gap:12px;height:200px;padding-top:20px}.bar{flex:1;background:var(--primary);border-radius:4px 4px 0 0;position:relative;transition:0.3s}.bar:hover{opacity:0.8}.bar span{position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:0.75rem;font-weight:600}.btn{padding:10px 16px;background:#f1f5f9;border:1px solid var(--border);border-radius:6px;cursor:pointer}</style></head>
<body>
<div class="container">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
        <div style="display:flex;gap:12px;align-items:center">
            <h1 style="margin:0">📈 Reports & Analytics</h1>
            <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
        </div>
        <button class="btn" onclick="window.print()" style="margin:0">🖨️ Export/Print</button>
    </div>
    <div class="grid">
        <div class="card"><h3>Attendance (30d)</h3><div class="val" id="attPct">-</div></div>
        <div class="card"><h3>Subjects Tracked</h3><div class="val" id="subCount">-</div></div>
        <div class="card"><h3>Top Performing</h3><div class="val" id="topSub">-</div></div>
    </div>
    <div class="section">
        <h2 style="margin-bottom:16px">Subject Performance Averages</h2>
        <div class="chart" id="chart"></div>
    </div>
</div>
<script>
fetch('/multi/api/reports/academic.php')
  .then(r => r.json())
  .then(d => {
    document.getElementById('attPct').textContent = (d.attendance?.pct || 0) + '%';
    document.getElementById('subCount').textContent = d.subjects?.length || 0;
    document.getElementById('topSub').textContent = d.subjects?.[0]?.name || 'No Data';
    
    const max = Math.max(...(d.subjects?.map(s=>s.avg_pct) || [1]), 1);
    document.getElementById('chart').innerHTML = (d.subjects || []).slice(0,10).map(s => 
      `<div class="bar" style="height:${(s.avg_pct/max)*100}%"><span>${s.avg_pct}%</span></div>`
    ).join('') || '<div style="color:#64748b;width:100%;text-align:center;padding:40px">No assessment data recorded yet.</div>';
  })
  .catch(e => console.error('Reports load failed', e));
</script>
</body>
</html>