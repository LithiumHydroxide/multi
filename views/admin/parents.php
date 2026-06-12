<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fetch classes and students for dropdowns
$classes = $pdo->query("SELECT id, name FROM classes WHERE school_id = $schoolId ORDER BY grade_level, name")->fetchAll();
$students = $pdo->query("SELECT id, name, admission_number FROM students WHERE school_id = $schoolId AND status = 'active' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parents & Guardians | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1100px;margin:0 auto}
.header{display:flex;justify-content:space-between;margin-bottom:16px}
table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f1f5f9;font-weight:600;color:var(--muted)}
.btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer;font-size:0.85rem}
.btn-primary{background:var(--primary);color:#fff}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}
.modal-content{background:var(--card);padding:24px;border-radius:12px;width:90%;max-width:500px}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px}
.form-group input,.form-group select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px}
.badge{padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:600}
.badge-father{background:#dbeafe;color:#1e40af}
.badge-mother{background:#fce7f3;color:#9d174d}
.badge-guardian{background:#fef3c7;color:#d97706}
</style>
</head>
<body>
<div class="container">
<div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
<div style="display:flex;gap:12px;align-items:center">
<h1 style="margin:0">👨‍👩‍ Parents & Guardians</h1>
<a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem">← Back</a>
</div>
<button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary">+ Add Parent</button>
</div>

<table>
<thead>
<tr><th>Name</th><th>Phone</th><th>Relation</th><th>Linked Students</th><th>Actions</th></tr>
</thead>
<tbody id="rows"><tr><td colspan="5" style="text-align:center">Loading...</td></tr></tbody>
</table>
</div>

<!-- Add Parent Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Add New Parent/Guardian</h2>
<form id="parentForm">
<div class="form-group" style="display:flex;gap:12px">
    <div style="flex:1"><label>First Name</label><input name="first_name" required></div>
    <div style="flex:1"><label>Last Name</label><input name="last_name" required></div>
</div>
<div class="form-group"><label>Phone Number *</label><input name="phone" placeholder="+254..." required></div>
<div class="form-group"><label>Email (Optional)</label><input type="email" name="email"></div>
<div class="form-group"><label>Relation</label>
    <select name="relation">
        <option value="Father">Father</option>
        <option value="Mother">Mother</option>
        <option value="Guardian" selected>Guardian</option>
        <option value="Sponsor">Sponsor</option>
    </select>
</div>

<!-- NEW: Class Filter for Easier Student Lookup -->
<div class="form-group">
    <label>Filter Student by Class/Grade</label>
    <select id="classFilter">
        <option value="">-- All Classes --</option>
        <?php foreach($classes as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="form-group">
    <label>Link to Student (Optional)</label>
    <select name="student_id" id="studentSelect">
        <option value="">-- No Link --</option>
        <?php foreach($students as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['admission_number'] ?>)</option>
        <?php endforeach; ?>
    </select>
</div>

<div style="display:flex;gap:10px;margin-top:16px">
    <button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Save</button>
    <button type="button" onclick="document.getElementById('addModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
</div>
</form>
</div>
</div>

<script>
// Load Parents
async function loadParents() {
const res = await fetch('/multi/api/admin/parents.php?action=list');
const data = await res.json();
const tb = document.getElementById('rows');
tb.innerHTML = '';
data.forEach(p => {
    let badgeClass = 'badge-guardian';
    if(p.relation === 'Father') badgeClass = 'badge-father';
    if(p.relation === 'Mother') badgeClass = 'badge-mother';
    
    tb.innerHTML += `<tr>
        <td><strong>${p.first_name} ${p.last_name}</strong></td>
        <td>${p.phone}</td>
        <td><span class="badge ${badgeClass}">${p.relation}</span></td>
        <td>${p.student_count || 0}</td>
        <td>
            <button class="btn" onclick="assignStudent(${p.id})">Assign Student</button>
            <button class="btn btn-danger" onclick="deleteParent(${p.id})">Delete</button>
        </td>
    </tr>`;
});
}

// Submit Form
document.getElementById('parentForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    const res = await fetch('/multi/api/admin/parents.php?action=create', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    });
    const result = await res.json();
    if(result.success) {
        document.getElementById('addModal').style.display = 'none';
        e.target.reset();
        loadParents();
    } else {
        alert('❌ ' + result.error);
    }
});

// Delete Parent
async function deleteParent(id) {
    if(!confirm('Delete this parent? Links to students will be removed.')) return;
    await fetch('/multi/api/admin/parents.php?action=delete', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id})
    });
    loadParents();
}

// Assign Student to Existing Parent
async function assignStudent(parentId) {
    const studentId = prompt("Enter Student ID to link:");
    if(studentId) {
        await fetch('/multi/api/admin/parents.php?action=assign', {
            method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({parent_id: parentId, student_id: studentId})
        });
        loadParents();
    }
}

// 🆕 Dynamic Class Filter for Student Dropdown
document.getElementById('classFilter').addEventListener('change', async function() {
    const classId = this.value;
    const studentSelect = document.getElementById('studentSelect');
    studentSelect.innerHTML = '<option value="">Loading...</option>';
    
    try {
        const res = await fetch(`/multi/api/admin/parents.php?action=get_students_by_class&class_id=${classId}`);
        const students = await res.json();
        
        studentSelect.innerHTML = '<option value="">-- No Link --</option>';
        if(students.length === 0) {
            studentSelect.innerHTML = '<option value="">No students in this class</option>';
        } else {
            students.forEach(s => {
                studentSelect.innerHTML += `<option value="${s.id}">${s.name} (${s.admission_number})</option>`;
            });
        }
    } catch(e) {
        studentSelect.innerHTML = '<option value="">Error loading students</option>';
    }
});

loadParents();
</script>
</body>
</html>