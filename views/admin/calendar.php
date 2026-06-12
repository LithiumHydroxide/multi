<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fetch current calendar settings
$terms = $pdo->query("SELECT * FROM school_calendar WHERE school_id = $schoolId AND year = YEAR(CURDATE()) ORDER BY term")->fetchAll();
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academic Calendar | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--danger:#dc2626}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:900px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:20px}
h2{margin-bottom:16px;font-size:1.2rem;color:var(--primary)}
.form-group{margin-bottom:14px}
label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:6px;font-weight:600}
input,select{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px}
.btn{padding:10px 16px;border-radius:6px;border:none;cursor:pointer;font-weight:600}
.btn-primary{background:var(--primary);color:#fff}
.btn-danger{background:var(--danger);color:#fff}
table{width:100%;border-collapse:collapse}
th,td{padding:12px;text-align:left;border-bottom:1px solid var(--border)}
th{background:#f8fafc;color:var(--muted);font-weight:600}
.badge{padding:4px 8px;border-radius:12px;font-size:0.75rem;font-weight:600}
.badge-active{background:#d1fae5;color:#059669}
.badge-inactive{background:#fee2e2;color:#dc2626}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100}
.modal-content{background:var(--card);padding:24px;border-radius:12px;width:90%;max-width:400px}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1>📅 Academic Calendar</h1>
<a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500">← Back</a>
</div>

<div class="card">
<h2>Term Dates (<?= $currentYear ?>)</h2>
<p style="color:var(--muted);margin-bottom:16px">Define the start and end dates for each term.</p>
<table>
<thead>
<tr>
<th>Term</th>
<th>Start Date</th>
<th>End Date</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="termTable">
<!-- Loaded via JS -->
</tbody>
</table>
</div>
</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
<div class="modal-content">
<h2 style="margin-bottom:16px">Update Term Dates</h2>
<form id="termForm">
<input type="hidden" name="term_id" id="termId">
<div class="form-group">
<label>Start Date</label>
<input type="date" name="start_date" id="startDate" required>
</div>
<div class="form-group">
<label>End Date</label>
<input type="date" name="end_date" id="endDate" required>
</div>
<div class="form-group">
<label>Set as Current Term?</label>
<select name="is_current">
<option value="0">No</option>
<option value="1">Yes</option>
</select>
</div>
<div style="display:flex;gap:10px;margin-top:16px">
<button type="submit" class="btn btn-primary" style="flex:1">Save</button>
<button type="button" onclick="document.getElementById('editModal').style.display='none'" style="flex:1;background:#f1f5f9" class="btn">Cancel</button>
</div>
</form>
</div>
</div>

<script>
// Load Terms
async function loadTerms() {
const res = await fetch(`/multi/api/admin/calendar.php?action=list`);
const terms = await res.json();
const tbody = document.getElementById('termTable');
tbody.innerHTML = '';
terms.forEach(t => {
const statusClass = t.is_current ? 'badge-active' : 'badge-inactive';
const statusText = t.is_current ? 'Active' : 'Inactive';
tbody.innerHTML += `
<tr>
<td><strong>${t.term_name}</strong></td>
<td>${t.start_date}</td>
<td>${t.end_date}</td>
<td><span class="badge ${statusClass}">${statusText}</span></td>
<td>
<button class="btn btn-sm" style="background:#f1f5f9" onclick="openEdit(${t.id}, '${t.start_date}', '${t.end_date}', ${t.is_current})">Edit</button>
</td>
</tr>
`;
});
}

function openEdit(id, start, end, isCurrent) {
document.getElementById('termId').value = id;
document.getElementById('startDate').value = start;
document.getElementById('endDate').value = end;
document.getElementById('editModal').style.display = 'flex';
document.querySelector('#editModal select[name="is_current"]').value = isCurrent;
}

// Save Term
document.getElementById('termForm').addEventListener('submit', async e => {
e.preventDefault();
const formData = new FormData(e.target);
const res = await fetch('/multi/api/admin/calendar.php?action=update', {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify(Object.fromEntries(formData))
});
const result = await res.json();
if(result.success) {
document.getElementById('editModal').style.display = 'none';
loadTerms();
} else {
alert('Failed to update: ' + result.error);
}
});

loadTerms();
</script>
</body>
</html>