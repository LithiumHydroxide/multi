<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); 
$pdo = getDBConnection();
$schoolId = getAuthenticatedSchoolId();
$teacherCount = $pdo->query("SELECT COUNT(*) as count FROM teachers WHERE school_id = $schoolId")->fetch()['count'];
$activeCount = $pdo->query("SELECT COUNT(*) as count FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.school_id = $schoolId AND u.status = 'active'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Teachers | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--danger:#dc2626;--success:#059669;--warning:#d97706}
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
.form-group input{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.95rem}
.form-group input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
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
<h1>👨‍🏫 Manage Teachers</h1>
<p class="header-subtitle">View, add, and manage teaching staff</p>
</div>
<a href="/multi/views/admin/dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
</div>

<div class="stats-grid">
<div class="stat-card total">
<div class="stat-card-value"><?= $teacherCount ?></div>
<div class="stat-card-label">Total Teachers</div>
</div>
<div class="stat-card active">
<div class="stat-card-value"><?= $activeCount ?></div>
<div class="stat-card-label">Active Teachers</div>
</div>
</div>

<div class="controls">
<input type="text" id="search" class="search-box" placeholder="🔍 Search teachers by name, email, or TSC number...">
<button onclick="document.getElementById('addModal').style.display='flex'" class="btn btn-primary">+ Add Teacher</button>
</div>

<div class="table-container">
<table>
<thead>
<tr>
<th>Name</th>
<th>Email</th>
<th>TSC Number</th>
<th>Specialization</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="rows">
<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--muted)">Loading teachers...</td></tr>
</tbody>
</table>
</div>
</div>

<!-- View Details Modal -->
<div class="modal" id="detailsModal">
<div class="modal-content">
<h2>👨‍🏫 Teacher Details</h2>
<div id="detailsContent" style="background:#f8fafc;padding:16px;border-radius:8px;margin-bottom:20px"></div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('detailsModal').style.display='none'" class="btn btn-outline">Close</button>
</div>
</div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<h2>✏️ Edit Teacher</h2>
<form id="editForm">
<input type="hidden" name="id" id="editTeacherId">
<div class="form-group">
<label>Full Name *</label>
<input name="name" id="editName" required placeholder="e.g. John Ochieng">
</div>
<div class="form-group">
<label>Email *</label>
<input type="email" name="email" id="editEmail" required placeholder="john@school.com">
</div>
<div class="form-group">
<label>TSC Number</label>
<input name="tsc_number" id="editTsc" placeholder="e.g. 123456">
</div>
<div class="form-group">
<label>Specialization</label>
<input name="specialization" id="editSpecialization" placeholder="e.g. Mathematics, Sciences">
</div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('editModal').style.display='none'" class="btn btn-outline">Cancel</button>
<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>

<!-- Add Teacher Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2>➕ Add New Teacher</h2>
<form id="form">
<div class="form-group">
<label>Full Name *</label>
<input name="name" required placeholder="e.g. John Ochieng">
</div>
<div class="form-group">
<label>Email *</label>
<input type="email" name="email" required placeholder="john@school.com">
</div>
<div class="form-group">
<label>Password (8+ characters) *</label>
<input type="password" name="password" required minlength="8" placeholder="••••••••">
</div>
<div class="form-group">
<label>TSC Number</label>
<input name="tsc_number" placeholder="e.g. 123456">
</div>
<div class="form-group">
<label>Specialization</label>
<input name="specialization" placeholder="e.g. Mathematics, Sciences">
</div>
<div class="btn-group">
<button type="button" onclick="document.getElementById('addModal').style.display='none'" class="btn btn-outline">Cancel</button>
<button type="submit" class="btn btn-primary">Create Teacher</button>
</div>
</form>
</div>
</div>

<script>
async function load(){
const res = await fetch('/multi/api/admin/teachers.php?action=list');
const d = await res.json();
const tb = document.getElementById('rows');
tb.innerHTML = '';

if (!d || d.length === 0) {
tb.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📚</div><p>No teachers found. Click "Add Teacher" to create one.</p></div></td></tr>';
return;
}

d.forEach(t => {
const statusClass = t.status === 'active' ? 'badge-active' : 'badge-inactive';
const statusBtnText = t.status === 'active' ? '🔇 Disable' : '✓ Enable';

tb.innerHTML += `<tr>
<td><strong>${t.name}</strong></td>
<td>${t.email}</td>
<td>${t.tsc_number || '-'}</td>
<td>${t.specialization || '-'}</td>
<td><span class="badge ${statusClass}">${t.status === 'active' ? 'Active' : 'Inactive'}</span></td>
<td>
<button class="btn btn-action" onclick="viewTeacherDetails('${t.id}', '${t.name.replace(/'/g, "\\'")}', '${t.email}', '${(t.tsc_number||'').replace(/'/g, "\\'")}', '${(t.specialization||'').replace(/'/g, "\\'")}', '${(t.subjects||'').replace(/'/g, "\\'")}', '${(t.class_assignments||'').replace(/'/g, "\\'")}', '${t.status}')">👁️ View</button>
<button class="btn btn-action" onclick="openEditTeacherModal('${t.id}')">✏️ Edit</button>
<button class="btn btn-action" onclick="tog('${t.id}')">${statusBtnText}</button>
<button class="btn btn-action btn-danger" onclick="deleteTeacher('${t.id}', '${t.name.replace(/'/g, "\\'")}')">🗑️ Delete</button>
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
const formData = new FormData(e.target);
const data = Object.fromEntries(formData);

const r = await fetch('/multi/api/admin/teachers.php?action=create', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(data)
});

const result = await r.json();
if(result.success) {
alert('✅ Teacher created successfully');
document.getElementById('addModal').style.display = 'none';
load();
e.target.reset();
} else {
alert('❌ ' + (result.error || 'Failed to create teacher'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Create Teacher';
}
});

async function tog(id){
try {
await fetch('/multi/api/admin/teachers.php?action=toggle', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({id})
});
load();
} catch (e) {
alert('❌ Network error: ' + e.message);
}
}

async function deleteTeacher(id, name) {
if(!confirm(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) return;

try {
const res = await fetch('/multi/api/admin/teachers.php?action=delete', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({id})
});

const result = await res.json();
if(result.success) {
alert('✅ Teacher deleted successfully');
load();
} else {
alert('❌ ' + (result.error || 'Failed to delete teacher'));
}
} catch(e) {
alert('❌ Network error: ' + e.message);
}
}

async function openEditTeacherModal(id) {
try {
const res = await fetch(`/multi/api/admin/teachers.php?action=view&id=${id}`);
const t = await res.json();
if (!t) {
alert('Teacher not found');
return;
}
document.getElementById('editTeacherId').value = t.id;
document.getElementById('editName').value = t.name || '';
document.getElementById('editEmail').value = t.email || '';
document.getElementById('editTsc').value = t.tsc_number || '';
document.getElementById('editSpecialization').value = t.specialization || '';
document.getElementById('editModal').style.display = 'flex';
} catch (e) {
alert('Failed to load teacher details');
}
}

document.getElementById('editForm').addEventListener('submit', async e => {
e.preventDefault();
const btn = e.target.querySelector('button[type="submit"]');
btn.disabled = true;
btn.textContent = 'Saving...';

try {
const formData = new FormData(e.target);
const data = Object.fromEntries(formData);
const res = await fetch('/multi/api/admin/teachers.php?action=update', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(data)
});
const result = await res.json();
if (result.success) {
alert('✅ Teacher updated successfully');
document.getElementById('editModal').style.display = 'none';
load();
} else {
alert('❌ ' + (result.error || 'Failed to update teacher'));
}
} catch (err) {
alert('❌ Network error: ' + err.message);
} finally {
btn.disabled = false;
btn.textContent = 'Save Changes';
}
});

function viewTeacherDetails(id, name, email, tscNumber, specialization, subjects, classAssignments, status) {
const statusClass = status === 'active' ? 'badge-active' : 'badge-inactive';

let detailsHtml = `
<div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value"><strong>${name}</strong></span></div>
<div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${email}</span></div>
<div class="detail-row"><span class="detail-label">TSC Number:</span><span class="detail-value">${tscNumber || 'Not specified'}</span></div>
<div class="detail-row"><span class="detail-label">Specialization:</span><span class="detail-value">${specialization || 'Not specified'}</span></div>
<div class="detail-row"><span class="detail-label">Subjects:</span><span class="detail-value">${subjects || 'None assigned'}</span></div>
<div class="detail-row"><span class="detail-label">Status:</span><span class="detail-value"><span class="badge ${statusClass}">${status === 'active' ? 'Active' : 'Inactive'}</span></span></div>`;

document.getElementById('detailsContent').innerHTML = detailsHtml;
document.getElementById('detailsModal').style.display = 'flex';
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
        <h2 style="margin-bottom:16px">Teacher Details</h2>
        <div id="detailsContent"></div>
        <div style="display:flex;gap:10px;margin-top:16px">
            <button type="button" onclick="document.getElementById('detailsModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Close</button>
        </div>
    </div>
</div>

<!-- Edit Teacher Modal -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <h2 style="margin-bottom:16px">Edit Teacher</h2>
        <form id="editForm">
            <input type="hidden" name="id" id="editTeacherId">
            <div class="form-group"><label>Full Name</label><input name="name" id="editName" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" id="editEmail" required></div>
            <div class="form-group"><label>TSC Number</label><input name="tsc_number" id="editTsc"></div>
            <div class="form-group"><label>Specialization</label><input name="specialization" id="editSpecialization" placeholder="e.g. Mathematics, Sciences"></div>
            <div style="display:flex;gap:10px;margin-top:16px">
                <button type="button" onclick="document.getElementById('editModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
                <button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Teacher Modal -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <h2 style="margin-bottom:16px">Add New Teacher</h2>
        <form id="form">
            <div class="form-group"><label>Full Name</label><input name="name" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Password (8+)</label><input type="password" name="password" required minlength="8"></div>
            <div class="form-group"><label>TSC Number</label><input name="tsc_number"></div>
            <div class="form-group"><label>Specialization</label><input name="specialization" placeholder="e.g. Mathematics, Sciences"></div>
            <div style="display:flex;gap:10px;margin-top:16px">
                <button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Create</button>
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
async function load(){
    const res = await fetch('/multi/api/admin/teachers.php?action=list');
    const d = await res.json();
    const tb = document.getElementById('rows');
    tb.innerHTML = '';
    
    d.forEach(t => {
        const statusClass = t.status === 'active' ? 'badge-active' : 'badge-inactive';
        const statusBtnText = t.status === 'active' ? 'Disable' : 'Enable';
        
        tb.innerHTML += `<tr>
            <td>${t.name}</td>
            <td>${t.email}</td>
            <td>${t.tsc_number || '-'}</td>
            <td>${t.specialization || '-'}</td>
            <td>${t.subjects || 'None'}</td>
            <td><span class="badge ${statusClass}">${t.status}</span></td>
            <td>
                <button class="btn btn-sm" onclick="viewTeacherDetails('${t.id}', '${t.name.replace(/'/g, "\\'")}', '${t.email}', '${(t.tsc_number||'').replace(/'/g, "\\'")}', '${(t.specialization||'').replace(/'/g, "\\'")}', '${(t.subjects||'').replace(/'/g, "\\'")}', '${(t.class_assignments||'').replace(/'/g, "\\'")}', '${t.status}')">View</button>
                <button class="btn btn-sm" onclick="openEditTeacherModal('${t.id}')">Edit</button>
                <button class="btn btn-sm" onclick="tog('${t.id}')">${statusBtnText}</button>
                <button class="btn btn-sm btn-danger" onclick="deleteTeacher('${t.id}', '${t.name.replace(/'/g, "\\'")}')">Delete</button>
            </td>
        </tr>`;
    });
}

document.getElementById('form').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const r = await fetch('/multi/api/admin/teachers.php?action=create', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    if((await r.json()).success) {
        location.reload();
    } else {
        alert('❌ Failed to create teacher.');
    }
});

async function tog(id){
    await fetch('/multi/api/admin/teachers.php?action=toggle', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    });
    load();
}

async function deleteTeacher(id, name) {
    if(!confirm(`Are you sure you want to delete teacher "${name}"?\n\nThis action cannot be undone.`)) return;
    
    try {
        const res = await fetch('/multi/api/admin/teachers.php?action=delete', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        
        const result = await res.json();
        if(result.success) {
            alert('✅ Teacher deleted successfully');
            load();
        } else {
            alert('❌ ' + (result.error || 'Failed to delete teacher'));
        }
    } catch(e) {
        alert(' Network error: ' + e.message);
    }
}

async function openEditTeacherModal(id) {
    try {
        const res = await fetch(`/multi/api/admin/teachers.php?action=view&id=${id}`);
        const t = await res.json();
        if (!t) {
            alert('Teacher not found');
            return;
        }
        document.getElementById('editTeacherId').value = t.id;
        document.getElementById('editName').value = t.name || '';
        document.getElementById('editEmail').value = t.email || '';
        document.getElementById('editTsc').value = t.tsc_number || '';
        document.getElementById('editSpecialization').value = t.specialization || '';
        document.getElementById('editModal').style.display = 'flex';
    } catch (e) {
        alert('Failed to load teacher details');
    }
}

document.getElementById('editForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    const res = await fetch('/multi/api/admin/teachers.php?action=update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
        alert('✅ Teacher updated successfully');
        document.getElementById('editModal').style.display = 'none';
        load();
    } else {
        alert('❌ ' + (result.error || 'Failed to update teacher'));
    }
});

function viewTeacherDetails(id, name, email, tscNumber, specialization, subjects, classAssignments, status) {
    const statusClass = status === 'active' ? 'badge-active' : 'badge-inactive';
    
    // Format class assignments for better readability
    const formattedAssignments = classAssignments ? classAssignments.split('|').map(a => a.trim()).join('<br>') : 'None';
    
    let detailsHtml = `
    <div style="background:#f1f5f9;padding:12px;border-radius:6px;margin-bottom:16px">
        <div class="detail-row"><span class="detail-label">Name:</span><span class="detail-value">${name}</span></div>
        <div class="detail-row"><span class="detail-label">Email:</span><span class="detail-value">${email}</span></div>
        <div class="detail-row"><span class="detail-label">TSC Number:</span><span class="detail-value">${tscNumber || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Specialization:</span><span class="detail-value">${specialization || '-'}</span></div>
        <div class="detail-row"><span class="detail-label">Subjects:</span><span class="detail-value">${subjects || 'None'}</span></div>
        <div class="detail-row"><span class="detail-label">Class Assignments:</span><span class="detail-value">${formattedAssignments}</span></div>
        <div class="detail-row"><span class="detail-label">Status:</span><span class="badge ${statusClass}">${status}</span></div>
    </div>`;
    
    document.getElementById('detailsContent').innerHTML = detailsHtml;
    document.getElementById('detailsModal').style.display = 'flex';
}

load();
</script>
</body>
</html>