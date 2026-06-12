<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Class Assignments | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1200px;margin:0 auto}
.header{margin-bottom:24px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}@media(max-width:900px){.grid{grid-template-columns:1fr}}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
h2{margin-bottom:16px;font-size:1.2rem;color:var(--primary)}
h3{margin-top:20px;margin-bottom:12px;font-size:1rem;color:var(--muted)}
.form-group{margin-bottom:14px}
label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px;font-weight:600}
select,input{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px;font-size:0.9rem}
select:focus,input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.btn{padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:0.2s}
.btn:hover{background:#1d4ed8}
.btn-danger{background:#dc2626}
.btn-danger:hover{background:#b91c1c}
.btn-small{padding:6px 12px;font-size:0.8rem}
.alert{padding:12px;border-radius:6px;margin-bottom:12px;font-size:0.9rem}
.alert-success{background:#d1fae5;color:#065f46;border-left:4px solid var(--success)}
.alert-danger{background:#fee2e2;color:#7f1d1d;border-left:4px solid var(--danger)}
.alert-info{background:#dbeafe;color:#1e40af;border-left:4px solid var(--primary)}
table{width:100%;border-collapse:collapse;margin-top:16px}
th,td{padding:12px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f8fafc;color:var(--muted);font-weight:600}
.empty-state{text-align:center;padding:32px;color:var(--muted)}
button[type="submit"]{width:100%}
</style>
</head>
<body>
<div class="container">
<div class="header" style="justify-content:space-between;align-items:flex-start;margin-bottom:24px;flex-wrap:wrap;gap:12px">
<div>
<div style="display:flex;gap:12px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
<h1 style="margin:0">🎓 Class Management</h1>
<a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
</div>
<p style="color:var(--muted);margin-top:8px">Assign class teachers, assistant teachers, prefects, and subject instructors</p>
</div>
</div>

<div class="grid">
<div class="card">
<h2>👨‍🏫 Class Leadership</h2>
<p style="color:var(--muted);font-size:0.9rem;margin-bottom:16px">Assign class teachers, assistants, and prefects</p>
<div id="leadershipAlert"></div>
<form id="leadershipForm">
<div class="form-group">
    <label>Select Class *</label>
    <select id="lClass" name="class_id" required>
        <option value="">Select Class</option>
    </select>
</div>
<div class="form-group">
    <label>Class Teacher</label>
    <select id="lCT" name="class_teacher_id">
        <option value="">None</option>
    </select>
</div>
<div class="form-group">
    <label>Assistant Class Teacher</label>
    <select id="lAT" name="assistant_teacher_id">
        <option value="">None</option>
    </select>
</div>
<div class="form-group">
    <label>Class Prefect (Student from selected class)</label>
    <select id="lPrefect" name="prefect_id" disabled>
        <option value="">Select a class first</option>
    </select>
    <small style="color:var(--muted);font-size:0.75rem;display:block;margin-top:4px">Only students from the selected class will appear</small>
</div>
<button type="submit" class="btn">💾 Save Leadership</button>
</form>
</div>

<div class="card">
<h2>📚 Subject-to-Teacher</h2>
<p style="color:var(--muted);font-size:0.9rem;margin-bottom:16px">Map subjects to specific teachers per class</p>
<div id="subjectAlert"></div>
<form id="subjectForm">
<div class="form-group">
    <label>Class *</label>
    <select id="sClass" name="class_id" required>
        <option value="">Select Class</option>
    </select>
</div>
<div class="form-group">
    <label>Subject *</label>
    <select id="sSubject" name="subject_id" required>
        <option value="">Select Class First</option>
    </select>
</div>
<div class="form-group">
    <label>Teacher *</label>
    <select id="sTeacher" name="teacher_id" required>
        <option value="">Select Teacher</option>
    </select>
</div>
<button type="submit" class="btn">➕ Assign Subject</button>
</form>

<h3 style="margin-top:24px;font-size:1rem">Current Assignments</h3>
<div style="overflow-x:auto">
<table id="assignTable">
<thead><tr><th>Class</th><th>Subject</th><th>Teacher</th><th style="width:80px">Action</th></tr></thead>
<tbody id="assignRows"><tr><td colspan="4" style="text-align:center;color:var(--muted)">Loading...</td></tr></tbody>
</table>
</div>
</div>
</div>
</div>

<script>
let allTeachers = [];
let subjectsCache = {};

function showAlert(id, message, type) {
    const el = document.getElementById(id);
    el.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    setTimeout(() => el.innerHTML = '', 5000);
}

async function init(){
    try {
        const [dropRes, leadRes, subRes] = await Promise.all([
            fetch('/multi/api/admin/class_leadership.php?action=dropdowns').then(r=>r.json()),
            fetch('/multi/api/admin/class_leadership.php?action=list').then(r=>r.json()),
            fetch('/multi/api/admin/subject_assignments.php?action=list').then(r=>r.json())
        ]);
        
        allTeachers = dropRes.teachers;
        
        // Populate class dropdowns
        const classes = leadRes.map(c=>`<option value="${c.id}">${c.name} (Grade ${c.grade_level})</option>`).join('');
        document.getElementById('lClass').innerHTML = '<option value="">Select Class</option>'+classes;
        document.getElementById('sClass').innerHTML = '<option value="">Select Class</option>'+classes;
        
        // Populate teacher dropdowns
        allTeachers.forEach(t=>{
            document.getElementById('lCT').innerHTML += `<option value="${t.id}">${t.name}</option>`;
            document.getElementById('lAT').innerHTML += `<option value="${t.id}">${t.name}</option>`;
            document.getElementById('sTeacher').innerHTML += `<option value="${t.id}">${t.name}</option>`;
        });
        
        // Load assignments
        loadAssignments(subRes);
        
        // Add event listener for class change to load prefects
        document.getElementById('lClass').addEventListener('change', loadPrefects);
        
    } catch(e) {
        showAlert('leadershipAlert', 'Error loading data: '+e.message, 'danger');
        console.error('Full error:', e);
    }
}

async function loadPrefects() {
    const classId = document.getElementById('lClass').value;
    const prefectSelect = document.getElementById('lPrefect');
    
    if (!classId) {
        prefectSelect.innerHTML = '<option value="">Select a class first</option>';
        prefectSelect.disabled = true;
        return;
    }
    
    prefectSelect.innerHTML = '<option value="">Loading students...</option>';
    prefectSelect.disabled = true;
    
    try {
        const res = await fetch(`/multi/api/admin/class_leadership.php?action=get_students_by_class&class_id=${classId}`);
        const students = await res.json();
        
        if (students.length === 0) {
            prefectSelect.innerHTML = '<option value="">No students in this class</option>';
            prefectSelect.disabled = true;
            return;
        }
        
        prefectSelect.innerHTML = '<option value="">None</option>';
        students.forEach(s => {
            prefectSelect.innerHTML += `<option value="${s.id}">${s.name} (${s.admission_number})</option>`;
        });
        prefectSelect.disabled = false;
    } catch (e) {
        prefectSelect.innerHTML = '<option value="">Error loading students</option>';
        prefectSelect.disabled = true;
        console.error('Error loading prefects:', e);
    }
}

function loadAssignments(data) {
    const rows = document.getElementById('assignRows');
    if(data.length === 0) {
        rows.innerHTML = '<tr><td colspan="4" class="empty-state">No assignments yet</td></tr>';
        return;
    }
    rows.innerHTML = data.map(a=>`<tr><td>${a.class_name}</td><td>${a.subject_name}</td><td>${a.teacher_name}</td><td><button type="button" class="btn btn-danger btn-small" onclick="deleteAssignment(${a.id})">Delete</button></td></tr>`).join('');
}

async function loadSubjects() {
    const classId = document.getElementById('sClass').value;
    if(!classId) {
        document.getElementById('sSubject').innerHTML = '<option value="">Select Class First</option>';
        return;
    }
    try {
        const res = await fetch(`/multi/api/admin/subject_assignments.php?action=get_subjects&class_id=${classId}`);
        const subjects = await res.json();
        document.getElementById('sSubject').innerHTML = '<option value="">Select Subject</option>' +
        subjects.map(s=>`<option value="${s.id}">${s.name}</option>`).join('');
    } catch(e) {
        showAlert('subjectAlert', 'Error loading subjects', 'danger');
    }
}

document.getElementById('sClass').addEventListener('change', loadSubjects);

document.getElementById('leadershipForm').addEventListener('submit', async e=>{
    e.preventDefault();
    try {
        const data = {
            class_id: document.getElementById('lClass').value,
            class_teacher_id: document.getElementById('lCT').value || null,
            assistant_teacher_id: document.getElementById('lAT').value || null,
            prefect_id: document.getElementById('lPrefect').value || null
        };
        const r = await fetch('/multi/api/admin/class_leadership.php?action=update', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(data)
        });
        const res = await r.json();
        if(res.success) showAlert('leadershipAlert', '✅ Class leadership updated successfully', 'success');
        else showAlert('leadershipAlert', res.error || 'Failed to update', 'danger');
    } catch(e) {
        showAlert('leadershipAlert', 'Error: '+e.message, 'danger');
    }
});

document.getElementById('subjectForm').addEventListener('submit', async e=>{
    e.preventDefault();
    try {
        const data = {
            class_id: document.getElementById('sClass').value,
            subject_id: document.getElementById('sSubject').value,
            teacher_id: document.getElementById('sTeacher').value
        };
        const r = await fetch('/multi/api/admin/subject_assignments.php?action=create', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify(data)
        });
        const res = await r.json();
        if(res.success) {
            showAlert('subjectAlert', '✅ Subject assigned successfully', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('subjectAlert', res.error || 'Failed to assign', 'danger');
        }
    } catch(e) {
        showAlert('subjectAlert', 'Error: '+e.message, 'danger');
    }
});

async function deleteAssignment(id) {
    if(!confirm('Remove this assignment?')) return;
    try {
        const r = await fetch('/multi/api/admin/subject_assignments.php?action=delete', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({id})
        });
        const res = await r.json();
        if(res.success) {
            showAlert('subjectAlert', '✅ Assignment deleted', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('subjectAlert', 'Failed to delete', 'danger');
        }
    } catch(e) {
        showAlert('subjectAlert', 'Error: '+e.message, 'danger');
    }
}

init();
</script>
</body>
</html>