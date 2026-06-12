<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole(['school_admin', 'super_admin']);

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$classes = $pdo->query("SELECT id, name FROM classes WHERE school_id = $schoolId ORDER BY grade_level, name")->fetchAll();
$subjects = $pdo->query("SELECT id, name FROM subjects WHERE school_id = $schoolId ORDER BY name")->fetchAll();
$teachers = $pdo->query("SELECT t.id, u.name FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.school_id = $schoolId ORDER BY u.name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Timetable | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626;--radius:12px}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:20px}.container{max-width:1200px;margin:0 auto}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}.controls{background:var(--card);padding:14px;border-radius:var(--radius);box-shadow:0 2px 4px rgba(0,0,0,0.05);display:flex;gap:12px;align-items:center;margin-bottom:16px;flex-wrap:wrap}select,button{padding:10px;border:1px solid var(--border);border-radius:8px;font-size:0.9rem;background:#fff}button{background:var(--primary);color:#fff;cursor:pointer}button:hover{background:#1d4ed8}button:disabled{opacity:0.6;cursor:not-allowed}.grid-wrapper{background:var(--card);border-radius:var(--radius);box-shadow:0 2px 4px rgba(0,0,0,0.05);overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:800px}th,td{border:1px solid var(--border);padding:10px;text-align:center;vertical-align:middle}th{background:#f1f5f9;color:var(--muted);font-weight:600;font-size:0.85rem}.cell{min-width:140px;min-height:60px;cursor:pointer;transition:0.2s}.cell:hover{background:#f8fafc}.cell .subject{font-weight:600;color:var(--primary);font-size:0.9rem}.cell .teacher{font-size:0.75rem;color:var(--muted)}.cell .room{font-size:0.7rem;color:#94a3b8}.cell.empty{color:var(--muted);opacity:0.5}.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}.modal-content{background:var(--card);padding:24px;border-radius:var(--radius);width:90%;max-width:400px}.form-group{margin-bottom:14px}label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px}input,select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px}.modal-actions{display:flex;gap:10px;margin-top:16px}.modal-actions button{flex:1;padding:10px;border-radius:6px;cursor:pointer;font-weight:500}.btn-save{background:var(--primary);color:#fff;border:none}.btn-delete{background:#fee2e2;color:var(--danger);border:1px solid var(--danger)}.btn-cancel{background:#f1f5f9;border:1px solid var(--border)}.conflict-msg{background:#fee2e2;color:var(--danger);padding:8px;border-radius:6px;font-size:0.8rem;margin-bottom:10px;display:none}</style></head>
<body>
<div class="container">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <h1 style="margin:0">🗓️ Manage Timetable <span style="font-size:0.8rem;background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:12px">ADMIN</span></h1>
            <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
        </div>
    </div>
    <div class="controls">
        <select id="classSelect"><option value="">Select Class to Edit</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
        <button onclick="togglePeriodConfig()" style="background:#f1f5f9;color:var(--text)">⚙️ Adjust Periods</button>
        <span id="statusText" style="color:var(--muted);font-size:0.85rem;">Ready</span>
    </div>
    <div id="periodConfig" style="display:none;background:var(--card);padding:16px;border-radius:12px;margin-bottom:16px">
        <h3 style="margin-bottom:8px">Define Periods</h3>
        <div id="periodInputs" style="display:flex;gap:10px;flex-wrap:wrap"></div>
        <button onclick="savePeriods()" style="margin-top:12px;padding:10px 16px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer">Apply Schedule</button>
    </div>
    <div class="grid-wrapper"><table id="timetableTable"><thead id="thead"></thead><tbody id="tbody"></tbody></table></div>
</div>

<div class="modal" id="assignModal">
    <div class="modal-content">
        <h3 style="margin-bottom:12px">Edit Slot</h3>
        <div id="conflictBox" class="conflict-msg"></div>
        <div class="form-group"><label>Subject</label><select id="modalSubject"><option value="">None</option><?php foreach($subjects as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Teacher</label><select id="modalTeacher"><option value="">None</option><?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Room</label><input type="text" id="modalRoom" placeholder="e.g. Lab 3, Room 12"></div>
        <div class="modal-actions">
            <button class="btn-cancel" onclick="closeModal()">Cancel</button>
            <button class="btn-delete" id="btnDelete" onclick="deleteEntry()">Remove</button>
            <button class="btn-save" onclick="saveEntry()">💾 Save</button>
        </div>
    </div>
</div>

<script>
const API = '/multi/api/timetable';
let slots = [], entries = [], currentSlot = null, currentClass = null;
document.getElementById('classSelect').addEventListener('change', e => { currentClass = e.target.value; loadTimetable(); });

async function loadTimetable() {
    if(!currentClass) return;
    document.getElementById('statusText').textContent = 'Loading...';
    try {
        const [slotsRes, entriesRes] = await Promise.all([
            fetch(`${API}/slots.php`).then(r => r.json()),
            fetch(`${API}/entries.php?class_id=${currentClass}`).then(r => r.json())
        ]);
        slots = slotsRes; entries = entriesRes;
        renderGrid();
        document.getElementById('statusText').textContent = 'Edit Mode';
    } catch(e) { document.getElementById('statusText').textContent = 'Error loading data'; }
}

function renderGrid() {
    if(!slots.length) return;
    const days = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
    document.getElementById('thead').innerHTML = `<tr><th style="width:100px">Period</th>${days.map(d => `<th>${d}</th>`).join('')}</tr>`;
    const matrix = {}; entries.forEach(e => matrix[`${e.slot_id}`] = e);
    document.getElementById('tbody').innerHTML = '';
    const periods = [...new Set(slots.map(s => s.period_number))].sort((a,b)=>a-b);
    periods.forEach(p => {
        const tr = document.createElement('tr');
        const timeSlots = slots.filter(s => s.period_number === p);
        tr.innerHTML = `<td><strong>P${p}</strong><br><span style="font-size:0.75rem;color:var(--muted)">${timeSlots[0].start_time} - ${timeSlots[0].end_time}</span></td>`;
        timeSlots.forEach(slot => {
            const entry = matrix[slot.id];
            const td = document.createElement('td');
            td.className = 'cell';
            td.innerHTML = entry ? `<div class="subject">${entry.subject_name||'Unknown'}</div><div class="teacher">${entry.teacher_name||'TBA'}</div><div class="room">${entry.room_number||''}</div>` : `<div class="cell empty">+ Add</div>`;
            td.onclick = () => openModal(slot.id, entry);
            tr.appendChild(td);
        });
        document.getElementById('tbody').appendChild(tr);
    });
}

function openModal(slotId, entry) {
    currentSlot = slotId;
    document.getElementById('modalSubject').value = entry?.subject_id || '';
    document.getElementById('modalTeacher').value = entry?.teacher_id || '';
    document.getElementById('modalRoom').value = entry?.room_number || '';
    document.getElementById('conflictBox').style.display = 'none';
    document.getElementById('btnDelete').style.display = entry ? 'block' : 'none';
    document.getElementById('assignModal').style.display = 'flex';
}
function closeModal() { document.getElementById('assignModal').style.display = 'none'; }

// Replace your saveEntry() function with this:
async function saveEntry() {
    const btn = document.querySelector('.btn-save'); btn.disabled = true; btn.textContent = 'Saving...';
    const payload = { 
        class_id: currentClass, slot_id: currentSlot, entry_id: currentEntry?.entry_id || null,
        subject_id: document.getElementById('modalSubject').value,
        teacher_id: document.getElementById('modalTeacher').value,
        room: document.getElementById('modalRoom').value 
    };
    try {
        const res = await fetch('/multi/api/timetable/entries.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
        const data = await res.json();
        if(data.success) { closeModal(); loadTimetable(); }
        else { document.getElementById('conflictBox').textContent = data.conflicts?.join('<br>') || data.error; document.getElementById('conflictBox').style.display = 'block'; }
    } catch(e) { alert('Network error'); }
    finally { btn.disabled = false; btn.textContent = '💾 Save'; }
}
// Add this to top of openModal() to track current entry ID:
currentEntry = entry; // Store entry data globally for use in saveEntry()


async function deleteEntry() {
    if(!confirm('Remove this assignment?')) return;
    const payload = { class_id: currentClass, slot_id: currentSlot, delete: true };
    await fetch(`${API}/entries.php`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    closeModal(); loadTimetable();
}

// Period Config
function togglePeriodConfig() {
    const box = document.getElementById('periodConfig');
    box.style.display = box.style.display === 'none' ? 'block' : 'none';
    if(box.style.display === 'block') {
        fetch(`${API}/settings.php`).then(r=>r.json()).then(slots=>{
            const days=['Monday','Tuesday','Wednesday','Thursday','Friday'];
            document.getElementById('periodInputs').innerHTML = `
                <label>Days: <input id="pDays" value="${days.join(',')}" style="width:300px;padding:8px"></label>
                <label>Count: <input id="pCount" type="number" value="${Math.max(...slots.map(s=>s.period_number))}" style="width:60px;padding:8px"></label>
                <label>Start: <input id="pStart" type="time" value="${slots[0]?.start_time||'08:00'}" style="padding:8px"></label>
                <label>Min: <input id="pDur" type="number" value="40" style="width:60px;padding:8px"></label>`;
        });
    }
}
async function savePeriods() {
    const days = document.getElementById('pDays').value.split(',').map(d=>d.trim());
    const count = parseInt(document.getElementById('pCount').value);
    let time = document.getElementById('pStart').value.split(':').map(Number);
    const dur = parseInt(document.getElementById('pDur').value);
    const slots = [];
    days.forEach(d=>{
        for(let i=1;i<=count;i++){
            let h=time[0], m=time[1]+dur; if(m>=60){h++;m-=60;}
            slots.push({day:d, period:i, start:`${String(time[0]).padStart(2,'0')}:${String(time[1]).padStart(2,'0')}`, end:`${String(h).padStart(2,'0')}:${String(m%60).padStart(2,'0')}`});
            time[0]=h; time[1]=m%60;
        }
    });
    await fetch(`${API}/settings.php`, {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({slots})});
    togglePeriodConfig(); loadTimetable();
}
</script></body></html>