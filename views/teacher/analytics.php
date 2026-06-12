<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher');
$schoolId = getAuthenticatedSchoolId();
$userId = getAuthenticatedUserId();
$pdo = getDBConnection();

// Fetch assigned classes for dropdown
$classesStmt = $pdo->prepare("
SELECT c.id, c.name
FROM classes c
JOIN class_subject_teacher cst ON c.id = cst.class_id
WHERE cst.teacher_id = (SELECT id FROM teachers WHERE user_id = ?)
ORDER BY c.grade_level, c.name
");
$classesStmt->execute([$userId]);
$classes = $classesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<style>
/* Layout & Structural Variables - Colors inherited from Branding CSS */
:root {
--shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
--shadow-md: 0 4px 6px rgba(0,0,0,0.1);
--radius: 12px;
--radius-lg: 16px;
}
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
html, body { background: var(--bg, #f8fafc); color: var(--text, #0f172a); }
body { padding: 24px; }
.container { max-width: 1400px; margin: 0 auto; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; gap: 16px; flex-wrap: wrap; }
.header h1 { font-size: 1.875rem; font-weight: 700; color: var(--text, #0f172a); }
.btn { padding: 10px 16px; background: var(--primary, #1e40af); color: white; border: none; border-radius: var(--radius); cursor: pointer; font-weight: 600; font-size: 0.95rem; transition: all 0.2s; display: flex; align-items: center; gap: 8px; }
.btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: var(--shadow-md); }
.btn-secondary { background: var(--card, #fff); color: var(--text, #0f172a); border: 1px solid var(--border, #e2e8f0); }
.controls { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
select { padding: 10px 12px; border: 1px solid var(--border, #e2e8f0); border-radius: var(--radius); font-size: 0.95rem; background: var(--card, #fff); color: var(--text, #0f172a); cursor: pointer; min-width: 200px; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 32px; }
.card { background: var(--card, #fff); border-radius: var(--radius-lg); padding: 24px; border: 1px solid var(--border, #e2e8f0); box-shadow: var(--shadow-sm); transition: all 0.3s; }
.card:hover { border-color: var(--primary-light, #3b82f6); box-shadow: var(--shadow-md); }
.card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; gap: 12px; }
.card-title { font-size: 1.15rem; font-weight: 700; color: var(--text, #0f172a); display: flex; align-items: center; gap: 8px; }
.badge { padding: 6px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
.badge-success { background: var(--success-light, #d1fae5); color: #065f46; }
.badge-warning { background: var(--warning-light, #fef3c7); color: #92400e; }
.badge-danger { background: var(--danger-light, #fee2e2); color: #7f1d1d; }
.badge-neutral { background: #f1f5f9; color: #64748b; }
.stat-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--border, #e2e8f0); font-size: 0.95rem; }
.stat-row:last-child { border-bottom: none; }
.stat-label { color: var(--muted, #64748b); font-weight: 500; }
.stat-value { font-size: 1.35rem; font-weight: 700; color: var(--primary, #1e40af); }
.chart-container { position: relative; height: 300px; margin-top: 16px; }
.heatmap-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 12px; margin-top: 12px; }
.heatmap-tile { padding: 12px; border-radius: 8px; text-align: center; transition: 0.2s; }
.heatmap-tile .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; margin-bottom: 4px; }
.heatmap-tile .pct { font-size: 1.25rem; font-weight: 700; }
.empty-state { text-align: center; padding: 40px 20px; color: var(--muted, #64748b); }
.empty-state-icon { font-size: 3rem; margin-bottom: 12px; }
@media (max-width: 768px) {
.grid { grid-template-columns: 1fr; }
.header { flex-direction: column; align-items: flex-start; }
.controls { flex-direction: column; align-items: stretch; }
select { width: 100%; }
}
</style>
</head>
<body>
<div class="container">
<div class="header">
<div>
<h1>📊 Academic Performance Analytics</h1>
<p style="color: var(--muted, #64748b); margin-top: 4px;">Track class progress, CBC competencies, and student achievements</p>
</div>
<a href="/multi/views/teacher/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>

<div class="controls">
<div style="flex:1">
<label style="display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px">Filter by Class</label>
<select id="classSelect" onchange="loadClassAnalytics()">
<option value="">📚 All Assigned Classes</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div style="flex:1">
<label style="display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px">Student Detail View</label>
<select id="studentSelect" onchange="loadStudentAnalytics()">
<option value="">👤 Select Student for Details</option>
</select>
</div>
<div style="margin-top: 24px;">
<button class="btn btn-secondary" onclick="loadClassAnalytics()">🔄 Refresh Data</button>
</div>
</div>

<!-- Section 1: Class Overview -->
<h2 style="margin-bottom: 16px; font-size: 1.1rem;">Class Overview</h2>
<div class="grid" id="classOverview">
<div class="empty-state">
<div class="empty-state-icon">📊</div>
<p>Loading class data...</p>
</div>
</div>

<!-- Section 2: Student Detail (Hidden until selected) -->
<div id="studentSection" style="display:none;">
<div style="display:flex; justify-content:space-between; align-items:center; margin: 32px 0 24px;">
<h2 style="font-size: 1.5rem;">👤 Student Profile: <span id="studentName" style="color:var(--primary, #1e40af)"></span></h2>
<span id="riskBadge" class="badge badge-neutral">Risk: Low</span>
</div>
<div class="grid">
<!-- Growth Chart -->
<div class="card">
<div class="card-header">
<div class="card-title">📈 Assessment Scores</div>
<span id="trendBadge" class="badge badge-neutral">Stable</span>
</div>
<div class="chart-container"><canvas id="growthChart"></canvas></div>
</div>
<!-- Attendance Chart -->
<div class="card">
<div class="card-header">
<div class="card-title">📅 Attendance Record</div>
</div>
<div class="chart-container"><canvas id="attendanceChart"></canvas></div>
</div>
<!-- Competency Radar & Heatmap -->
<div class="card" style="grid-column: 1 / -1;">
<div class="card-header">
<div class="card-title">🎯 CBC Competency Intelligence</div>
<span id="compBadge" class="badge badge-neutral">--</span>
</div>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
<div>
<h4 style="font-size:0.95rem;color:var(--muted);margin-bottom:12px">Learner Competency Profile</h4>
<div class="chart-container" style="height: 350px;"><canvas id="competencyRadar"></canvas></div>
</div>
<div>
<h4 style="font-size:0.95rem;color:var(--muted);margin-bottom:12px">Class Competency Heatmap</h4>
<div id="heatmapGrid" class="heatmap-grid"></div>
</div>
</div>
</div>
</div>
</div>
</div>

<script>
let growthChart, attendanceChart, competencyRadar;

// 1. Load Class Overview
async function loadClassAnalytics() {
const classId = document.getElementById('classSelect').value;
const container = document.getElementById('classOverview');
container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⏳</div><p>Loading...</p></div>';

try {
// Trigger background trend calculation (ignore errors if not ready)
fetch('/multi/api/teacher/calculate_trends.php').catch(() => {});

const res = await fetch(`/multi/api/teacher/analytics_engine.php?action=dashboard${classId ? '&class_id='+classId : ''}`);
const data = await res.json();

if (!data.success || !data.classes || data.classes.length === 0) {
container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">📚</div><p>No class data available.</p></div>';
return;
}

container.innerHTML = data.classes.map(c => {
const trendBadge = c.improving_pct > c.declining_pct ? '<span class="badge badge-success">📈 Improving</span>' : 
                   (c.declining_pct > c.improving_pct ? '<span class="badge badge-danger">📉 Declining</span>' : '<span class="badge badge-neutral">➡️ Stable</span>');
const consColor = c.avg_consistency < 10 ? 'var(--success, #059669)' : (c.avg_consistency < 20 ? 'var(--warning, #d97706)' : 'var(--danger, #dc2626)');

return `
<div class="card">
<div class="card-header">
<div class="card-title">${c.class_name}</div>
${trendBadge}
</div>
<div class="stat-row"><span class="stat-label">👥 Students</span><span class="stat-value">${c.student_count}</span></div>
<div class="stat-row"><span class="stat-label">✅ Avg Score</span><span class="stat-value">${c.avg_score}%</span></div>
<div class="stat-row"><span class="stat-label">⚖️ Consistency</span><span class="stat-value" style="color:${consColor}">${c.avg_consistency}</span></div>
<button onclick="loadStudentsForClass(${c.class_id})" style="width:100%; margin-top:16px; padding:10px; background:var(--primary, #1e40af); color:#fff; border:none; border-radius:var(--radius); cursor:pointer; font-weight:600;">👤 View Students</button>
</div>`;
}).join('');
} catch (err) {
console.error('Error loading class analytics:', err);
container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">⚠️</div><p>Error loading data.</p></div>';
}
}

// 2. Load Students Dropdown (Robust Error Handling)
async function loadStudentsForClass(classId) {
try {
const res = await fetch(`/multi/api/student/crud.php?action=list&class_id=${classId}`);
if (!res.ok) throw new Error(`HTTP ${res.status}`);
const students = await res.json();

const select = document.getElementById('studentSelect');
select.innerHTML = '<option value="">👤 Select Student for Details</option>';

if (Array.isArray(students)) {
students.forEach(s => {
const opt = document.createElement('option');
opt.value = s.id;
opt.textContent = `${s.name} (${s.admission_number})`;
select.appendChild(opt);
});
} else {
console.error('Expected array, got:', students);
}
} catch (err) {
console.error('Error loading students:', err);
document.getElementById('studentSelect').innerHTML = '<option value="">Error loading students</option>';
}
}

// 3. Load Student Detail Analytics
async function loadStudentAnalytics() {
const studentId = document.getElementById('studentSelect').value;
const section = document.getElementById('studentSection');
if (!studentId) { section.style.display = 'none'; return; }
section.style.display = 'block';

// Set Name
const opt = document.getElementById('studentSelect').options[document.getElementById('studentSelect').selectedIndex];
document.getElementById('studentName').textContent = opt ? opt.textContent : 'Student';

// Load Growth
try {
const growthRes = await fetch(`/multi/api/teacher/analytics_engine.php?action=student_growth&student_id=${studentId}`);
const growthData = await growthRes.json();
renderGrowthChart(growthData.success ? growthData.data : []);
} catch(e) { console.error(e); }

// Load Attendance
try {
const attRes = await fetch(`/multi/api/teacher/analytics_engine.php?action=attendance_data&student_id=${studentId}`);
const attData = await attRes.json();
renderAttendanceChart(attData.success ? attData.summary : {present: 0, absent: 0, late: 0, excused: 0});
} catch(e) { console.error(e); }

// Load Competency Radar
try {
const radarRes = await fetch(`/multi/api/teacher/competency_analytics.php?action=student_radar&student_id=${studentId}`);
const radarData = await radarRes.json();
renderCompetencyRadar(radarData.success ? radarData.competencies : {});
} catch(e) { console.error(e); }

// Load Class Heatmap
const classId = document.getElementById('classSelect').value;
if (classId) {
try {
const heatRes = await fetch(`/multi/api/teacher/competency_analytics.php?action=class_heatmap&class_id=${classId}`);
const heatData = await heatRes.json();
renderClassHeatmap(heatData.success ? heatData.heatmap : []);
} catch(e) { console.error(e); }
}

// Load Risk/Interventions
try {
const riskRes = await fetch(`/multi/api/teacher/analytics_engine.php?action=interventions&student_id=${studentId}`);
const riskData = await riskRes.json();
if (riskData.success) {
const riskBadge = document.getElementById('riskBadge');
const factors = riskData.risk_factors || [];
if (factors.length >= 3) { riskBadge.textContent = '🔴 High Risk'; riskBadge.className = 'badge badge-danger'; }
else if (factors.length >= 1) { riskBadge.textContent = '🟡 Medium Risk'; riskBadge.className = 'badge badge-warning'; }
else { riskBadge.textContent = '🟢 Low Risk'; riskBadge.className = 'badge badge-success'; }
}
} catch (e) {
console.warn('Interventions endpoint not ready:', e);
}
}

// 4. Render Growth Chart
function renderGrowthChart(data) {
const ctx = document.getElementById('growthChart').getContext('2d');
if (growthChart) growthChart.destroy();

if (!data || data.length === 0) {
ctx.font = '14px Inter'; ctx.fillStyle = '#64748b'; ctx.textAlign = 'center';
ctx.fillText('No scores recorded yet', ctx.canvas.width / 2, ctx.canvas.height / 2);
document.getElementById('trendBadge').textContent = 'No Data';
return;
}

const labels = data.map(d => d.assessment_name || d.title || 'Assessment');
const scores = data.map(d => parseFloat(d.marks_obtained) || 0);
const colors = scores.map(s => s >= 80 ? 'var(--success, #059669)' : s >= 60 ? 'var(--primary, #1e40af)' : s >= 40 ? 'var(--warning, #d97706)' : 'var(--danger, #dc2626)');

growthChart = new Chart(ctx, {
type: 'bar',
data: { labels, datasets: [{ label: 'Score %', data: scores, backgroundColor: colors, borderRadius: 8 }] },
options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } }
});

// Simple trend calculation
const slope = scores.length >= 2 ? scores[scores.length - 1] - scores[0] : 0;
const badge = document.getElementById('trendBadge');
if (slope > 5) { badge.textContent = '📈 Improving'; badge.className = 'badge badge-success'; }
else if (slope < -5) { badge.textContent = '📉 Declining'; badge.className = 'badge badge-danger'; }
else { badge.textContent = '➡️ Stable'; badge.className = 'badge badge-neutral'; }
}

// 5. Render Attendance Chart
function renderAttendanceChart(summary) {
const ctx = document.getElementById('attendanceChart').getContext('2d');
if (attendanceChart) attendanceChart.destroy();

attendanceChart = new Chart(ctx, {
type: 'doughnut',
data: {
labels: ['Present', 'Absent', 'Late', 'Excused'],
datasets: [{
data: [summary.present || 0, summary.absent || 0, summary.late || 0, summary.excused || 0],
backgroundColor: ['#d1fae5', '#fee2e2', '#fef3c7', '#dbeafe'],
borderColor: ['#065f46', '#7f1d1d', '#92400e', '#1e40af'],
borderWidth: 2
}]
},
options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
}

// 6. Render Competency Radar
function renderCompetencyRadar(comps) {
const ctx = document.getElementById('competencyRadar').getContext('2d');
if (competencyRadar) competencyRadar.destroy();

const labels = Object.keys(comps).map(k => k.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
const values = Object.values(comps).map(v => v.mastery_pct || 0);
const avg = values.length > 0 ? values.reduce((a,b)=>a+b, 0) / values.length : 0;

competencyRadar = new Chart(ctx, {
type: 'radar',
data: {
labels,
datasets: [{
label: 'Mastery %', data: values,
backgroundColor: 'rgba(16, 185, 129, 0.2)',
borderColor: 'var(--success, #059669)', pointBackgroundColor: 'var(--success, #059669)', borderWidth: 2
}]
},
options: { responsive: true, maintainAspectRatio: false, scales: { r: { min: 0, max: 100, ticks: { stepSize: 25 } } } }
});

const badge = document.getElementById('compBadge');
badge.textContent = avg >= 60 ? 'High Mastery' : (avg >= 40 ? 'Developing' : 'Needs Support');
badge.className = `badge ${avg >= 60 ? 'badge-success' : (avg >= 40 ? 'badge-warning' : 'badge-danger')}`;
}

// 7. Render Class Heatmap
function renderClassHeatmap(heatmapData) {
const grid = document.getElementById('heatmapGrid');
if (!Array.isArray(heatmapData) || heatmapData.length === 0) {
grid.innerHTML = '<p style="color:var(--muted);grid-column:1/-1;text-align:center">No competency data available yet.</p>';
return;
}
grid.innerHTML = heatmapData.map(h => {
const pct = h.mastery_pct || 0;
let bg = '#f1f5f9', color = '#64748b';
if (pct >= 75) { bg = 'var(--success-light, #d1fae5)'; color = '#065f46'; }
else if (pct >= 50) { bg = 'var(--primary-light, #dbeafe)'; color = '#1e40af'; }
else if (pct >= 25) { bg = 'var(--warning-light, #fef3c7)'; color = '#92400e'; }
else { bg = 'var(--danger-light, #fee2e2)'; color = '#7f1d1d'; }

return `<div class="heatmap-tile" style="background:${bg}; color:${color}">
<div class="label">${h.competency_focus.replace(/_/g,' ')}</div>
<div class="pct">${Math.round(pct)}%</div>
</div>`;
}).join('');
}

// Initial Load
loadClassAnalytics();
</script>
</body>
</html>