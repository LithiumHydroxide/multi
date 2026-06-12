<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Get school type
$school = $pdo->query("SELECT school_type, name FROM schools WHERE id = $schoolId")->fetch();

// Get houses for dropdown
$houses = $pdo->query("SELECT id, name, code, color FROM houses WHERE school_id = $schoolId ORDER BY name")->fetchAll();

// Get classes for stream assignment
$classes = $pdo->query("SELECT id, name, stream_code FROM classes WHERE school_id = $schoolId ORDER BY grade_level, name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Streams & Houses | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1200px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(350px,1fr));gap:20px;margin-bottom:24px}
@media(max-width:900px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
h2{margin-bottom:16px;font-size:1.2rem;color:var(--primary)}
h3{margin:16px 0 12px;font-size:1rem;color:var(--muted)}
.form-group{margin-bottom:14px}
label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px;font-weight:500}
select,input{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.95rem}
select:focus,input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.btn{padding:10px 16px;border-radius:6px;border:none;cursor:pointer;font-weight:600;transition:0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#1d4ed8}
.btn-success{background:var(--success);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
.btn-sm{padding:6px 12px;font-size:0.85rem}
.badge{padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:600}
.badge-day{background:#dbeafe;color:#1e40af}
.badge-boarding{background:#fef3c7;color:#d97706}
.badge-mixed{background:#d1fae5;color:#059669}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:10px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f8fafc;color:var(--muted);font-weight:600}
.alert{padding:12px;border-radius:6px;margin-bottom:16px}
.alert-info{background:#dbeafe;color:#1e40af;border-left:4px solid var(--primary)}
.color-preview{width:30px;height:30px;border-radius:6px;display:inline-block;vertical-align:middle;margin-left:8px}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}
.modal-content{background:var(--card);padding:24px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto}
.tab{padding:10px 20px;background:#f1f5f9;border:none;cursor:pointer;font-weight:500;border-radius:6px 6px 0 0;margin-right:4px}
.tab.active{background:var(--primary);color:#fff}
.tab-content{display:none}
.tab-content.active{display:block}
.house-color{width:20px;height:20px;border-radius:50%;display:inline-block;vertical-align:middle;margin-right:6px}
.points-input{width:80px;padding:6px;border:1px solid var(--border);border-radius:4px}
</style>
</head>
<body>
<div class="container">
<div class="header">
<div>
<h1>🏫 Streams & Houses Management</h1>
<p style="color:var(--muted);margin-top:4px">Configure class streams, boarding houses, and house points</p>
</div>
<a href="/multi/views/admin/dashboard.php" class="btn" style="background:#f1f5f9;color:var(--text);text-decoration:none">← Back</a>
</div>

<div class="alert alert-info">
<strong>Current School Type:</strong> 
<span class="badge badge-<?= $school['school_type'] ?>"><?= ucfirst($school['school_type']) ?></span>
<?= $school['school_type'] === 'day' ? '(Day School - No boarding)' : ($school['school_type'] === 'boarding' ? '(Boarding School)' : '(Mixed - Day & Boarding)') ?>
</div>

<!-- Tabs -->
<div style="margin-bottom:20px">
<button class="tab active" onclick="switchTab('streams')">📚 Streams</button>
<button class="tab" onclick="switchTab('houses')">🏠 Houses</button>
<button class="tab" onclick="switchTab('students')">👥 Assign Students</button>
<button class="tab" onclick="switchTab('points')">🏆 House Points</button>
</div>

<!-- Streams Tab -->
<div id="streamsTab" class="tab-content active">
<div class="grid">
<div class="card">
<h2>📚 Class Streams</h2>
<p style="color:var(--muted);font-size:0.85rem;margin-bottom:12px">Assign streams to classes (e.g., East, West, North, South)</p>
<form id="streamForm">
<div class="form-group">
<label>Class</label>
<select name="class_id" required>
<option value="">Select Class</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>" <?= $c['stream_code'] ? 'disabled' : '' ?>><?= htmlspecialchars($c['name']) ?> <?= $c['stream_code'] ? '(Already assigned: '.$c['stream_code'].')' : '' ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Stream Code</label>
<input name="stream_code" placeholder="e.g., EAST, WEST, NORTH, SOUTH" required style="text-transform:uppercase">
</div>
<button type="submit" class="btn btn-primary">Assign Stream</button>
</form>
</div>

<div class="card">
<h2>📊 Active Streams</h2>
<table id="streamsTable">
<thead><tr><th>Class</th><th>Stream</th><th>Actions</th></tr></thead>
<tbody><tr><td colspan="3" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>
</div>
</div>

<!-- Houses Tab -->
<div id="housesTab" class="tab-content">
<?php if ($school['school_type'] !== 'day'): ?>
<div class="card" id="housesSection">
<h2>🏠 Boarding & Sports Houses</h2>
<p style="color:var(--muted);font-size:0.85rem;margin-bottom:16px">Houses for boarding organization and sports competitions</p>
<div style="display:flex;gap:12px;margin-bottom:16px">
<button onclick="openHouseModal()" class="btn btn-primary">+ Add House</button>
</div>
<h3>Active Houses</h3>
<table id="housesTable">
<thead><tr><th>House</th><th>Code</th><th>Type</th><th>Color</th><th>Patron</th><th>Students</th><th>Actions</th></tr></thead>
<tbody><tr><td colspan="7" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>
<?php else: ?>
<div class="alert alert-info">Houses are only available for boarding and mixed schools. Change school type to enable this feature.</div>
<?php endif; ?>
</div>

<!-- Assign Students Tab -->
<div id="studentsTab" class="tab-content">
<div class="card">
<h2>👥 Assign Students to Houses</h2>
<p style="color:var(--muted);font-size:0.85rem;margin-bottom:16px">Bulk assign students to boarding houses</p>
<form id="assignStudentsForm">
<div class="form-group">
<label>Select House</label>
<select name="house_id" required>
<option value="">Select House</option>
<?php foreach($houses as $h): ?>
<option value="<?= $h['id'] ?>" style="background:<?= $h['color'] ?>20"><?= htmlspecialchars($h['name']) ?> (<?= $h['code'] ?>)</option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Filter by Class (Optional)</label>
<select name="class_filter" id="classFilter">
<option value="">All Classes</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div id="studentsList" style="margin:16px 0;max-height:300px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;padding:12px">
<p style="color:var(--muted);text-align:center">Select a class filter to load students</p>
</div>
<button type="submit" class="btn btn-primary" <?= empty($houses) ? 'disabled' : '' ?>>💾 Assign Selected Students</button>
</form>
</div>
</div>

<!-- House Points Tab -->
<div id="pointsTab" class="tab-content">
<div class="grid">
<div class="card">
<h2>🏆 Award House Points</h2>
<p style="color:var(--muted);font-size:0.85rem;margin-bottom:16px">Award points for sports, academics, or behavior</p>
<form id="pointsForm">
<div class="form-group">
<label>House</label>
<select name="house_id" required>
<option value="">Select House</option>
<?php foreach($houses as $h): ?>
<option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Points</label>
<input type="number" name="points" placeholder="e.g., 10" required min="-100" max="100">
</div>
<div class="form-group">
<label>Reason/Event</label>
<select name="event_name" required>
<option value="">Select Event</option>
<option>Sports Day</option>
<option>Academic Competition</option>
<option>Best Attendance</option>
<option>Best Behavior</option>
<option>Cleanliness</option>
<option>Other</option>
</select>
</div>
<div class="form-group">
<label>Additional Notes</label>
<textarea name="reason" rows="2" placeholder="Optional details..."></textarea>
</div>
<button type="submit" class="btn btn-success">🏆 Award Points</button>
</form>
</div>

<div class="card">
<h2>📊 House Points Standings</h2>
<table id="pointsTable">
<thead><tr><th>House</th><th>Total Points</th><th>Entries</th></tr></thead>
<tbody><tr><td colspan="3" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>
</div>
</div>

</div>

<!-- House Modal -->
<div class="modal" id="houseModal">
<div class="modal-content">
<h2 id="houseModalTitle">Add House</h2>
<form id="houseForm">
<input type="hidden" name="house_id" id="houseId">
<div class="form-group">
<label>House Name *</label>
<input name="name" id="houseName" required placeholder="e.g., Kenya House">
</div>
<div class="form-group">
<label>House Code *</label>
<input name="code" id="houseCode" required placeholder="e.g., KEN" style="text-transform:uppercase">
</div>
<div class="form-group">
<label>House Color</label>
<input type="color" name="color" id="houseColor" value="#3b82f6">
<span class="color-preview" id="colorPreview" style="background:#3b82f6"></span>
</div>
<div class="form-group">
<label>House Type</label>
<select name="type" id="houseType">
<option value="both">Both Boarding & Sports</option>
<option value="boarding">Boarding Only</option>
<option value="sports">Sports Only</option>
</select>
</div>
<div class="form-group">
<label>Capacity (Optional)</label>
<input type="number" name="capacity" placeholder="e.g., 50">
</div>
<div class="form-group">
<label>House Patron/Matron</label>
<input name="patron_name" placeholder="e.g., Mrs. Kamau">
</div>
<div style="display:flex;gap:10px;margin-top:20px">
<button type="submit" class="btn btn-primary" style="flex:1">Save House</button>
<button type="button" onclick="closeHouseModal()" class="btn" style="flex:1;background:#f1f5f9">Cancel</button>
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
if(tab === 'streams') loadStreams();
if(tab === 'houses') loadHouses();
if(tab === 'students') loadStudentsForAssignment();
if(tab === 'points') loadHousePoints();
}

// School Type Update
document.getElementById('schoolTypeForm')?.addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=update_school_type', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(formData))
});
const result = await res.json();
if(result.success) {
alert('✅ School type updated. Refreshing...');
location.reload();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
});

// Stream Assignment
document.getElementById('streamForm')?.addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=assign_stream', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(formData))
});
const result = await res.json();
if(result.success) {
alert('✅ Stream assigned successfully');
e.target.reset();
loadStreams();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
});

// Load Streams
async function loadStreams() {
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=list_streams');
const data = await res.json();
const tbody = document.querySelector('#streamsTable tbody');
if(data.length === 0) {
tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--muted)">No streams assigned yet</td></tr>';
return;
}
tbody.innerHTML = data.map(s => `
<tr>
<td><strong>${s.class_name}</strong></td>
<td><span style="background:#dbeafe;color:#1e40af;padding:4px 8px;border-radius:4px;font-weight:600">${s.stream_code}</span></td>
<td><button class="btn btn-sm btn-danger" onclick="removeStream(${s.class_id})">Remove</button></td>
</tr>
`).join('');
} catch(err) {
console.error('Failed to load streams:', err);
}
}

// Remove Stream
async function removeStream(classId) {
if(!confirm('Remove stream assignment from this class?')) return;
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=remove_stream', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({class_id: classId})
});
const result = await res.json();
if(result.success) {
loadStreams();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
}

// House Modal Functions
function openHouseModal(id = null) {
document.getElementById('houseModal').style.display = 'flex';
document.getElementById('houseModalTitle').textContent = id ? 'Edit House' : 'Add House';
document.getElementById('houseId').value = id || '';
if(!id) {
document.getElementById('houseForm').reset();
document.getElementById('houseColor').value = '#3b82f6';
document.getElementById('colorPreview').style.background = '#3b82f6';
}
}

function closeHouseModal() {
document.getElementById('houseModal').style.display = 'none';
}

// Color preview
document.getElementById('houseColor')?.addEventListener('input', function() {
document.getElementById('colorPreview').style.background = this.value;
});

// House Form Submit
document.getElementById('houseForm')?.addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
const data = Object.fromEntries(formData);
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=save_house', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(data)
});
const result = await res.json();
if(result.success) {
closeHouseModal();
loadHouses();
alert('✅ House saved successfully');
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
});

// Load Houses
async function loadHouses() {
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=list_houses');
const data = await res.json();
const tbody = document.querySelector('#housesTable tbody');
if(data.length === 0) {
tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted)">No houses defined yet</td></tr>';
return;
}
tbody.innerHTML = data.map(h => `
<tr>
<td><strong>${h.name}</strong></td>
<td><span style="background:${h.color}20;color:${h.color};padding:2px 8px;border-radius:4px;font-weight:600">${h.code}</span></td>
<td><span class="badge badge-${h.type==='boarding'?'day':(h.type==='sports'?'boarding':'mixed')}">${h.type}</span></td>
<td><span class="house-color" style="background:${h.color}"></span></td>
<td>${h.patron_name || '-'}</td>
<td>${h.student_count || 0}</td>
<td>
<button class="btn btn-sm" onclick="openHouseModal(${h.id})">Edit</button>
<button class="btn btn-sm btn-danger" onclick="deleteHouse(${h.id})">Delete</button>
</td>
</tr>
`).join('');
} catch(err) {
console.error('Failed to load houses:', err);
}
}

// Delete House
async function deleteHouse(id) {
if(!confirm('Delete this house? Students will be unassigned.')) return;
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=delete_house', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({id})
});
const result = await res.json();
if(result.success) {
loadHouses();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
}

// Load Students for Assignment
async function loadStudentsForAssignment() {
const classFilter = document.getElementById('classFilter')?.value || '';
try {
const res = await fetch(`/multi/api/admin/streams_houses.php?action=list_students&class_id=${classFilter}`);
const data = await res.json();
const container = document.getElementById('studentsList');
if(data.length === 0) {
container.innerHTML = '<p style="color:var(--muted);text-align:center">No students found</p>';
return;
}
container.innerHTML = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">' +
data.map(s => `
<label style="display:flex;align-items:center;gap:8px;padding:8px;background:#f8fafc;border-radius:6px;cursor:pointer">
<input type="checkbox" name="student_ids[]" value="${s.id}" style="margin:0">
<span>${s.name} (${s.admission_number})</span>
</label>
`).join('') + '</div>';
} catch(err) {
console.error('Failed to load students:', err);
}
}

document.getElementById('classFilter')?.addEventListener('change', loadStudentsForAssignment);

// Assign Students to Houses
document.getElementById('assignStudentsForm')?.addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
const studentIds = formData.getAll('student_ids[]');
if(studentIds.length === 0) {
alert('❌ Please select at least one student');
return;
}
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=assign_students', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({
house_id: formData.get('house_id'),
student_ids: studentIds
})
});
const result = await res.json();
if(result.success) {
alert(`✅ Assigned ${result.assigned} students to house`);
e.target.reset();
loadStudentsForAssignment();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
});

// Award House Points
document.getElementById('pointsForm')?.addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=award_points', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(formData))
});
const result = await res.json();
if(result.success) {
alert('✅ Points awarded successfully');
e.target.reset();
loadHousePoints();
} else {
alert('❌ ' + result.error);
}
} catch(err) {
alert('❌ Network error');
}
});

// Load House Points
async function loadHousePoints() {
try {
const res = await fetch('/multi/api/admin/streams_houses.php?action=list_house_points');
const data = await res.json();
const tbody = document.querySelector('#pointsTable tbody');
if(data.length === 0) {
tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--muted)">No points recorded yet</td></tr>';
return;
}
tbody.innerHTML = data.map(h => `
<tr>
<td><span class="house-color" style="background:${h.color}"></span><strong>${h.name}</strong></td>
<td style="font-size:1.1rem;font-weight:700;color:var(--primary)">${h.total_points}</td>
<td>${h.entries}</td>
</tr>
`).join('');
} catch(err) {
console.error('Failed to load points:', err);
}
}

// Initialize
loadStreams();
<?php if ($school['school_type'] !== 'day'): ?>
loadHouses();
<?php endif; ?>
</script>
</body>
</html>