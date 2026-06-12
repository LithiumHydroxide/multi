<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); 
$pdo = getDBConnection();
$schoolId = getAuthenticatedSchoolId();
$totalStaff = $pdo->query("SELECT COUNT(*) as count FROM users WHERE school_id = $schoolId AND staff_type = 'non_teaching'")->fetch()['count'];
$activeStaff = $pdo->query("SELECT COUNT(*) as count FROM users WHERE school_id = $schoolId AND staff_type = 'non_teaching' AND status = 'active'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Non-Teaching Staff | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--danger:#dc2626;--success:#059669;--warning:#d97706}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:20px}
.container{max-width:1200px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px}
.header h1{font-size:1.8rem;font-weight:700;color:var(--text)}
.header-subtitle{color:var(--muted);margin-top:4px;font-size:0.95rem}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:linear-gradient(135deg,#f093fb 0%,#f5576c 100%);color:#fff;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.1);display:flex;flex-direction:column;justify-content:space-between}
.stat-card.active{background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%)}
.stat-card.total{background:linear-gradient(135deg,#1e40af 0%,#3b82f6 100%)}
.stat-card-value{font-size:2.5rem;font-weight:700}
.stat-card-label{font-size:0.9rem;opacity:0.9;margin-top:8px}
.controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.search-box{padding:10px 14px;border:1px solid var(--border);border-radius:8px;width:100%;max-width:300px;font-size:0.95rem}
.search-box:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.btn{padding:10px 16px;border-radius:6px;border:none;cursor:pointer;font-size:0.9rem;font-weight:500;transition:all 0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:#1d4ed8;transform:translateY(-2px);box-shadow:0 4px 12px rgba(30,64,175,0.3)}
.btn-outline{background:#fff;border:1px solid var(--border);color:var(--text)}
.btn-outline:hover{background:#f1f5f9}
.btn-sm{padding:8px 12px;font-size:0.85rem}
.table-container{background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06)}
table{width:100%;border-collapse:collapse}
th{background:#f1f5f9;padding:14px 16px;text-align:left;font-weight:600;color:var(--muted);font-size:0.85rem;border-bottom:2px solid var(--border)}
td{padding:14px 16px;border-bottom:1px solid var(--border);font-size:0.9rem}
tbody tr:hover{background:#f8fafc}
tbody tr:last-child td{border-bottom:none}
.badge{padding:5px 12px;border-radius:20px;font-size:0.75rem;font-weight:600;display:inline-block}
.badge-active{background:#d1fae5;color:#059669}
.badge-inactive{background:#fee2e2;color:#dc2626}
.btn-action{background:#f1f5f9;color:var(--text);border:1px solid var(--border);padding:6px 10px;margin-right:4px;font-size:0.8rem}
.btn-action:hover{background:#e2e8f0}
.btn-danger{background:#fee2e2;color:var(--danger);border:1px solid #fecaca}
.btn-danger:hover{background:#fecaca}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:1000}
.modal-content{background:var(--card);padding:28px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 25px rgba(0,0,0,0.15)}
.modal-content h2{margin-bottom:20px;font-size:1.3rem;color:var(--text)}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px;font-weight:500}
.form-group input,.form-group select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.95rem}
.form-group input:focus,.form-group select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.btn-group{display:flex;gap:10px;margin-top:20px}
.btn-group button{flex:1}
.detail-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)}
.detail-row:last-child{border-bottom:none}
.detail-label{font-weight:600;color:var(--muted);font-size:0.9rem}
.detail-value{color:var(--text);font-size:0.9rem}
.empty-state{text-align:center;padding:40px 20px;color:var(--muted)}
.empty-state-icon{font-size:3rem;margin-bottom:12px}
@media(max-width:768px){.stats-grid{grid-template-columns:1fr}.header{flex-direction:column;align-items:flex-start}.controls{width:100%;flex-direction:column}.search-box{max-width:100%}}
</style>
</head>
<body>
<div class="container">
<div class="header">
<div>
<h1>👥 Non-Teaching Staff</h1>
<p class="header-subtitle">Manage support staff and administrative personnel</p>
</div>
<a href="/multi/views/admin/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
</div>

<div class="stats-grid">
<div class="stat-card total">
<div class="stat-card-value"><?= $totalStaff ?></div>
<div class="stat-card-label">Total Staff</div>
</div>
<div class="stat-card active">
<div class="stat-card-value"><?= $activeStaff ?></div>
<div class="stat-card-label">Active Staff</div>
</div>
</div>

<div class="controls">
<input type="text" id="search" class="search-box" placeholder="🔍 Search by name, email, or role...">
<button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary">+ Add Staff</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>Job Role</th>
<th>Details</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="rows">
<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">Loading staff...</td></tr>
</tbody>
</table>
</div>
</div>

<!-- View Details Modal -->
<div class="modal" id="detailsModal">
<div class="modal-content">
<h2>👤 Staff Member Details</h2>
<div id="detailsContent" style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:20px"></div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('detailsModal').style.display='none'" class="btn btn-outline">Close</button>
</div>
</div>
</div>

<!-- Edit Staff Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<h2>✏️ Edit Staff Member</h2>
<form id="editForm">
<input type="hidden" name="id" id="editId">
<div class="form-group">
<label>Full Name *</label>
<input name="name" id="editName" required placeholder="e.g. Mary Koech">
</div>
<div class="form-group">
<label>Email *</label>
<input type="email" name="email" id="editEmail" required placeholder="mary@school.com">
</div>
<div class="form-group">
<label>Job Role *</label>
<select name="job_role" id="editJobRole" onchange="showRoleFields('editJobRole', 'editRoleSpecificFields')" required>
<option value="">Select Role...</option>
<optgroup label="Transport"><option value="Driver">Driver</option><option value="Conductor">Conductor</option></optgroup>
<optgroup label="Health"><option value="School Nurse">School Nurse</option></optgroup>
<optgroup label="Kitchen"><option value="Head Cook">Head Cook</option><option value="Kitchen Assistant">Kitchen Assistant</option></optgroup>
<optgroup label="Maintenance"><option value="Electrician">Electrician</option><option value="Plumber">Plumber</option><option value="Carpenter">Carpenter</option></optgroup>
<optgroup label="Security"><option value="Security Guard">Security Guard</option><option value="Gatekeeper">Gatekeeper</option></optgroup>
<optgroup label="Admin"><option value="Bursar">Bursar</option><option value="Secretary">Secretary</option></optgroup>
</select>
</div>
<div id="editRoleSpecificFields"></div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-outline">Cancel</button>
<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>

<!-- Add Staff Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2>➕ Add Non-Teaching Staff</h2>
<form id="form">
<div class="form-group">
<label>Full Name *</label>
<input name="name" required placeholder="e.g. Mary Koech">
</div>
<div class="form-group">
<label>Email *</label>
<input type="email" name="email" required placeholder="mary@school.com">
</div>
<div class="form-group">
<label>Password (8+ characters) *</label>
<input type="password" name="password" required minlength="8" placeholder="••••••••">
</div>
<div class="form-group">
<label>Job Role *</label>
<select name="job_role" id="jobRole" onchange="showRoleFields()" required>
<option value="">Select Role...</option>
<optgroup label="Transport"><option value="Driver">Driver</option><option value="Conductor">Conductor</option></optgroup>
<optgroup label="Health"><option value="School Nurse">School Nurse</option></optgroup>
<optgroup label="Kitchen"><option value="Head Cook">Head Cook</option><option value="Kitchen Assistant">Kitchen Assistant</option></optgroup>
<optgroup label="Maintenance"><option value="Electrician">Electrician</option><option value="Plumber">Plumber</option><option value="Carpenter">Carpenter</option></optgroup>
<optgroup label="Security"><option value="Security Guard">Security Guard</option><option value="Gatekeeper">Gatekeeper</option></optgroup>
<optgroup label="Admin"><option value="Bursar">Bursar</option><option value="Secretary">Secretary</option></optgroup>
</select>
</div>
<div id="roleSpecificFields"></div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-outline">Cancel</button>
<button type="submit" class="btn btn-primary">Create Staff</button>
</div>
</form>
</div>
</div>

<script>
function showRoleFields(jobRoleId = 'jobRole', containerId = 'roleSpecificFields', details = {}) {
const role = document.getElementById(jobRoleId).value;
const container = document.getElementById(containerId);
container.innerHTML = '';
let html = '';
if (role === 'Driver') html = `
<div class="form-group"><label>Bus Number</label><input name="details[bus_number]"></div>
<div class="form-group"><label>Number Plate</label><input name="details[plate_number]"></div>
<div class="form-group"><label>Route</label><input name="details[route]"></div>
<div class="form-group"><label>License Number *</label><input name="details[license_no]" required></div>`;
else if (role === 'School Nurse') html = `
<div class="form-group"><label>License/Reg Number *</label><input name="details[license_no]" required></div>
<div class="form-group"><label>Certification</label><input name="details[certification]"></div>`;
else if (role === 'Bursar') html = `
<div class="form-group"><label>CPA/KASNEB Certificate</label><input name="details[certification]"></div>
<div class="form-group"><label>Professional Membership</label><input name="details[membership]"></div>`;
else if (role === 'Security Guard') html = `
<div class="form-group"><label>License Number *</label><input name="details[license_no]" required></div>
<div class="form-group"><label>Security Company</label><input name="details[company]"></div>`;
container.innerHTML = html;
for (const [key, value] of Object.entries(details)) {
const input = container.querySelector(`[name="details[${key}]"]`);
if (input) input.value = value;
}
}

async function load(){
const res = await fetch('/multi/api/admin/staff_crud.php?action=list&staff_type=non_teaching');
const d = await res.json();
const tb = document.getElementById('rows');
tb.innerHTML = '';

if (!d || d.length === 0) {
tb.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">👥</div><p>No staff members found. Click "Add Staff" to create one.</p></div></td></tr>';
return;
}

d.forEach(s => {
const statusClass = s.status === 'active' ? 'badge-active' : 'badge-inactive';
const statusBtnText = s.status === 'active' ? '🔇 Disable' : '✓ Enable';
const detailsPreview = s.details && Object.keys(s.details).length > 0 ? Object.entries(s.details).map(([k,v]) => {
const label = k.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
return `<strong>${label}:</strong> ${v}`;
}).join(' • ') : '<span style="color:var(--muted)">No details</span>';

tb.innerHTML += `<tr>
<td><strong>${s.name}</strong></td>
<td>${s.email}</td>
<td>${s.job_role}</td>
<td style="font-size:0.85rem">${detailsPreview}</td>
<td><span class="badge ${statusClass}">${s.status === 'active' ? 'Active' : 'Inactive'}</span></td>
<td>
<button class="btn btn-action" onclick='showDetailsModal(${JSON.stringify(s).replace(/'/g, "\\'")})'>👁️ View</button>
<button class="btn btn-action" onclick='openEditStaffModal(${JSON.stringify(s).replace(/'/g, "\\'")})'>✏️ Edit</button>
<button class="btn btn-action" onclick="tog(${s.id})">${statusBtnText}</button>
<button class="btn btn-action btn-danger" onclick="deleteStaff(${s.id}, '${s.name.replace(/'/g, "\\'")}')">🗑️ Delete</button>
</td>
</tr>`;
});
}

document.getElementById('form').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Creating...';

try {
const fd = new FormData(e.target);
const data = Object.fromEntries(fd);
const details = {};
for (const key in data) {
if (key.startsWith('details[')) details[key.replace('details[','').replace(']','')] = data[key];
}
const payload = {
name: data.name,
email: data.email,
password: data.password,
staff_type: 'non_teaching',
job_role: data.job_role
};
for (const [key, value] of Object.entries(details)) {
payload[`details[${key}]`] = value;
}
const r = await fetch('/multi/api/admin/staff_crud.php?action=create', {
method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
});
const result = await r.json();
if(result.success) {
alert('✅ Staff member created successfully');
document.getElementById('addModal').style.display = 'none';
load();
e.target.reset();
} else {
alert('❌ ' + (result.error || 'Failed to create staff'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Create Staff';
}
});

async function tog(id){
try {
await fetch('/multi/api/admin/staff_crud.php?action=toggle_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:id})});
load();
} catch (e) {
alert('❌ Network error: ' + e.message);
}
}

async function deleteStaff(id, name) {
if(!confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) return;
try {
const r = await fetch('/multi/api/admin/staff_crud.php?action=delete', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({user_id: id})
});
const result = await r.json();
if(result.success) {
alert('✅ Staff member deleted successfully');
load();
} else {
alert('❌ ' + (result.error || 'Failed to delete'));
}
} catch(e) {
alert('❌ Network error: ' + e.message);
}
}

function openEditStaffModal(staff) {
const s = typeof staff === 'string' ? JSON.parse(staff) : staff;
document.getElementById('editId').value = s.id;
document.getElementById('editName').value = s.name;
document.getElementById('editEmail').value = s.email;
document.getElementById('editJobRole').value = s.job_role;
showRoleFields('editJobRole','editRoleSpecificFields', s.details || {});
document.getElementById('editModal').style.display = 'flex';
}

document.getElementById('editForm').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Saving...';

try {
const fd = new FormData(e.target);
const data = Object.fromEntries(fd);
const details = {};
for (const key in data) {
if (key.startsWith('details[')) details[key.replace('details[','').replace(']','')] = data[key];
}
const payload = {
id: data.id,
name: data.name,
email: data.email,
job_role: data.job_role
};
for (const [key, value] of Object.entries(details)) {
payload[`details[${key}]`] = value;
}
const r = await fetch('/multi/api/admin/staff_crud.php?action=update', {
method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
});
const result = await r.json();
if (result.success) {
alert('✅ Staff member updated successfully');
document.getElementById('editModal').style.display = 'none';
load();
} else {
alert('❌ ' + (result.error || 'Failed to update staff'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Save Changes';
}
});

function showDetailsModal(staff) {
const s = typeof staff === 'string' ? JSON.parse(staff) : staff;
let detailsHtml = `
<div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value"><strong>${s.name}</strong></span></div>
<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${s.email}</span></div>
<div class="detail-row"><span class="detail-label">Job Role:</span><span class="detail-value">${s.job_role}</span></div>
<div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value"><span class="badge ${s.status==='active'?'badge-active':'badge-inactive'}">${s.status === 'active' ? 'Active' : 'Inactive'}</span></span></div>`;

if (s.details && Object.keys(s.details).length > 0) {
detailsHtml += `<div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border)"><h4 style="margin:8px 0;color:var(--primary);font-size:0.9rem">Additional Details:</h4>`;
for (const [key, value] of Object.entries(s.details)) {
const label = key.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
detailsHtml += `<div class="detail-row"><span class="detail-label">${label}:</span><span class="detail-value">${value}</span></div>`;
}
detailsHtml += `</div>`;
} else {
detailsHtml += `<div style="margin-top:12px;padding:12px;background:#fef3c7;border-radius:6px;text-align:center;color:#92400e;font-size:0.85rem">⚠️ No additional details stored</div>`;
}

document.getElementById('detailsContent').innerHTML = detailsHtml;
document.getElementById('detailsModal').style.display='flex';
}

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

load();
</script>
</body>
</html>

<!-- View Details Modal -->
<div class="modal" id="detailsModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Staff Member Details</h2>
<div id="detailsContent"></div>
<div style="display:flex;gap:10px;margin-top:16px">
<button type="button" onclick="document.getElementById('detailsModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Close</button>
</div>
</div>
</div>

<!-- Edit Staff Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Edit Non-Teaching Staff</h2>
<form id="editForm">
<input type="hidden" name="id" id="editId">
<div class="form-group"><label>Full Name</label><input name="name" id="editName" required></div>
<div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail" required></div>
<div class="form-group">
<label>Job Role *</label>
<select name="job_role" id="editJobRole" onchange="showRoleFields('editJobRole', 'editRoleSpecificFields')">
<option value="">Select Role...</option>
<optgroup label="Transport"><option value="Driver">Driver</option><option value="Conductor">Conductor</option></optgroup>
<optgroup label="Health"><option value="School Nurse">School Nurse</option></optgroup>
<optgroup label="Kitchen"><option value="Head Cook">Head Cook</option><option value="Kitchen Assistant">Kitchen Assistant</option></optgroup>
<optgroup label="Maintenance"><option value="Electrician">Electrician</option><option value="Plumber">Plumber</option><option value="Carpenter">Carpenter</option></optgroup>
<optgroup label="Security"><option value="Security Guard">Security Guard</option><option value="Gatekeeper">Gatekeeper</option></optgroup>
<optgroup label="Admin"><option value="Bursar">Bursar</option><option value="Secretary">Secretary</option></optgroup>
</select>
</div>
<div id="editRoleSpecificFields"></div>
<div style="display:flex;gap:10px;margin-top:16px">
<button type="button" onclick="document.getElementById('editModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
<button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Save Changes</button>
</div>
</form>
</div>
</div>

<!-- Add Staff Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Add Non-Teaching Staff</h2>
<form id="form">
<div class="form-group"><label>Full Name</label><input name="name" required></div>
<div class="form-group"><label>Email</label><input type="email" name="email" required></div>
<div class="form-group"><label>Password (8+ chars)</label><input type="password" name="password" required minlength="8"></div>
<div class="form-group">
<label>Job Role *</label>
<select name="job_role" id="jobRole" onchange="showRoleFields()">
<option value="">Select Role...</option>
<optgroup label="Transport"><option value="Driver">Driver</option><option value="Conductor">Conductor</option></optgroup>
<optgroup label="Health"><option value="School Nurse">School Nurse</option></optgroup>
<optgroup label="Kitchen"><option value="Head Cook">Head Cook</option><option value="Kitchen Assistant">Kitchen Assistant</option></optgroup>
<optgroup label="Maintenance"><option value="Electrician">Electrician</option><option value="Plumber">Plumber</option><option value="Carpenter">Carpenter</option></optgroup>
<optgroup label="Security"><option value="Security Guard">Security Guard</option><option value="Gatekeeper">Gatekeeper</option></optgroup>
<optgroup label="Admin"><option value="Bursar">Bursar</option><option value="Secretary">Secretary</option></optgroup>
</select>
</div>
<div id="roleSpecificFields"></div>
<div style="display:flex;gap:10px;margin-top:16px">
<button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Create</button>
<button type="button" onclick="document.getElementById('addModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
</div>
</form>
</div>
</div>

<script>
function showRoleFields(jobRoleId = 'jobRole', containerId = 'roleSpecificFields', details = {}) {
const role = document.getElementById(jobRoleId).value;
const container = document.getElementById(containerId);
container.innerHTML = '';
let html = '';
if (role === 'Driver') html = `<div class="form-group"><label>Bus Number</label><input name="details[bus_number]"></div><div class="form-group"><label>Number Plate</label><input name="details[plate_number]"></div><div class="form-group"><label>Route</label><input name="details[route]"></div><div class="form-group"><label>License No</label><input name="details[license_no]" required></div>`;
else if (role === 'School Nurse') html = `<div class="form-group"><label>License/Reg No</label><input name="details[license_no]" required></div><div class="form-group"><label>Certification</label><input name="details[certification]"></div>`;
else if (role === 'Bursar') html = `<div class="form-group"><label>CPA/KASNEB Cert</label><input name="details[certification]"></div><div class="form-group"><label>Membership</label><input name="details[membership]"></div>`;
else if (role === 'Security Guard') html = `<div class="form-group"><label>License No</label><input name="details[license_no]" required></div><div class="form-group"><label>Company</label><input name="details[company]"></div>`;
container.innerHTML = html;
for (const [key, value] of Object.entries(details)) {
const input = container.querySelector(`[name="details[${key}]"]`);
if (input) input.value = value;
}
}

async function load(){
const res = await fetch('/multi/api/admin/staff_crud.php?action=list&staff_type=non_teaching');
const d = await res.json();
const tb = document.getElementById('rows');
tb.innerHTML = '';
d.forEach(s => {
const detailsPreview = s.details && Object.keys(s.details).length > 0 ? Object.entries(s.details).map(([k,v]) => {
const label = k.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
return `<strong>${label}:</strong> ${v}`;
}).join('<br>') : '<span style="color:var(--muted)">No details</span>';
tb.innerHTML += `<tr>
<td>${s.name}</td><td>${s.email}</td><td>${s.job_role}</td>
<td style="font-size:0.8rem">${detailsPreview}</td>
<td><span class="badge ${s.status==='active'?'badge-active':'badge-inactive'}">${s.status}</span></td>
<td>
<button class="btn" onclick='showDetailsModal(${JSON.stringify(s).replace(/'/g, "\\'")})'>View</button> 
<button class="btn" onclick='openEditStaffModal(${JSON.stringify(s).replace(/'/g, "\\'")})'>Edit</button>
<button class="btn" onclick="tog(${s.id})">${s.status==='active'?'Disable':'Enable'}</button>
<button class="btn btn-danger" onclick="deleteStaff(${s.id})">Delete</button>
</td>
</tr>`;
});
}

document.getElementById('form').addEventListener('submit', async e => {
e.preventDefault();
const fd = new FormData(e.target);
const data = Object.fromEntries(fd);
const details = {};
for (const key in data) {
if (key.startsWith('details[')) details[key.replace('details[','').replace(']','')] = data[key];
}
const payload = {
name: data.name,
email: data.email,
password: data.password,
staff_type: 'non_teaching',
job_role: data.job_role
};
// Merge details directly into payload with details[...] keys
for (const [key, value] of Object.entries(details)) {
payload[`details[${key}]`] = value;
}
const r = await fetch('/multi/api/admin/staff_crud.php?action=create', {
method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
});
const result = await r.json();
if(result.success) location.reload(); else alert('❌ ' + result.error);
});

async function tog(id){
await fetch('/multi/api/admin/staff_crud.php?action=toggle_status',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id:id})});
load();
}

async function deleteStaff(id) {
if(!confirm('Are you sure you want to delete this staff member? This action cannot be undone.')) return;
try {
const r = await fetch('/multi/api/admin/staff_crud.php?action=delete', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({user_id: id})
});
const result = await r.json();
if(result.success) {
alert('✅ Staff member deleted successfully');
load();
} else {
alert('❌ ' + (result.error || 'Failed to delete'));
}
} catch(e) {
alert('❌ Network error: ' + e.message);
}
}

function openEditStaffModal(staff) {
const s = typeof staff === 'string' ? JSON.parse(staff) : staff;
document.getElementById('editId').value = s.id;
document.getElementById('editName').value = s.name;
document.getElementById('editEmail').value = s.email;
document.getElementById('editJobRole').value = s.job_role;
showRoleFields('editJobRole','editRoleSpecificFields', s.details || {});
document.getElementById('editModal').style.display = 'flex';
}

document.getElementById('editForm').addEventListener('submit', async e => {
e.preventDefault();
const fd = new FormData(e.target);
const data = Object.fromEntries(fd);
const details = {};
for (const key in data) {
if (key.startsWith('details[')) details[key.replace('details[','').replace(']','')] = data[key];
}
const payload = {
id: data.id,
name: data.name,
email: data.email,
job_role: data.job_role
};
for (const [key, value] of Object.entries(details)) {
payload[`details[${key}]`] = value;
}
const r = await fetch('/multi/api/admin/staff_crud.php?action=update', {
method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)
});
const result = await r.json();
if (result.success) {
alert('✅ Staff updated successfully');
document.getElementById('editModal').style.display = 'none';
load();
} else {
alert('❌ ' + (result.error || 'Failed to update staff'));
}
});

function showDetailsModal(staff) {
    // Parse the stringified object if needed
    const s = typeof staff === 'string' ? JSON.parse(staff) : staff;
    let detailsHtml = `
    <div style="background:#f1f5f9;padding:16px;border-radius:8px;margin-bottom:16px">
        <div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value">${s.name}</span></div>
        <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${s.email}</span></div>
        <div class="detail-row"><span class="detail-label">Job Role:</span><span class="detail-value">${s.job_role}</span></div>
        <div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value"><span class="badge ${s.status==='active'?'badge-active':'badge-inactive'}">${s.status}</span></span></div>
    `;

    detailsHtml += `
    <div style="margin-top:16px;padding-top:16px;border-top:2px solid var(--border)"><h4 style="margin:8px 0;color:var(--primary)">Additional Details:</h4>`;

    if (s.details && Object.keys(s.details).length > 0) {
        for (const [key, value] of Object.entries(s.details)) {
            const label = key.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
            detailsHtml += `<div class="detail-row"><span class="detail-label">${label}:</span><span class="detail-value">${value}</span></div>`;
        }
    } else {
        detailsHtml += `<div style="margin-top:16px;padding:12px;background:#fef3c7;border-radius:6px;text-align:center;color:#92400e">⚠️ No additional details stored for this staff member</div>`;
    }

    detailsHtml += `</div>`;
    detailsHtml += `</div>`;
    document.getElementById('detailsContent').innerHTML = detailsHtml;
    document.getElementById('detailsModal').style.display='flex';
}

load();
</script>
</body>
</html>