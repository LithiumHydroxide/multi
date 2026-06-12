<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Academics Setup | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1000px;margin:0 auto}.tabs{display:flex;gap:8px;margin-bottom:16px}.tab{padding:10px 20px;background:var(--card);border:1px solid var(--border);border-radius:8px;cursor:pointer}.tab.active{background:var(--primary);color:#fff;border-color:var(--primary)}.panel{background:var(--card);padding:20px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.form-row{display:flex;gap:10px;margin-bottom:12px}.form-row input,.form-row select{flex:1;padding:10px;border:1px solid var(--border);border-radius:6px}.btn{padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{padding:10px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}th{background:#f8fafc;color:var(--muted)}</style></head>
<body>
<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;gap:12px;flex-wrap:wrap">
        <h1 style="margin:0">📚 Academics Setup</h1>
        <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>
    <div class="tabs"><button class="tab active" onclick="switchTab('classes')">Classes</button><button class="tab" onclick="switchTab('subjects')">Subjects</button></div>
    
   <div id="classesPanel" class="panel">
    <h3 style="margin-bottom:12px">Add New Class</h3>
    <form id="classForm" style="margin-bottom:16px">
        <div class="form-row" style="align-items:flex-end;gap:10px;flex-wrap:wrap">
            <input name="name" placeholder="e.g. Grade 5 East, PP1, Form 3" required style="flex:2;min-width:220px">
            <select name="grade" style="flex:1;min-width:150px" title="0=Kindergarten, 1-6=Primary, 7-9=JSS, 10-12=SSS, None=Custom">
                <option value="">No Grade/Custom</option>
                <option value="0">PP1 / Kindergarten</option>
                <option value="1">Grade 1</option><option value="2">Grade 2</option>
                <option value="3">Grade 3</option><option value="4">Grade 4</option>
                <option value="5">Grade 5</option><option value="6">Grade 6</option>
                <option value="7">Grade 7 (JSS)</option><option value="8">Grade 8 (JSS)</option>
                <option value="9">Grade 9 (JSS)</option>
                <option value="10">Grade 10 (SSS)</option><option value="11">Grade 11 (SSS)</option>
                <option value="12">Grade 12 (SSS)</option>
            </select>
            <div style="display:flex;gap:8px;flex:1;min-width:220px;align-items:center">
                <select name="stream_select" id="streamSelect" required style="flex:1;min-width:160px" onchange="handleStreamSelectChange()">
                    <option value="">Select Stream</option>
                    <option value="__new">+ Add new stream</option>
                </select>
                <input name="stream_new" id="streamNewInput" placeholder="New stream code" style="flex:1;display:none;min-width:160px">
            </div>
            <button class="btn" type="submit">Add</button>
        </div>
    </form>
    <table>
        <thead><tr><th>Name</th><th>CBC Level</th><th>Stream</th></tr></thead>
        <tbody id="classRows"></tbody>
    </table>
</div>

    <div id="subjectsPanel" class="panel" style="display:none">
        <h3 style="margin-bottom:12px">Add New Subject</h3>
        <form id="subjectForm" style="margin-bottom:16px">
            <div class="form-row">
                <select name="phase" required style="flex:1">
                    <option value="">Select Curriculum Phase</option>
                    <option value="lower_primary">Lower Primary (Grades 1-3)</option>
                    <option value="upper_primary">Upper Primary (Grades 4-6)</option>
                    <option value="junior_secondary">Junior Secondary (Grades 7-9)</option>
                    <option value="senior_secondary">Senior Secondary (Grades 10-12)</option>
                </select>
                <select name="learning_area" required style="flex:1">
                    <option value="">Select Learning Area</option>
                </select>
            </div>
            <div class="form-row">
                <input name="name" placeholder="Subject Name (e.g. Mathematics)" required style="flex:1">
                <input name="code" placeholder="Code (e.g. MAT)" required style="flex:1">
                <button class="btn" type="submit">Add Subject</button>
            </div>
        </form>
        <table><thead><tr><th>Subject</th><th>Code</th><th>Learning Area</th><th>Phase</th></tr></thead><tbody id="subjectRows"></tbody></table>
    </div>
</div>

<script>
function getGradeLabel(grade) {
    if (grade === null) return 'Custom';
    if (grade === 0) return 'PP1 / Kindergarten';
    if (grade <= 6) return `Primary G${grade}`;
    if (grade <= 9) return `JSS G${grade}`;
    return `SSS G${grade}`;
}
async function loadData() {
    const res = await fetch('/multi/api/academics/setup.php?action=list');
    const data = await res.json();
    document.getElementById('classRows').innerHTML = data.classes.map(c => `<tr><td>${c.name}</td><td>${getGradeLabel(c.grade_level)}</td><td>${c.stream_code}</td></tr>`).join('');
    document.getElementById('subjectRows').innerHTML = data.subjects.map(s => `<tr><td>${s.name}</td><td>${s.code}</td><td>${s.learning_area||'-'}</td><td>${s.phase||'-'}</td></tr>`).join('');
}

const curriculumPhases = {
    lower_primary: {
        label: 'Lower Primary (Grades 1-3)',
        areas: ['English Activities', 'Kiswahili Activities', 'Mathematics Activities', 'Environmental Activities', 'Religious Education Activities', 'Creative Activities', 'Indigenous Language Activities']
    },
    upper_primary: {
        label: 'Upper Primary (Grades 4-6)',
        areas: ['English', 'Kiswahili', 'Mathematics', 'Science and Technology', 'Agriculture and Nutrition', 'Social Studies', 'Creative Arts', 'Religious Education']
    },
    junior_secondary: {
        label: 'Junior Secondary (Grades 7-9)',
        areas: ['English', 'Kiswahili', 'Mathematics', 'Religious Education', 'Integrated Science', 'Social Studies', 'Agriculture & Home Science', 'Pre-Technical Studies', 'Creative Arts & Sports']
    },
    senior_secondary: {
        label: 'Senior Secondary (Grades 10-12)',
        areas: ['English', 'Kiswahili', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Computer Science', 'Business Studies', 'Economics', 'History', 'Geography', 'Citizenship', 'Religious Studies', 'Creative Arts', 'Physical Education', 'Agriculture']
    }
};

function initializeSubjectForm() {
    const phaseSelect = document.querySelector('select[name="phase"]');
    if (!phaseSelect) return;
    
    phaseSelect.addEventListener('change', e => {
        const phase = e.target.value;
        const areaSelect = document.querySelector('select[name="learning_area"]');
        areaSelect.innerHTML = '<option value="">Select Learning Area</option>';
        if (phase && curriculumPhases[phase]) {
            curriculumPhases[phase].areas.forEach(area => {
                const opt = document.createElement('option');
                opt.value = area;
                opt.textContent = area;
                areaSelect.appendChild(opt);
            });
        }
    });
}

function getGradeLabel(grade) {
    if (grade === null) return 'Custom';
    if (grade === 0) return 'PP1 / Kindergarten';
    if (grade <= 6) return `Primary G${grade}`;
    if (grade <= 9) return `JSS G${grade}`;
    return `SSS G${grade}`;
}

async function loadStreams() {
    const res = await fetch('/multi/api/admin/streams_houses.php?action=list_streams');
    const streams = await res.json();
    const select = document.getElementById('streamSelect');
    if (!select) return;
    select.innerHTML = '<option value="">Select Stream</option><option value="__new">+ Add new stream</option>';
    streams.forEach(stream => {
        const option = document.createElement('option');
        option.value = stream.stream_code;
        option.textContent = stream.stream_code;
        select.appendChild(option);
    });
}

function handleStreamSelectChange() {
    const select = document.getElementById('streamSelect');
    const customInput = document.getElementById('streamNewInput');
    if (select.value === '__new') {
        customInput.style.display = 'block';
        customInput.required = true;
        customInput.focus();
    } else {
        customInput.style.display = 'none';
        customInput.required = false;
    }
}

async function loadData() {
    const res = await fetch('/multi/api/academics/setup.php?action=list');
    const data = await res.json();
    document.getElementById('classRows').innerHTML = data.classes.map(c => `<tr><td>${c.name}</td><td>${getGradeLabel(c.grade_level)}</td><td>${c.stream_code || '-'}</td></tr>`).join('');
    document.getElementById('subjectRows').innerHTML = data.subjects.map(s => `<tr><td>${s.name}</td><td>${s.code}</td><td>${s.learning_area||'-'}</td><td>${s.phase||'-'}</td></tr>`).join('');
}

document.getElementById('classForm').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const payload = Object.fromEntries(formData);
    payload.grade = payload.grade === '' ? null : payload.grade;
    const selectedStream = payload.stream_select;
    const customStream = (payload.stream_new || '').trim();
    payload.stream = selectedStream === '__new' ? customStream : selectedStream;
    if (!payload.stream) {
        alert('Please choose an existing stream or enter a new one.');
        return;
    }
    delete payload.stream_select;
    delete payload.stream_new;
    const res = await fetch('/multi/api/academics/setup.php?action=create', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({...payload, type:'class'})});
    const result = await res.json();
    if (result.success) {
        loadData();
        e.target.reset();
        handleStreamSelectChange();
    } else {
        alert('Error: ' + (result.error || 'Failed to add class'));
    }
});

document.getElementById('subjectForm').addEventListener('submit', async e => {
    e.preventDefault();
    const payload = Object.fromEntries(new FormData(e.target));
    console.log('Submitting subject:', payload);
    const res = await fetch('/multi/api/academics/setup.php?action=create', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({...payload, type:'subject'})});
    const result = await res.json();
    if (result.success) {
        loadData(); 
        e.target.reset();
        document.querySelector('select[name="phase"]').value = '';
        document.querySelector('select[name="learning_area"]').innerHTML = '<option value="">Select Learning Area</option>';
    } else {
        alert('Error: ' + (result.error || 'Failed to add subject'));
    }
});

function switchTab(tab) {
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', (tab==='classes' && i===0) || (tab==='subjects' && i===1)));
    document.getElementById('classesPanel').style.display = tab==='classes' ? 'block' : 'none';
    document.getElementById('subjectsPanel').style.display = tab==='subjects' ? 'block' : 'none';
}

// Initialize everything after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initializeSubjectForm();
        loadStreams();
        loadData();
    });
} else {
    initializeSubjectForm();
    loadStreams();
    loadData();
}
</script>
</body>
</html>