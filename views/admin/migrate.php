<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('super_admin'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Database Migration | CBC Manager</title>
<style>:root{--primary:#1e40af;--success:#059669;--danger:#dc2626;--warning:#f59e0b}*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',system-ui,sans-serif}body{background:linear-gradient(135deg, var(--primary) 0%, #0c3a8f 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}.container{background:#fff;padding:40px;border-radius:12px;box-shadow:0 20px 60px rgba(0,0,0,0.3);max-width:600px;width:100%}.header{text-align:center;margin-bottom:32px}.header h1{color:var(--primary);margin-bottom:8px}.header p{color:#64748b}.status-box{padding:20px;border-radius:8px;margin-bottom:24px;border-left:4px solid}.status-box.pending{background:#fef3c7;border-color:var(--warning)}.status-box.ready{background:#d1fae5;border-color:var(--success)}.status-box strong{display:block;margin-bottom:8px}.columns-list{margin-top:12px;padding:12px;background:rgba(0,0,0,0.05);border-radius:6px;font-family:monospace;font-size:0.85rem;line-height:1.6}.button-group{display:flex;gap:12px;margin-bottom:24px}.btn{padding:12px 24px;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:0.2s;font-size:0.95rem}.btn-primary{background:var(--primary);color:#fff}.btn-primary:hover{background:#1d4ed8;transform:translateY(-2px)}.btn-primary:disabled{opacity:0.5;cursor:not-allowed;transform:none}.btn-secondary{background:#f1f5f9;color:var(--primary)}.message{padding:16px;border-radius:8px;margin-bottom:16px;display:none}.message.show{display:block}.message.success{background:#d1fae5;color:#065f46;border-left:4px solid var(--success)}.message.danger{background:#fee2e2;color:#7f1d1d;border-left:4px solid var(--danger)}.steps{margin-top:24px;padding:20px;background:#f8fafc;border-radius:8px}.steps h3{color:var(--primary);margin-bottom:12px}.steps ol{margin-left:20px;line-height:1.8;color:#475569}.steps li{margin-bottom:10px}.code-block{background:#1f2937;color:#10b981;padding:12px;border-radius:6px;margin-top:12px;font-family:monospace;font-size:0.85rem;overflow-x:auto;line-height:1.4}table{width:100%;margin-top:12px;border-collapse:collapse}th,td{padding:8px;text-align:left;border-bottom:1px solid #e2e8f0;font-size:0.85rem}th{background:#f1f5f9;font-weight:600}</style>
</head>
<body>
<div class="container">
    <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:32px;flex-wrap:wrap">
        <div>
        <h1 style="margin-bottom:8px">🔧 Database Migration Tool</h1>
        <p style="color:#64748b">Class Leadership Feature Setup</p>
        </div>
        <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>

    <div id="statusBox" class="status-box pending">
        <strong id="statusTitle">⏳ Checking database status...</strong>
        <p id="statusMessage"></p>
    </div>

    <div id="messageBox" class="message"></div>

    <div class="button-group">
        <button class="btn btn-primary" onclick="checkStatus()">🔄 Check Status</button>
        <button class="btn btn-primary" id="migrateBtn" onclick="runMigration()" disabled>▶️ Run Migration</button>
    </div>

    <div id="detailsBox" style="display:none;">
        <h3 style="color:var(--primary);margin-bottom:12px">📋 Missing Columns</h3>
        <table id="missingTable">
            <thead><tr><th>Column Name</th><th>Type</th><th>Purpose</th></tr></thead>
            <tbody id="missingRows"></tbody>
        </table>

        <div class="steps">
            <h3>What This Migration Does:</h3>
            <ol>
                <li><strong>Adds class_teacher_id</strong> - Stores the primary teacher for the class</li>
                <li><strong>Adds assistant_teacher_id</strong> - Stores the deputy/assistant teacher</li>
                <li><strong>Adds prefect_id</strong> - Stores the class prefect (student leader)</li>
                <li><strong>Creates foreign keys</strong> - Ensures data integrity</li>
            </ol>
        </div>
    </div>

    <div id="successBox" style="display:none;margin-top:24px;padding:20px;background:#d1fae5;border-radius:8px">
        <h3 style="color:var(--success);margin-bottom:12px">✅ Migration Complete!</h3>
        <p style="color:#065f46;margin-bottom:12px">Your database has been successfully updated. You can now:</p>
        <ul style="margin-left:20px;color:#065f46;line-height:1.8">
            <li>Assign class teachers to classes</li>
            <li>Assign assistant teachers to classes</li>
            <li>Assign class prefects (students)</li>
            <li>Map subjects to specific teachers</li>
        </ul>
        <p style="margin-top:16px;color:#065f46"><a href="/multi/views/admin/class_assignments.php" style="color:var(--success);font-weight:600;text-decoration:none">→ Go to Class Management</a></p>
    </div>
</div>

<script>
let migrationStatus = null;

async function checkStatus() {
    const btn = document.querySelector('button');
    btn.disabled = true;
    btn.textContent = '⏳ Checking...';
    
    try {
        const res = await fetch('/multi/api/admin/migrate.php?action=status');
        migrationStatus = await res.json();
        
        const box = document.getElementById('statusBox');
        const details = document.getElementById('detailsBox');
        const migrateBtn = document.getElementById('migrateBtn');
        
        if (!migrationStatus.needs_migration) {
            box.className = 'status-box ready';
            box.innerHTML = '<strong style="color:var(--success)">✅ Database is up to date!</strong><p>All required columns are present.</p>';
            details.style.display = 'none';
            migrateBtn.disabled = true;
            
            // Show success box
            document.getElementById('successBox').style.display = 'block';
        } else {
            box.className = 'status-box pending';
            box.innerHTML = '<strong style="color:var(--warning)">⚠️ Migration Required</strong><p>The following columns are missing:</p><div class="columns-list">' + migrationStatus.missing_columns.join('<br>') + '</div>';
            details.style.display = 'block';
            migrateBtn.disabled = false;
            
            // Show details table
            const columnInfo = {
                'class_teacher_id': 'INT(10) UNSIGNED',
                'assistant_teacher_id': 'INT(10) UNSIGNED',
                'prefect_id': 'INT(10) UNSIGNED'
            };
            const tbody = document.getElementById('missingRows');
            tbody.innerHTML = migrationStatus.missing_columns.map(col => `
                <tr>
                    <td><strong>${col}</strong></td>
                    <td>${columnInfo[col] || 'Unknown'}</td>
                    <td>${col === 'class_teacher_id' ? 'Class teacher assignment' : col === 'assistant_teacher_id' ? 'Assistant teacher assignment' : 'Class prefect assignment'}</td>
                </tr>
            `).join('');
        }
    } catch(e) {
        showMessage('Error checking status: ' + e.message, 'danger');
    }
    
    btn.disabled = false;
    btn.textContent = '🔄 Check Status';
}

async function runMigration() {
    if (!confirm('Run database migration? This will add required columns to the classes table.')) return;
    
    const btn = document.getElementById('migrateBtn');
    btn.disabled = true;
    btn.textContent = '⏳ Running migration...';
    
    try {
        const res = await fetch('/multi/api/admin/migrate.php?action=run_migration');
        const result = await res.json();
        
        if (result.success) {
            showMessage('✅ Migration successful! ' + result.message, 'success');
            document.getElementById('statusBox').style.display = 'none';
            document.getElementById('detailsBox').style.display = 'none';
            document.getElementById('successBox').style.display = 'block';
            document.querySelector('.button-group').style.display = 'none';
            
            console.log('Changes applied:', result.executed);
        } else {
            showMessage('❌ Migration failed: ' + result.error, 'danger');
            btn.disabled = false;
            btn.textContent = '▶️ Run Migration';
        }
    } catch(e) {
        showMessage('Error: ' + e.message, 'danger');
        btn.disabled = false;
        btn.textContent = '▶️ Run Migration';
    }
}

function showMessage(text, type) {
    const box = document.getElementById('messageBox');
    box.className = 'message show message-' + type;
    box.textContent = text;
}

// Check status on load
window.addEventListener('load', checkStatus);
</script>
</body></html>
