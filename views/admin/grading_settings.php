<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Grading Rules | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:800px;margin:0 auto}
.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
.header{display:flex;justify-content:space-between;margin-bottom:24px}
select, input{padding:10px;border:1px solid var(--border);border-radius:6px}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{padding:12px;text-align:left;border-bottom:1px solid var(--border)}
th{background:#f8fafc;color:var(--muted);font-weight:600}
input[type="number"]{width:100px}
.badge{padding:4px 8px;border-radius:12px;font-size:0.8rem;font-weight:600;color:#fff}
.bg-ee{background:#059669}
.bg-me{background:#2563eb}
.bg-ae{background:#d97706}
.bg-be{background:#dc2626}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Grading Rules Configuration</h1>
        <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a">← Back</a>
    </div>

    <div class="card">
        <div style="display:flex;gap:16px;align-items:center;margin-bottom:16px">
            <label><strong>Grade Level:</strong></label>
            <select id="gradeSelect" onchange="loadRules()">
                <option value="7">Grade 7</option>
                <option value="8">Grade 8</option>
                <option value="9" selected>Grade 9 (KJSEA)</option>
            </select>
        </div>

        <div id="rulesTable">
            <!-- Loaded via JS -->
        </div>

        <button onclick="saveRules()" style="margin-top:16px;padding:12px 24px;background:var(--primary);color:#fff;border:none;border-radius:6px;cursor:pointer;font-weight:600">💾 Save All Rules</button>
    </div>
</div>

<script>
async function loadRules() {
    const grade = document.getElementById('gradeSelect').value;
    const res = await fetch(`/multi/api/admin/grading_settings.php?action=list&grade_level=${grade}`);
    const data = await res.json();
    
    const container = document.getElementById('rulesTable');
    if(data.length === 0) {
        container.innerHTML = '<p>No rules defined. Loading defaults...</p>';
        return;
    }

    let html = '<table><thead><tr><th>Level</th><th>Min %</th><th>Max %</th><th>Color</th></tr></thead><tbody>';
    data.forEach(r => {
        let bgClass = r.level_code.startsWith('EE') ? 'bg-ee' : (r.level_code.startsWith('ME') ? 'bg-me' : (r.level_code.startsWith('AE') ? 'bg-ae' : 'bg-be'));
        html += `<tr>
            <td><span class="badge ${bgClass}">${r.level_code}</span></td>
            <td><input type="number" class="min-input" data-code="${r.level_code}" value="${r.min_percentage}"></td>
            <td><input type="number" class="max-input" data-code="${r.level_code}" value="${r.max_percentage}"></td>
            <td><span class="badge ${bgClass}">Preview</span></td>
        </tr>`;
    });
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function saveRules() {
    const grade = document.getElementById('gradeSelect').value;
    const mins = document.querySelectorAll('.min-input');
    const maxs = document.querySelectorAll('.max-input');
    
    let successCount = 0;
    for(let i=0; i<mins.length; i++) {
        const code = mins[i].dataset.code;
        const res = await fetch('/multi/api/admin/grading_settings.php?action=save', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                grade_level: grade,
                level_code: code,
                min_percentage: mins[i].value,
                max_percentage: maxs[i].value
            })
        });
        const d = await res.json();
        if(d.success) successCount++;
    }
    
    alert(`✅ Successfully saved rules for ${successCount} levels!`);
}

// Initial Load
loadRules();
</script>
</body>
</html>