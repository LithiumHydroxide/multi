<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Staff Management | CBC Manager</title>
<?= getBrandingCSS(getAuthenticatedSchoolId()) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:1100px;margin:0 auto}
.header{display:flex;justify-content:space-between;margin-bottom:16px}
table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:24px}
th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}
th{background:#f1f5f9;font-weight:600;color:var(--muted)}
.badge{padding:4px 8px;border-radius:20px;font-size:0.75rem;font-weight:600}
.teaching{background:#dbeafe;color:#1e40af}
.non_teaching{background:#fef3c7;color:#d97706}
.btn{padding:8px 12px;border-radius:6px;border:none;cursor:pointer;font-size:0.85rem}
.btn-primary{background:var(--primary);color:#fff}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}
.modal-content{background:var(--card);padding:24px;border-radius:12px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto}
.form-group{margin-bottom:12px}
.form-group label{display:block;font-size:0.8rem;color:var(--muted);margin-bottom:4px}
.form-group input,.form-group select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px}
.hidden{display:none}
.row{display:flex;gap:10px}
.row>*{flex:1}
</style></head>
<body>
<div class="container">
<div class="header"><h1>👥 Staff Management</h1><button onclick="document.getElementById('addModal').style.display='flex'" class="btn-primary">+ Add Staff</button></div>
<table><thead><tr><th>Name</th><th>Type</th><th>Role</th><th>Contact / TSC</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead><tbody id="rows"><tr><td colspan="7" style="text-align:center">Loading...</td></tr></tbody></table>
</div>

<!-- Add Staff Modal -->
<div class="modal" id="addModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Add Staff Member</h2>
<form id="staffForm">
<div class="form-group"><label>Full Name</label><input name="name" required></div>
<div class="form-group">
<label>Staff Category</label>
<select name="staff_type" id="staffType" onchange="toggleFields()">
<option value="teaching">Teaching Staff (Requires Login)</option>
<option value="non_teaching">Non-Teaching Staff (Registry Only)</option>
</select>
</div>
<div class="form-group">
<label>Job Role / Specialization</label>
<select name="job_role" id="jobRole" required>
<option value="">Select Role</option>
<optgroup label="Teaching" id="teachingRoles" style="display:block">
<option value="Teacher">Teacher</option>
<option value="HOD">Head of Department</option>
<option value="Deputy Principal">Deputy Principal</option>
</optgroup>
<optgroup label="Support & Operations" id="supportRoles" style="display:none">
<option value="Driver">Driver</option><option value="Head Cook">Head Cook</option>
<option value="School Nurse">School Nurse</option><option value="Security Guard">Security Guard</option>
<option value="Electrician">Electrician</option><option value="Bursar">Bursar</option>
<option value="Cleaner">Cleaner</option><option value="Groundsman">Groundsman</option>
</optgroup>
</select>
</div>

<!-- Teaching Fields -->
<div id="teachingFields">
<div class="form-group"><label>Email Address *</label><input type="email" name="email" id="emailInput" required></div>
<div class="form-group"><label>Login Password (8+ chars) *</label><input type="password" name="password" id="passInput" required minlength="8"></div>
<div class="row">
<div class="form-group"><label>TSC Number</label><input name="tsc_number"></div>
<div class="form-group"><label>Subjects</label><input name="details[subjects]" placeholder="e.g. Math, Science"></div>
</div>
</div>

<!-- Non-Teaching Fields -->
<div id="nonTeachingFields" class="hidden">
<div class="row">
<div class="form-group"><label>Phone Number</label><input name="phone" placeholder="+254..."></div>
<div class="form-group"><label>Emergency Contact</label><input name="emergency_contact"></div>
</div>
<div class="form-group" id="roleSpecificFields"></div>
<div class="row">
<div class="form-group"><label>Contract Type</label><select name="contract_type"><option value="contract">Contract</option><option value="permanent">Permanent</option><option value="part-time">Part-Time</option></select></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
</div>
</div>

<div style="display:flex;gap:10px;margin-top:16px">
<button type="submit" style="flex:1;background:var(--primary);color:#fff;padding:10px;border-radius:6px;border:none;cursor:pointer">Create</button>
<button type="button" onclick="document.getElementById('addModal').style.display='none'" style="flex:1;background:#f1f5f9;padding:10px;border-radius:6px;border:none;cursor:pointer">Cancel</button>
</div>
</form>
</div>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('staffType').value;
    document.getElementById('teachingFields').classList.toggle('hidden', type !== 'teaching');
    document.getElementById('nonTeachingFields').classList.toggle('hidden', type !== 'non_teaching');
    document.getElementById('teachingRoles').style.display = type === 'teaching' ? 'block' : 'none';
    document.getElementById('supportRoles').style.display = type !== 'teaching' ? 'block' : 'none';
    document.getElementById('emailInput').required = type === 'teaching';
    document.getElementById('passInput').required = type === 'teaching';
    document.getElementById('roleSpecificFields').innerHTML = '';
}

function showRoleFields() {
    const role = document.getElementById('jobRole').value;
    const container = document.getElementById('roleSpecificFields');
    container.innerHTML = '';
    const fields = {
        'Driver': `<div class="row"><div class="form-group"><label>License No</label><input name="details[license_no]"></div><div class="form-group"><label>Bus Number</label><input name="details[bus_number]"></div></div><div class="form-group"><label>Route</label><input name="details[route]"></div>`,
        'School Nurse': `<div class="form-group"><label>Nursing License</label><input name="details[license_no]"></div><div class="form-group"><label>Certifications</label><input name="details[certifications]" placeholder="e.g. First Aid & Trauma"></div>`,
        'Bursar': `<div class="form-group"><label>CPA/KASNEB Cert</label><input name="details[certifications]"></div>`,
        'Security Guard': `<div class="form-group"><label>Security License</label><input name="details[license_no]"></div><div class="form-group"><label>Shift</label><select name="details[shift]"><option>Day</option><option>Night</option><option>Rotating</option></select></div>`
    };
    container.innerHTML = fields[role] || '';
}
document.getElementById('jobRole').addEventListener('change', showRoleFields);

async function loadStaff() {
    const res = await fetch('/multi/api/admin/staff.php?action=list');
    const data = await res.json();
    const tb = document.getElementById('rows');
    tb.innerHTML = '';
    data.forEach(s => {
        const badgeClass = s.type === 'teaching' ? 'teaching' : 'non_teaching';
        const contact = s.type === 'teaching' ? (s.tsc_number || s.email || '-') : (s.phone || '-');
        const details = s.details ? Object.entries(s.details).filter(([k,v])=>v).map(([k,v])=>`<strong>${k}:</strong> ${v}`).join('<br>') : '-';
        tb.innerHTML += `<tr>
            <td>${s.name}</td>
            <td><span class="badge ${badgeClass}">${s.type.replace('_',' ')}</span></td>
            <td>${s.job_role}</td>
            <td>${contact}</td>
            <td style="font-size:0.8rem">${details}</td>
            <td>${s.status}</td>
            <td><button class="btn" onclick="tog(${s.id}, '${s.type}')">${s.status==='active'?'Disable':'Enable'}</button></td>
        </tr>`;
    });
}

document.getElementById('staffForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = Object.fromEntries(fd);
    // Clean details object
    const details = {};
    for (const key in data) {
        if (key.startsWith('details[')) {
            details[key.replace('details[', '').replace(']', '')] = data[key];
            delete data[key];
        }
    }
    data.details = details;
    const res = await fetch('/multi/api/admin/staff.php?action=create', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
    });
    const d = await res.json();
    if (d.success) { location.reload(); } else { alert('❌ ' + d.error); }
});

async function tog(id, type) {
    await fetch('/multi/api/admin/staff.php?action=toggle', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id, type})
    });
    loadStaff();
}
loadStaff();
</script></body></html>