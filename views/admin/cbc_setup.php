<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fetch subjects for this school
$subjects = $pdo->query("SELECT id, name, code FROM subjects WHERE school_id = $schoolId ORDER BY name")->fetchAll();

// Fetch pathways
$pathways = $pdo->query("SELECT * FROM cbc_pathways WHERE school_id = $schoolId ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CBC Setup | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1200px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.tabs{display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--border)}
.tab{padding:12px 20px;background:none;border:none;cursor:pointer;font-weight:500;color:var(--muted);border-bottom:2px solid transparent}
.tab.active{color:var(--primary);border-bottom-color:var(--primary)}
.tab-content{display:none}
.tab-content.active{display:block}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:20px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.form-group{margin-bottom:16px}
label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px;font-weight:500}
select,input,textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.95rem}
.btn{padding:10px 16px;border-radius:6px;border:none;cursor:pointer;font-weight:600;transition:0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-danger{background:#dc2626;color:#fff}
.btn-sm{padding:6px 12px;font-size:0.85rem}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:10px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f8fafc;color:var(--muted);font-weight:600}
.badge{padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:600}
.badge-7{background:#dbeafe;color:#1e40af}
.badge-8{background:#fef3c7;color:#d97706}
.badge-9{background:#d1fae5;color:#059669}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}
.modal-content{background:var(--card);padding:24px;border-radius:12px;width:90%;max-width:500px}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>📚 CBC Academic Setup</h1>
<a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:var(--text);font-weight:500">← Back</a>
</div>

<div class="tabs">
<button class="tab active" onclick="switchTab('strands')">🔗 CBC Strands</button>
<button class="tab" onclick="switchTab('pathways')">🛤️ Pathways</button>
<button class="tab" onclick="switchTab('grading')">📊 Grading Rules</button>
</div>

<!-- Strands Tab -->
<div id="strandsTab" class="tab-content active">
<div class="grid">
<div class="card">
<h2>➕ Add CBC Strand</h2>
<form id="strandForm">
<div class="form-group">
<label>Subject *</label>
<select name="subject_id" required>
<option value="">Select Subject</option>
<?php foreach($subjects as $sub): ?>
<option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?> (<?= $sub['code'] ?>)</option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Strand Name *</label>
<input name="name" placeholder="e.g., Numbers, Geometry, Listening" required>
</div>
<div class="form-group">
<label>Strand Code *</label>
<input name="code" placeholder="e.g., NUM, GEO, LIS" required style="text-transform:uppercase">
</div>
<div class="form-group">
<label>Grade Level *</label>
<select name="grade_level" required>
<option value="7">Grade 7</option>
<option value="8">Grade 8</option>
<option value="9">Grade 9</option>
</select>
</div>
<div class="form-group">
<label>Description</label>
<textarea name="description" rows="2" placeholder="Brief description of this strand"></textarea>
</div>
<button type="submit" class="btn btn-primary">💾 Save Strand</button>
</form>
</div>

<div class="card">
<h2>📋 Existing Strands</h2>
<div class="form-group">
<label>Filter by Subject</label>
<select id="strandSubjectFilter" onchange="loadStrands()">
<option value="">All Subjects</option>
<?php foreach($subjects as $sub): ?>
<option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<table id="strandsTable">
<thead><tr><th>Subject</th><th>Strand</th><th>Code</th><th>Grade</th><th>Actions</th></tr></thead>
<tbody><tr><td colspan="5" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>
</div>
</div>

<!-- Pathways Tab -->
<div id="pathwaysTab" class="tab-content">
<div class="card">
<h2>🛤️ CBC Pathways (Grade 9 Specialization)</h2>
<p style="color:var(--muted);margin-bottom:16px">Pathways allow Grade 9 students to specialize in areas of interest</p>

<div style="display:flex;gap:12px;margin-bottom:16px">
<button onclick="openPathwayModal()" class="btn btn-primary">+ Add Pathway</button>
</div>

<table id="pathwaysTable">
<thead><tr><th>Pathway</th><th>Code</th><th>Description</th><th>Status</th><th>Students</th><th>Actions</th></tr></thead>
<tbody><tr><td colspan="6" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>
</div>

<!-- Grading Rules Tab -->
<div id="gradingTab" class="tab-content">
<div class="card">
<h2>📊 CBC Achievement Levels</h2>
<p style="color:var(--muted);margin-bottom:16px">Configure the percentage ranges for each achievement level</p>

<table>
<thead><tr><th>Level</th><th>Code</th><th>Min %</th><th>Max %</th><th>Description</th></tr></thead>
<tbody>
<tr><td>Exceeding Expectations (High)</td><td class="badge" style="background:#d1fae5;color:#065f46">EE1</td><td><input type="number" value="90" min="0" max="100" style="width:60px"></td><td><input type="number" value="100" min="0" max="100" style="width:60px"></td><td>Outstanding performance</td></tr>
<tr><td>Exceeding Expectations</td><td class="badge" style="background:#a7f3d0;color:#065f46">EE2</td><td><input type="number" value="80" min="0" max="100" style="width:60px"></td><td><input type="number" value="89" min="0" max="100" style="width:60px"></td><td>Excellent performance</td></tr>
<tr><td>Meeting Expectations (High)</td><td class="badge" style="background:#dbeafe;color:#1e40af">ME1</td><td><input type="number" value="70" min="0" max="100" style="width:60px"></td><td><input type="number" value="79" min="0" max="100" style="width:60px"></td><td>Very good performance</td></tr>
<tr><td>Meeting Expectations</td><td class="badge" style="background:#bfdbfe;color:#1e40af">ME2</td><td><input type="number" value="65" min="0" max="100" style="width:60px"></td><td><input type="number" value="69" min="0" max="100" style="width:60px"></td><td>Good performance</td></tr>
<tr><td>Approaching Expectations (High)</td><td class="badge" style="background:#fef3c7;color:#92400e">AE1</td><td><input type="number" value="55" min="0" max="100" style="width:60px"></td><td><input type="number" value="64" min="0" max="100" style="width:60px"></td><td>Satisfactory performance</td></tr>
<tr><td>Approaching Expectations</td><td class="badge" style="background:#fde68a;color:#92400e">AE2</td><td><input type="number" value="50" min="0" max="100" style="width:60px"></td><td><input type="number" value="54" min="0" max="100" style="width:60px"></td><td>Adequate performance</td></tr>
<tr><td>Below Expectations (High)</td><td class="badge" style="background:#fee2e2;color:#7f1d1d">BE1</td><td><input type="number" value="40" min="0" max="100" style="width:60px"></td><td><input type="number" value="49" min="0" max="100" style="width:60px"></td><td>Needs improvement</td></tr>
<tr><td>Below Expectations</td><td class="badge" style="background:#fecaca;color:#7f1d1d">BE2</td><td><input type="number" value="0" min="0" max="100" style="width:60px"></td><td><input type="number" value="39" min="0" max="100" style="width:60px"></td><td>Requires support</td></tr>
</tbody>
</table>
<button class="btn btn-primary" style="margin-top:16px">💾 Save Grading Rules</button>
</div>
</div>

</div>

<!-- Pathway Modal -->
<div class="modal" id="pathwayModal">
<div class="modal-content">
<h2 id="pathwayModalTitle" style="margin-bottom:16px">Add Pathway</h2>
<form id="pathwayForm">
<input type="hidden" name="pathway_id" id="pathwayId">
<div class="form-group">
<label>Pathway Name *</label>
<input name="name" id="pathwayName" required placeholder="e.g., STEM, Arts & Sports">
</div>
<div class="form-group">
<label>Code *</label>
<input name="code" id="pathwayCode" required placeholder="e.g., STEM, ARTS" style="text-transform:uppercase">
</div>
<div class="form-group">
<label>Description</label>
<textarea name="description" id="pathwayDesc" rows="3" placeholder="Brief description of this pathway"></textarea>
</div>
<div style="display:flex;gap:10px;margin-top:20px">
<button type="submit" class="btn btn-primary" style="flex:1">Save Pathway</button>
<button type="button" onclick="closePathwayModal()" class="btn" style="flex:1;background:#f1f5f9">Cancel</button>
</div>
</form>
</div>
</div>

<script>
// Tab Switching
function switchTab(tab) {
document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
event.target.classList.add('active');
document.getElementById(tab + 'Tab').classList.add('active');
if(tab === 'strands') loadStrands();
if(tab === 'pathways') loadPathways();
}

// Load Strands
async function loadStrands() {
const subjectId = document.getElementById('strandSubjectFilter').value;
const url = `/multi/api/admin/cbc_strands.php?action=list${subjectId ? '&subject_id='+subjectId : ''}`;
const res = await fetch(url);
const data = await res.json();
const tbody = document.querySelector('#strandsTable tbody');
if(data.length === 0) {
tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted)">No strands found</td></tr>';
return;
}
tbody.innerHTML = data.map(s => `
<tr>
<td>${s.subject_name}</td>
<td><strong>${s.name}</strong></td>
<td><span style="background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:4px;font-weight:600">${s.code}</span></td>
<td><span class="badge badge-${s.grade_level}">Grade ${s.grade_level}</span></td>
<td>
<button class="btn btn-sm" onclick="editStrand(${s.id}, '${s.name}', '${s.code}', '${s.description.replace(/'/g, "\\'")}')">Edit</button>
<button class="btn btn-sm btn-danger" onclick="deleteStrand(${s.id})">Delete</button>
</td>
</tr>
`).join('');
}

// Strand Form Submit
document.getElementById('strandForm').addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
const data = Object.fromEntries(formData);
const res = await fetch('/multi/api/admin/cbc_strands.php?action=create', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(data)
});
const result = await res.json();
if(result.success) {
alert('✅ Strand saved successfully');
e.target.reset();
loadStrands();
} else {
alert('❌ ' + result.error);
}
});

// Delete Strand
async function deleteStrand(id) {
if(!confirm('Delete this strand?')) return;
const res = await fetch('/multi/api/admin/cbc_strands.php?action=delete', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({id})
});
const result = await res.json();
if(result.success) loadStrands();
else alert('❌ ' + result.error);
}

// Pathway Functions
function openPathwayModal(id = null) {
document.getElementById('pathwayModal').style.display = 'flex';
document.getElementById('pathwayModalTitle').textContent = id ? 'Edit Pathway' : 'Add Pathway';
document.getElementById('pathwayId').value = id || '';
if(!id) {
document.getElementById('pathwayForm').reset();
}
}
function closePathwayModal() {
document.getElementById('pathwayModal').style.display = 'none';
}

// Load Pathways
async function loadPathways() {
const res = await fetch('/multi/api/admin/cbc_strands.php?action=list_pathways');
const data = await res.json();
const tbody = document.querySelector('#pathwaysTable tbody');
if(data.length === 0) {
tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--muted)">No pathways defined</td></tr>';
return;
}
tbody.innerHTML = data.map(p => `
<tr>
<td><strong>${p.name}</strong></td>
<td><span style="background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:4px;font-weight:600">${p.code}</span></td>
<td>${p.description || '-'}</td>
<td><span class="badge" style="background:${p.is_active?'#d1fae5':'#fee2e2'};color:${p.is_active?'#059669':'#dc2626'}">${p.is_active?'Active':'Inactive'}</span></td>
<td>${p.student_count || 0}</td>
<td>
<button class="btn btn-sm" onclick="openPathwayModal(${p.id})">Edit</button>
<button class="btn btn-sm btn-danger" onclick="deletePathway(${p.id})">Delete</button>
</td>
</tr>
`).join('');
}

// Pathway Form Submit
document.getElementById('pathwayForm').addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
const data = Object.fromEntries(formData);
// This would need a save_pathway endpoint - placeholder for now
alert('Pathway save functionality coming soon!');
closePathwayModal();
});

// Initialize
loadStrands();
loadPathways();
</script>
</body>
</html>