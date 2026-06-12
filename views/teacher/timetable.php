<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireSchoolScope();

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$teacherId = $pdo->query("SELECT id FROM teachers WHERE user_id = " . getAuthenticatedUserId())->fetchColumn();
$assignedClasses = $pdo->prepare("SELECT c.id, c.name FROM class_subject_teacher cst JOIN classes c ON cst.class_id = c.id WHERE cst.teacher_id = ? GROUP BY c.id ORDER BY c.grade_level, c.name");
$assignedClasses->execute([$teacherId]);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>My Timetable | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--warning:#f59e0b}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:20px}.container{max-width:1100px;margin:0 auto}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.controls{background:var(--card);padding:12px 16px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:16px}select{padding:10px;border:1px solid var(--border);border-radius:8px;font-size:0.9rem;background:#fff}table{width:100%;border-collapse:collapse;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}th,td{border:1px solid var(--border);padding:10px;text-align:center;vertical-align:middle;font-size:0.9rem}th{background:#f1f5f9;color:var(--muted);font-weight:600}.cell{padding:8px}.subj{font-weight:600;color:var(--primary)}.tchr{font-size:0.75rem;color:var(--muted)}.room{font-size:0.7rem;color:#94a3b8}.pending{background:#fffbeb;border:2px dashed var(--warning);border-radius:12px;padding:40px;text-align:center;color:#92400e;margin:20px 0}.hidden{display:none}</style></head>
<body>
<div class="container">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <h1 style="margin:0">🗓️ My Timetable</h1>
        <a href="/multi/views/teacher/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>
    <div class="controls">
        <select id="classSelect"><option value="">Select Assigned Class</option><?php while($c=$assignedClasses->fetch()): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endwhile; ?></select>
        <span id="status" style="color:var(--muted);font-size:0.85rem">Select a class to view</span>
    </div>
    <div id="pendingMsg" class="pending hidden">⏳ Timetable is currently pending setup by administration.<br><small>Please check back later or contact your school admin.</small></div>
    <div class="grid-wrapper" id="gridWrapper" class="hidden"><table id="timetableTable"><thead id="thead"></thead><tbody id="tbody"></tbody></table></div>
</div>
<script>
const API = '/multi/api/timetable';
let slots = [], currentClass = null;
document.getElementById('classSelect').addEventListener('change', e => { currentClass = e.target.value; load(); });

async function load() {
    if(!currentClass) return;
    document.getElementById('status').textContent = 'Loading...';
    document.getElementById('pendingMsg').classList.add('hidden');
    document.getElementById('gridWrapper').classList.add('hidden');
    try {
        const [slotsRes, entriesRes] = await Promise.all([
            fetch(`${API}/slots.php`).then(r=>r.json()),
            fetch(`${API}/entries.php?class_id=${currentClass}`).then(r=>r.json())
        ]);
        slots = slotsRes;
        if(entriesRes.length === 0 || (Array.isArray(entriesRes) && entriesRes.error)) {
            document.getElementById('pendingMsg').classList.remove('hidden');
            document.getElementById('status').textContent = 'Timetable pending';
            return;
        }
        renderGrid(entriesRes);
        document.getElementById('status').textContent = 'View Mode (Read-Only)';
    } catch(e) { document.getElementById('status').textContent = 'Error loading'; }
}

function renderGrid(entries) {
    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    document.getElementById('thead').innerHTML = `<tr><th style="width:90px">Period</th>${days.map(d=>`<th>${d}</th>`).join('')}</tr>`;
    const matrix = {}; entries.forEach(e => matrix[e.slot_id] = e);
    document.getElementById('tbody').innerHTML = '';
    const periods = [...new Set(slots.map(s=>s.period_number))].sort((a,b)=>a-b);
    periods.forEach(p => {
        const tr = document.createElement('tr');
        const timeSlots = slots.filter(s=>s.period_number===p);
        tr.innerHTML = `<td><strong>${p}</strong><br><span style="font-size:0.75rem;color:var(--muted)">${timeSlots[0]?.start_time||''}</span></td>`;
        timeSlots.forEach(slot => {
            const e = matrix[slot.id];
            const td = document.createElement('td');
            td.className = 'cell';
            td.innerHTML = e ? `<div class="subj">${e.subject_name||'-'}</div><div class="tchr">${e.teacher_name||'TBA'}</div><div class="room">${e.room_number||''}</div>` : `<span style="color:var(--muted)">-</span>`;
            tr.appendChild(td);
        });
        document.getElementById('tbody').appendChild(tr);
    });
    document.getElementById('gridWrapper').classList.remove('hidden');
}
</script></body></html>