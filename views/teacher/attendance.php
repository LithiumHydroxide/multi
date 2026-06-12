<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();

// 🔧 Fix: Initialize Database Connection
$pdo = getDBConnection();

$schoolId = getAuthenticatedSchoolId();
$userId   = getAuthenticatedUserId();

// Get teacher's assigned classes
$classes = $pdo->prepare("
    SELECT c.id, c.name FROM classes c
    JOIN class_subject_teacher cst ON c.id = cst.class_id
    JOIN teachers t ON cst.teacher_id = t.id
    WHERE t.user_id = ?
    ORDER BY c.grade_level, c.name
");
$classes->execute([$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance | CBC Manager</title>
    <!-- Ensure Branding Colors Load -->
    <?= getBrandingCSS($schoolId) ?>
    <style>
        :root{--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
        body{background:var(--bg);padding:24px}
        .container{max-width:1000px;margin:0 auto}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
        .header h1{font-size:1.5rem;font-weight:700;color:var(--text)}
        .controls{background:var(--card);padding:16px;border-radius:var(--radius);box-shadow:0 2px 4px rgba(0,0,0,0.05);display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-bottom:20px}
        .control-group label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px}
        .control-group select,.control-group input[type="date"]{width:100%;padding:10px;border:1px solid var(--border);border-radius:8px;background:white}
        .tabs{display:flex;gap:8px;margin-bottom:16px}
        .tab{padding:10px 20px;background:var(--card);border:1px solid var(--border);border-radius:8px 8px 0 0;cursor:pointer;font-weight:500;color:var(--muted)}
        .tab.active{background:var(--primary);color:white;border-color:var(--primary)}
        .card{background:var(--card);border-radius:var(--radius);box-shadow:0 2px 4px rgba(0,0,0,0.05);padding:16px}
        table{width:100%;border-collapse:collapse}
        th,td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--border)}
        th{background:#f8fafc;font-weight:600;color:var(--muted);font-size:0.85rem}
        tr:hover{background:#fafafa}
        .status-btn{padding:6px 12px;border-radius:20px;border:1px solid var(--border);background:#fff;cursor:pointer;font-size:0.8rem;font-weight:500;transition:0.2s}
        .status-btn:hover{background:#f1f5f9}
        /* Status Colors using CSS Variables for Branding Consistency */
        .status-btn.present{background:var(--primary-bg,#d1fae5);color:var(--primary,#059669);border-color:var(--primary,#059669)}
        .status-btn.absent{background:#fee2e2;color:#dc2626;border-color:#dc2626}
        .status-btn.late{background:#fef3c7;color:#d97706;border-color:#d97706}
        .status-btn.excused{background:#e2e8f0;color:#4338ca;border-color:#4338ca}
        .btn{padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;border:none;transition:0.2s}
        .btn-primary{background:var(--primary);color:white}
        .btn-primary:hover{background:var(--primary-dark,#1d4ed8)}
        .btn-primary:disabled{opacity:0.6;cursor:not-allowed}
        .btn-outline{background:white;border:1px solid var(--border);color:var(--text)}
        .toast{position:fixed;bottom:24px;right:24px;padding:14px 20px;border-radius:8px;color:white;font-weight:500;transform:translateY(100px);opacity:0;transition:0.3s;z-index:1000}
        .toast.show{transform:translateY(0);opacity:1}
        .toast.success{background:var(--success,#059669)}.toast.error{background:var(--danger,#dc2626)}
        .hidden{display:none !important}
        @media(max-width:768px){.controls{grid-template-columns:1fr}.status-btn{font-size:0.7rem;padding:4px 6px}}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📅 Attendance Management</h1>
        <a href="/multi/views/teacher/dashboard.php" class="btn btn-outline" style="text-decoration:none;padding:8px 16px">← Back</a>
    </div>
    <div class="controls">
        <div class="control-group">
            <label>Class</label>
            <select id="classSelect"><option value="">Select Class</option>
                <?php while($c=$classes->fetch()): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="control-group">
            <label>Date</label>
            <input type="date" id="datePicker" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="control-group" style="display: flex; align-items: flex-end;">
            <button class="btn btn-outline" id="markAllBtn">✅ Mark All Present</button>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" data-tab="daily">Daily Marking</button>
        <button class="tab" data-tab="monthly">Monthly Report</button>
    </div>

    <div id="dailyTab" class="card">
        <table id="dailyTable">
            <thead><tr><th>#</th><th>Name</th><th>Adm. No</th><th>Status</th></tr></thead>
            <tbody id="dailyRows"><tr><td colspan="4" style="text-align:center; color:var(--muted);">Select class & date to load</td></tr></tbody>
        </table>
        <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center">
            <span id="summaryText" style="font-size:0.9rem; color:var(--muted);">Ready to mark</span>
            <button class="btn btn-primary" id="saveBtn" disabled>💾 Save Attendance</button>
        </div>
    </div>

    <div id="monthlyTab" class="card hidden">
        <div class="control-group" style="margin-bottom:12px; max-width:200px;">
            <label>Month</label>
            <input type="month" id="monthPicker" value="<?= date('Y-m') ?>">
        </div>
        <table>
            <thead><tr><th>Name</th><th>Adm. No</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th></tr></thead>
            <tbody id="monthlyRows"><tr><td colspan="6" style="text-align:center; color:var(--muted);">Load monthly report</td></tr></tbody>
        </table>
    </div>
</div>
<div id="toast" class="toast"></div>

<script>
const API = '/multi/api/attendance';
const state = { classId: null, date: null, students: [], statuses: {} };
const els = {
    class: document.getElementById('classSelect'),
    date: document.getElementById('datePicker'),
    markAll: document.getElementById('markAllBtn'),
    dailyRows: document.getElementById('dailyRows'),
    monthlyRows: document.getElementById('monthlyRows'),
    save: document.getElementById('saveBtn'),
    summary: document.getElementById('summaryText'),
    tabs: document.querySelectorAll('.tab'),
    dailyTab: document.getElementById('dailyTab'),
    monthlyTab: document.getElementById('monthlyTab'),
    month: document.getElementById('monthPicker'),
    toast: document.getElementById('toast')
};

// Tab Switching
els.tabs.forEach(t => t.addEventListener('click', () => {
    els.tabs.forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    const isDaily = t.dataset.tab === 'daily';
    els.dailyTab.classList.toggle('hidden', !isDaily);
    els.monthlyTab.classList.toggle('hidden', isDaily);
    if (!isDaily) loadMonthly();
}));

// Load Data Triggers
els.class.addEventListener('change', () => { state.classId = els.class.value; loadDaily(); });
els.date.addEventListener('change', () => { state.date = els.date.value; loadDaily(); });
els.month.addEventListener('change', loadMonthly);

async function loadDaily() {
    if (!state.classId) return;
    state.date = state.date || els.date.value;
    els.dailyRows.innerHTML = '<tr><td colspan="4" style="text-align:center">Loading...</td></tr>';
    els.save.disabled = true;
    try {
        const [studentsRes, statusRes] = await Promise.all([
            fetch(`${API}/load_students.php?class_id=${state.classId}`).then(r => r.json()),
            fetch(`${API}/get_status.php?class_id=${state.classId}&date=${state.date}`).then(r => r.json())
        ]);
        state.students = studentsRes;
        state.statuses = statusRes;
        // Default to present if no status found
        state.students.forEach(s => { if (!state.statuses[s.id]) state.statuses[s.id] = 'present'; });
        renderDaily();
        els.save.disabled = false;
    } catch (e) {
        els.dailyRows.innerHTML = '<tr><td colspan="4" style="text-align:center; color:var(--danger)">Failed to load students.</td></tr>';
        showToast('❌ Failed to load data', 'error');
    }
}

function renderDaily() {
    if (state.students.length === 0) {
        els.dailyRows.innerHTML = '<tr><td colspan="4" style="text-align:center">No active students</td></tr>';
        return;
    }
    els.dailyRows.innerHTML = '';
    state.students.forEach((s, i) => {
        const status = state.statuses[s.id] || 'present';
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i+1}</td><td>${s.name}</td><td>${s.admission_number}</td>
            <td>
                <button class="status-btn ${status==='present'?'active-p':''}" data-sid="${s.id}" data-st="present">Present</button>
                <button class="status-btn ${status==='late'?'active-l':''}" data-sid="${s.id}" data-st="late">Late</button>
                <button class="status-btn ${status==='absent'?'active-a':''}" data-sid="${s.id}" data-st="absent">Absent</button>
                <button class="status-btn ${status==='excused'?'active-e':''}" data-sid="${s.id}" data-st="excused">Excused</button>
            </td>`;
        els.dailyRows.appendChild(tr);
    });
    attachStatusListeners();
    updateSummary();
}

function attachStatusListeners() {
    document.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const sid = e.target.dataset.sid;
            const st = e.target.dataset.st;
            state.statuses[sid] = st;
            const row = e.target.closest('tr');
            row.querySelectorAll('.status-btn').forEach(b => b.className = 'status-btn');
            e.target.classList.add(`active-${st[0]}`);
            updateSummary();
        });
    });
}

els.markAll.addEventListener('click', () => {
    state.students.forEach(s => state.statuses[s.id] = 'present');
    renderDaily();
});

function updateSummary() {
    const counts = {present:0, absent:0, late:0, excused:0};
    Object.values(state.statuses).forEach(s => counts[s]++);
    const total = state.students.length || 1;
    const pct = Math.round((counts.present / total) * 100);
    els.summary.textContent = `📊 ${counts.present} Present | ${counts.absent} Absent | ${counts.late} Late | ${pct}% Present`;
}

els.save.addEventListener('click', async () => {
    if (!state.classId) return showToast('❌ No class selected', 'error');
    if (!state.date) return showToast('❌ No date selected', 'error');
    if (state.students.length === 0) return showToast('❌ No students loaded', 'error');
    
    els.save.disabled = true; 
    els.save.textContent = 'Saving...';
    
    const payload = {
        class_id: state.classId,
        date: state.date,
        records: state.students.map(s => ({
            student_id: s.id,
            status: state.statuses[s.id] || 'present'
        }))
    };
    
    try {
        const res = await fetch(`${API}/save.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        showToast(data.success ? '✅ ' + (data.message || 'Saved') : '❌ ' + (data.error || 'Failed'), data.success ? 'success' : 'error');
    } catch (e) {
        showToast('❌ Network error', 'error');
    } finally {
        els.save.disabled = false; 
        els.save.textContent = '💾 Save Attendance';
    }
});

async function loadMonthly() {
    if (!els.class.value) return;
    els.monthlyRows.innerHTML = '<tr><td colspan="6" style="text-align:center">Loading...</td></tr>';
    try {
        const res = await fetch(`${API}/monthly_report.php?class_id=${els.class.value}&month=${els.month.value}`);
        const data = await res.json();
        els.monthlyRows.innerHTML = '';
        if (data.length === 0) {
            els.monthlyRows.innerHTML = '<tr><td colspan="6" style="text-align:center">No records found</td></tr>';
            return;
        }
        data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.name}</td><td>${r.admission_number}</td>
            <td>${r.present}</td><td style="color:${r.absent>0?'var(--danger)':'inherit'}">${r.absent}</td><td>${r.late}</td>
            <td><strong>${r.attendance_pct}%</strong><div style="height:6px;background:#e2e8f0;border-radius:3px;margin-top:4px"><div style="height:100%;width:${r.attendance_pct}%;background:var(--success);border-radius:3px"></div></div></td>`;
            els.monthlyRows.appendChild(tr);
        });
    } catch (e) {
        els.monthlyRows.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--danger)">Failed</td></tr>';
    }
}

function showToast(msg, type) {
    els.toast.textContent = msg;
    els.toast.className = `toast show ${type}`;
    setTimeout(() => els.toast.classList.remove('show'), 4000);
}
</script>
</body>
</html>