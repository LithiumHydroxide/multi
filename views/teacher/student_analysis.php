<?php require_once '../../config.php'; require_once __DIR__.'/../../app/Middleware/ModuleAccessMiddleware.php'; ModuleAccessMiddleware::requireFeature('advanced_analytics'); AuthMiddleware::requireAuth(); AuthMiddleware::requireSchoolScope(); ?>
<!DOCTYPE html><html><head><title>Premium Analysis | CBC Manager</title>
<style>body{font-family:system-ui;padding:24px;background:#f8fafc}.card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:16px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:16px}.bar{height:8px;background:#e2e8f0;border-radius:4px;margin:4px 0}.bar-fill{height:100%;background:var(--primary);border-radius:4px}.tag{display:inline-block;padding:4px 8px;border-radius:20px;font-size:0.75rem;font-weight:600;margin-right:4px}.ee{background:#d1fae5;color:#059669}.me{background:#dbeafe;color:#2563eb}.ae{background:#fef3c7;color:#d97706}.be{background:#fee2e2;color:#dc2626}</style></head>
<body>
<h1 style="margin-bottom:16px">📊 Student Performance Analysis <span style="font-size:0.8rem;background:#f59e0b;color:#000;padding:2px 8px;border-radius:12px">PREMIUM</span></h1>
<div style="display:flex;gap:12px;margin-bottom:20px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
<div style="display:flex;gap:10px">
<input type="number" id="sid" placeholder="Student ID" style="padding:10px;border:1px solid #e2e8f0;border-radius:8px"><button onclick="load()" style="padding:10px 16px;background:#1e40af;color:#fff;border:none;border-radius:8px;cursor:pointer">Analyze</button>
</div>
<a href="/multi/views/teacher/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back to Dashboard</a>
</div>
<div class="grid">
    <div class="card"><h3>Subject Averages</h3><div id="chart"></div></div>
    <div class="card"><h3>Assignment History</h3><div id="history"></div></div>
</div>
<script>
async function load(){const id=document.getElementById('sid').value;if(!id)return;const res=await fetch(`/multi/api/analysis/student_performance.php?student_id=${id}`);const d=await res.json();
document.getElementById('chart').innerHTML=d.trend.slice(0,10).map(t=>`<div style="margin-bottom:8px"><strong>${t.subject}</strong>: ${t.avg}%<div class="bar"><div class="bar-fill" style="width:${t.avg}%"></div></div></div>`).join('');
document.getElementById('history').innerHTML=d.assignments.map(a=>`<div style="padding:10px;border-bottom:1px solid #e2e8f0"><strong>${a.title}</strong> (Term ${a.term})<br>${a.marks_obtained}/${a.max_marks} <span class="tag ${a.achievement_level.substring(0,2).toLowerCase()}">${a.achievement_level}</span> ${a.remarks?'<br>💬 '+a.remarks:''}</div>`).join('');}
</script></body></html>