<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$pdo = getDBConnection();
$schoolId = getAuthenticatedSchoolId();

// Fetch classes with teachers and prefects
$classes = $pdo->query("
    SELECT c.id, c.name, c.grade_level, 
           tu.name as class_teacher_name,
           p.name as prefect_name
    FROM classes c
    LEFT JOIN teachers ct ON c.class_teacher_id = ct.id
    LEFT JOIN users tu ON ct.user_id = tu.id
    LEFT JOIN students p ON c.prefect_id = p.id
    WHERE c.school_id = $schoolId 
    ORDER BY c.grade_level, c.name
")->fetchAll();

// Get stats
$totalStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE school_id = $schoolId")->fetch()['count'];
$activeStudents = $pdo->query("SELECT COUNT(*) as count FROM students WHERE school_id = $schoolId AND status = 'active'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Students | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626;--warning:#d97706}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:20px}
.container{max-width:1200px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.header h1{font-size:1.8rem;font-weight:700;color:var(--text)}
.header-subtitle{color:var(--muted);margin-top:4px;font-size:0.95rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);display:flex;flex-direction:column;justify-content:space-between}
.stat-card.active{background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%)}
.stat-card.total{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%)}
.stat-card-value{font-size:2.5rem;font-weight:700}
.stat-card-label{font-size:0.9rem;opacity:0.9;margin-top:8px}
.controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.search-box{padding:10px 14px;border:1px solid var(--border);border-radius:8px;width:100%;max-width:350px;font-size:0.95rem}
.search-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.btn{padding:10px 16px;border-radius:6px;border:none;cursor:pointer;font-size:0.9rem;font-weight:500;transition:all 0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#1d4ed8;transform:translateY(-2px);box-shadow:0 4px 12px rgba(30,64,175,0.3)}
.btn-outline{background:#fff;border:1px solid var(--border);color:var(--text)}
.btn-outline:hover{background:#f1f5f9}
.table-container{background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);margin-top:16px}
table{width:100%;border-collapse:collapse}
th,td{padding:14px 16px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f1f5f9;font-weight:600;color:var(--muted);border-bottom:2px solid var(--border)}
tbody tr:hover{background:#f8fafc}
tbody tr:last-child td{border-bottom:none}
.badge{padding:5px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;display:inline-block}
.badge-active{background:#d1fae5;color:#059669}
.badge-transferred{background:#fee2e2;color:#dc2626}
.badge-graduated{background:#dbeafe;color:#1e40af}
.btn-sm{padding:6px 10px;font-size:0.8rem}
.btn-action{background:#f1f5f9;color:var(--text);border:1px solid var(--border)}
.btn-action:hover{background:#e2e8f0}
.btn-success{background:var(--success);color:#fff}
.btn-success:hover{background:#047857}
.btn-warning{background:var(--warning);color:#000}
.btn-warning:hover{background:#b45309}
.btn-danger{background:var(--danger);color:#fff}
.btn-danger:hover{background:#b91c1c}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1000}
.modal-content{background:var(--card);padding:28px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px rgba(0,0,0,0.15)}
.modal-content h2{margin-bottom:20px;font-size:1.3rem;color:var(--text)}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px;font-weight:500}
.form-group input,.form-group select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.95rem}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn-group{display:flex;gap:10px;margin-top:20px}
.btn-group button{flex:1}
.class-info{background:#f8fafc;padding:12px;border-radius:8px;margin-bottom:16px}
.class-info-item{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border)}
.class-info-item:last-child{border-bottom:none}
.class-info-label{font-size:0.85rem;color:var(--muted);font-weight:500}
.class-info-value{font-size:0.9rem;color:var(--text);font-weight:600}
.action-buttons{display:flex;gap:6px;flex-wrap:wrap}
.empty-state{text-align:center;padding:40px 20px;color:var(--muted)}
.empty-state-icon{font-size:3rem;margin-bottom:12px}
@media(max-width:768px){.form-row{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start}.stats-grid{grid-template-columns:1fr}.controls{width:100%;flex-direction:column}.search-box{max-width:100%}}
</style>
</head>
<body>
<div class="container">
<div class="header">
<div>
<h1>🎓 Manage Students</h1>
<p class="header-subtitle">View, add, and manage student records</p>
</div>
<a href="/multi/views/admin/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
</div>

<div class="stats-grid">
<div class="stat-card total">
<div class="stat-card-value"><?= $totalStudents ?></div>
<div class="stat-card-label">Total Students</div>
</div>
<div class="stat-card active">
<div class="stat-card-value"><?= $activeStudents ?></div>
<div class="stat-card-label">Active Students</div>
</div>
</div>

<div class="controls">
<input type="text" id="search" class="search-box" placeholder="🔍 Search students by name or admission number...">
<button onclick="openImportModal()" class="btn btn-outline">📥 Import CSV</button>
<button onclick="openAddModal()" class="btn btn-primary">+ Add Student</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Name</th>
<th>Admission No</th>
<th>Class</th>
<th>Class Teacher</th>
<th>Gender</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="rows">
<tr><td colspan="7" style="text-align:center;padding:40px;color:var(--muted)">Loading students...</td></tr>
</tbody>
</table>
</div>
</div>

<!-- Add Student Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2>➕ Add New Student</h2>
<form id="addForm">
<div class="form-group">
<label>Full Name *</label>
<input name="name" required placeholder="e.g. John Kamau">
</div>
<div class="form-group">
<label>Admission Number *</label>
<input name="admission_number" required placeholder="e.g. ADM/2026/001">
</div>
<div class="form-group">
<label>Class *</label>
<select name="class_id" required>
<option value="">Select Class</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-row">
<div class="form-group">
<label>Gender *</label>
<select name="gender" required>
<option value="">Select</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>
</div>
<div class="form-group">
<label>Date of Birth</label>
<input type="date" name="dob">
</div>
</div>
<div class="btn-group">
<button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
<button type="submit" class="btn btn-primary">Create Student</button>
</div>
</form>
</div>
</div>

<!-- View Student Details Modal -->
<div class="modal" id="viewModal">
<div class="modal-content">
<h2>📋 Student Details</h2>
<div id="viewContent"></div>
<div class="btn-group">
<button type="button" class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
</div>
</div>
</div>

<!-- Edit Student Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<h2>✏️ Edit Student</h2>
<form id="editForm">
<input type="hidden" name="id" id="editStudentId">
<div class="form-group">
<label>Full Name *</label>
<input name="name" id="editName" required>
</div>
<div class="form-group">
<label>Admission Number *</label>
<input name="admission_number" id="editAdmissionNumber" required>
</div>
<div class="form-group">
<label>Class *</label>
<select name="class_id" id="editClassId" required>
<option value="">Select Class</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-row">
<div class="form-group">
<label>Gender *</label>
<select name="gender" id="editGender" required>
<option value="">Select</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>
</div>
<div class="form-group">
<label>Date of Birth</label>
<input type="date" name="dob" id="editDob">
</div>
</div>
<div class="btn-group">
<button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>

<!-- Update Status Modal -->
<div class="modal" id="statusModal">
<div class="modal-content">
<h2 id="statusModalTitle">🔄 Update Student Status</h2>
<form id="statusForm">
<input type="hidden" name="student_id" id="statusStudentId">
<input type="hidden" name="action_type" id="actionType">

<div id="transferFields" style="display:none">
<div class="form-group">
<label>Transfer Reason</label>
<select name="transfer_reason">
<option>Family Relocation</option>
<option>School Transfer</option>
<option>Withdrawn</option>
<option>Other</option>
</select>
</div>
<div class="form-group">
<label>Transfer Date</label>
<input type="date" name="transfer_date" value="<?= date('Y-m-d') ?>">
</div>
<div class="form-group">
<label>Remarks</label>
<textarea name="remarks" rows="3" placeholder="Additional notes..."></textarea>
</div>
</div>

<div id="graduationFields" style="display:none">
<div class="form-group">
<label>Promote To Class *</label>
<select name="new_class_id" id="newClassId">
<option value="">Select Next Class</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Promotion Date</label>
<input type="date" name="promotion_date" value="<?= date('Y-m-d') ?>">
</div>
</div>

<div id="repeatFields" style="display:none">
<div class="form-group">
<label>Repeat Same Class</label>
<select name="repeat_class_id" id="repeatClassId">
<option value="">Select Class</option>
<?php foreach($classes as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group">
<label>Reason for Repeating</label>
<select name="repeat_reason">
<option>Academic Performance</option>
<option>Attendance Issues</option>
<option>Medical Reasons</option>
<option>Personal Reasons</option>
<option>Other</option>
</select>
</div>
<div class="form-group">
<label>Remarks</label>
<textarea name="remarks" rows="3"></textarea>
</div>
</div>

<div class="btn-group">
<button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
<button type="submit" class="btn btn-primary" id="statusSubmitBtn">Confirm</button>
</div>
</form>
</div>
</div>

<script>
// Load students with class information
async function loadStudents() {
const res = await fetch('/multi/api/student/crud.php?action=list');
const data = await res.json();
const tbody = document.getElementById('rows');
tbody.innerHTML = '';

if (data.length === 0) {
tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted)">No students found</td></tr>';
return;
}

data.forEach(s => {
const statusClass = s.status === 'active' ? 'badge-active' : (s.status === 'transferred' ? 'badge-transferred' : 'badge-graduated');
const statusText = s.status.charAt(0).toUpperCase() + s.status.slice(1);

tbody.innerHTML += `
<tr>
<td><strong>${s.name}</strong></td>
<td>${s.admission_number}</td>
<td>${s.class_name || '-'}</td>
<td>${s.class_teacher_name || '-'}</td>
<td>${s.gender || '-'}</td>
<td><span class="badge ${statusClass}">${statusText}</span></td>
<td class="action-buttons">
<button class="btn btn-sm btn-outline" onclick="viewStudent(${s.id})">👁️ View</button>
<button class="btn btn-sm btn-outline" onclick="openEditStudentModal(${s.id})">✏️ Edit</button>
${s.status === 'active' ? `
<button class="btn btn-sm btn-success" onclick="openStatusModal('graduate', ${s.id}, '${s.name.replace(/'/g, "\\'")}')">🎓 Graduate</button>
<button class="btn btn-sm btn-warning" onclick="openStatusModal('transfer', ${s.id}, '${s.name.replace(/'/g, "\\'")}')">📤 Transfer</button>
<button class="btn btn-sm btn-outline" onclick="openStatusModal('repeat', ${s.id}, '${s.name.replace(/'/g, "\\'")}')">🔁 Repeat</button>
` : ''}
</td>
</tr>
`;
});
}

// Open Add Modal
function openAddModal() {
document.getElementById('addModal').style.display = 'flex';
}

// Open Import Modal (placeholder)
function openImportModal() {
alert('Import functionality will be available soon. Please use the API endpoint: /multi/api/student/import.php');
}

// Close Modal
function closeModal(modalId) {
document.getElementById(modalId).style.display = 'none';
}

// View Student Details
async function viewStudent(id) {
try {
const res = await fetch(`/multi/api/student/crud.php?action=view&id=${id}`);
const s = await res.json();

const classInfo = s.class_name ? `
<div class="class-info">
<div class="class-info-item">
<span class="class-info-label">Current Class:</span>
<span class="class-info-value">${s.class_name}</span>
</div>
<div class="class-info-item">
<span class="class-info-label">Class Teacher:</span>
<span class="class-info-value">${s.class_teacher_name || 'Not Assigned'}</span>
</div>
<div class="class-info-item">
<span class="class-info-label">Class Prefect:</span>
<span class="class-info-value">${s.class_prefect_name || 'None'}</span>
</div>
</div>
` : '';

document.getElementById('viewContent').innerHTML = `
<div style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:16px">
<p style="margin:8px 0"><strong>Name:</strong> ${s.name}</p>
<p style="margin:8px 0"><strong>Admission Number:</strong> ${s.admission_number}</p>
<p style="margin:8px 0"><strong>Gender:</strong> ${s.gender || 'Not specified'}</p>
<p style="margin:8px 0"><strong>Date of Birth:</strong> ${s.dob || 'Not specified'}</p>
<p style="margin:8px 0"><strong>Status:</strong> <span class="badge ${s.status === 'active' ? 'badge-active' : 'badge-transferred'}">${s.status}</span></p>
<p style="margin:8px 0"><strong>Date Added:</strong> ${s.created_at ? new Date(s.created_at).toLocaleDateString() : 'N/A'}</p>
</div>
${classInfo}
`;
document.getElementById('viewModal').style.display = 'flex';
} catch (e) {
alert('Failed to load student details');
}
}

// Open Status Modal
function openStatusModal(action, id, name) {
document.getElementById('statusStudentId').value = id;
document.getElementById('actionType').value = action;
document.getElementById('statusModalTitle').textContent = 
action === 'graduate' ? `🎓 Graduate/Promote: ${name}` :
action === 'transfer' ? `📤 Transfer Out: ${name}` :
`🔁 Repeat Grade: ${name}`;

// Show/hide relevant fields
document.getElementById('transferFields').style.display = action === 'transfer' ? 'block' : 'none';
document.getElementById('graduationFields').style.display = action === 'graduate' ? 'block' : 'none';
document.getElementById('repeatFields').style.display = action === 'repeat' ? 'block' : 'none';

// Set button text
document.getElementById('statusSubmitBtn').textContent = 
action === 'graduate' ? 'Promote Student' :
action === 'transfer' ? 'Transfer Student' :
'Repeat Grade';

document.getElementById('statusModal').style.display = 'flex';
}

// Add Student Form Submit
document.getElementById('addForm').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Creating...';

try {
const res = await fetch('/multi/api/student/crud.php?action=create', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(new FormData(e.target)))
});
const result = await res.json();
if (result.success) {
closeModal('addModal');
loadStudents();
alert('✅ Student added successfully');
e.target.reset();
} else {
alert('❌ ' + (result.error || 'Failed to add student'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Create Student';
}
});

async function openEditStudentModal(id) {
try {
const res = await fetch(`/multi/api/student/crud.php?action=view&id=${id}`);
const s = await res.json();
if (!s || !s.id) {
alert('Student not found');
return;
}

document.getElementById('editStudentId').value = s.id;
document.getElementById('editName').value = s.name || '';
document.getElementById('editAdmissionNumber').value = s.admission_number || '';
document.getElementById('editClassId').value = s.class_id || '';
document.getElementById('editGender').value = s.gender || '';
document.getElementById('editDob').value = s.dob || '';
document.getElementById('editModal').style.display = 'flex';
} catch (err) {
alert('Failed to load student details');
}
}

document.getElementById('editForm').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Saving...';

try {
const res = await fetch('/multi/api/student/crud.php?action=update', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(new FormData(e.target)))
});
const result = await res.json();
if (result.success) {
closeModal('editModal');
loadStudents();
alert('✅ Student updated successfully');
} else {
alert('❌ ' + (result.error || 'Failed to update student'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Save Changes';
}
});

// Status Form Submit
document.getElementById('statusForm').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Processing...';

const formData = new FormData(e.target);
const action = formData.get('action_type');
const studentId = formData.get('student_id');

try {
const res = await fetch('/multi/api/student/crud.php?action=update_status', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(formData))
});
const result = await res.json();

if (result.success) {
closeModal('statusModal');
loadStudents();
const actionText = action === 'graduate' ? 'graduated' : action === 'transfer' ? 'transferred' : 'repeated';
alert(`✅ Student ${actionText} successfully`);
e.target.reset();
} else {
alert('❌ ' + (result.error || `Failed to ${action} student`));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Confirm';
}
});

// Search functionality
document.getElementById('search').addEventListener('input', e => {
const term = e.target.value.toLowerCase();
document.querySelectorAll('#rows tr').forEach(tr => {
const text = tr.textContent.toLowerCase();
tr.style.display = text.includes(term) ? '' : 'none';
});
});

// Close modals on outside click
window.onclick = function(event) {
if (event.target.classList.contains('modal')) {
event.target.style.display = 'none';
}
}

// Initial load
loadStudents();
</script>
</body>
</html>