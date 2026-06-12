<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher');
$schoolId = getAuthenticatedSchoolId();
$userId = getAuthenticatedUserId();
$pdo = getDBConnection();

// Fetch classes assigned to teacher
$classesStmt = $pdo->prepare("
    SELECT c.id, c.name, c.grade_level 
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
<title>Strand Score Entry | CBC Manager</title>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1000px;margin:0 auto}
.header{display:flex;justify-content:space-between;margin-bottom:20px}
.controls{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:20px;background:var(--card);padding:16px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
select,input{padding:10px;border:1px solid var(--border);border-radius:6px;width:100%}
table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
th,td{padding:12px;text-align:left;border-bottom:1px solid var(--border)}
th{background:#f8fafc;color:var(--muted);font-weight:600}
.badge{padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;color:#fff}
.ee{background:#059669} .me{background:#2563eb} .ae{background:#d97706} .be{background:#dc2626}
.btn{padding:10px 20px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer}
.btn:hover{opacity:0.9}
.toast{position:fixed;bottom:20px;right:20px;padding:12px 20px;background:var(--primary);color:#fff;border-radius:8px;transform:translateY(100px);transition:0.3s}
.toast.show{transform:translateY(0)}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📝 Strand Score Entry</h1>
        <a href="/multi/views/teacher/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border-radius:6px;text-decoration:none;color:var(--text)">← Back</a>
    </div>

    <div class="controls">
        <div>
            <label>Class</label>
            <select id="classSelect" onchange="loadStrands()">
                <option value="">Select Class</option>
                <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>" data-grade="<?= $c['grade_level'] ?>"><?= $c['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Subject</label>
            <select id="subjectSelect" onchange="loadStrands()">
                <option value="">Select Subject</option>
                <!-- Populated via JS -->
            </select>
        </div>
        <div>
            <label>Strand (Learning Area)</label>
            <select id="strandSelect">
                <option value="">Select Strand</option>
            </select>
        </div>
    </div>

    <table id="scoreTable">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Adm No</th>
                <th>Marks (0-100)</th>
                <th>Achievement Level</th>
            </tr>
        </thead>
        <tbody id="rows">
            <tr><td colspan="4" style="text-align:center;color:var(--muted)">Select Class and Subject to load students</td></tr>
        </tbody>
    </table>

    <div style="margin-top:20px;text-align:right">
        <button class="btn" onclick="saveScores()">💾 Save All Scores</button>
    </div>
</div>

<div id="toast" class="toast"></div>

<script>
let students = [];

// Load Subjects based on Class
document.getElementById('classSelect').addEventListener('change', function() {
    const classId = this.value;
    if(!classId) return;
    
    // In a real app, fetch subjects for this specific class
    // For now, hardcoding common subjects or fetching from API
    fetch(`/multi/api/admin/subject_assignments.php?action=get_subjects&class_id=${classId}`)
    .then(r => r.json())
    .then(data => {
        const sel = document.getElementById('subjectSelect');
        sel.innerHTML = '<option value="">Select Subject</option>';
        data.forEach(s => {
            sel.innerHTML += `<option value="${s.id}">${s.name}</option>`;
        });
    });
});

// Load Strands based on Subject and Grade Level
function loadStrands() {
    const subjectId = document.getElementById('subjectSelect').value;
    const classSelect = document.getElementById('classSelect');
    const gradeLevel = classSelect.options[classSelect.selectedIndex]?.dataset?.grade || 7;

    if (!subjectId) return;

    fetch(`/multi/api/teacher/strand_scores.php?action=get_strands&subject_id=${subjectId}&grade_level=${gradeLevel}`)
    .then(r => r.json())
    .then(strands => {
        const sel = document.getElementById('strandSelect');
        sel.innerHTML = '<option value="">Select Strand</option>';
        strands.forEach(s => {
            sel.innerHTML += `<option value="${s.id}">${s.name} (${s.code})</option>`;
        });
        loadStudents();
    });
}

// Load Students
function loadStudents() {
    const classId = document.getElementById('classSelect').value;
    if (!classId) return;

    fetch(`/multi/api/student/crud.php?action=list`)
    .then(r => r.json())
    .then(allStudents => {
        students = allStudents.filter(s => s.class_id == classId);
        renderTable();
    });
}

// Render Table
function renderTable() {
    const tbody = document.getElementById('rows');
    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center">No students found</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    students.forEach((s, i) => {
        tbody.innerHTML += `
        <tr>
            <td>${s.name}</td>
            <td>${s.admission_number}</td>
            <td><input type="number" id="mark_${s.id}" min="0" max="100" style="width:80px"></td>
            <td>
                <select id="level_${s.id}" style="width:120px">
                    <option value="">Select</option>
                    <option value="EE1">EE1</option>
                    <option value="EE2">EE2</option>
                    <option value="ME1">ME1</option>
                    <option value="ME2">ME2</option>
                    <option value="AE1">AE1</option>
                    <option value="AE2">AE2</option>
                    <option value="BE1">BE1</option>
                    <option value="BE2">BE2</option>
                </select>
            </td>
        </tr>`;
    });
}

// Save Scores
function saveScores() {
    const strandId = document.getElementById('strandSelect').value;
    if (!strandId) {
        alert('Please select a strand'); return;
    }

    const records = students.map(s => ({
        student_id: s.id,
        marks: document.getElementById(`mark_${s.id}`).value,
        level: document.getElementById(`level_${s.id}`).value
    }));

    fetch('/multi/api/teacher/strand_scores.php?action=batch_save', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            strand_id: strandId,
            term: 1, // Dynamic based on current term
            records: records
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast('✅ Scores saved successfully!');
        } else {
            showToast('❌ Error: ' + res.error);
        }
    });
}

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>