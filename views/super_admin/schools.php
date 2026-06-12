<?php
require_once __DIR__ . '/../../config.php';
SuperAdminMiddleware::requireSuperAdmin();
$pdo = getDBConnection();

$schools = $pdo->query("
    SELECT s.*, ss.status as sub_status, sp.name as plan_name, sp.price as plan_price,
           (SELECT COUNT(*) FROM students st WHERE st.school_id = s.id AND st.status='active') as student_count
    FROM schools s
    LEFT JOIN school_subscriptions ss ON s.id = ss.school_id AND ss.status IN ('active','trial')
    LEFT JOIN subscription_plans sp ON ss.plan_id = sp.id
    ORDER BY s.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manage Schools | CBC Manager</title>
<?= getBrandingCSS(getAuthenticatedSchoolId()) ?>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:1200px;margin:0 auto}.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}table{width:100%;background:var(--card);border-radius:12px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05)}th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:0.9rem}th{background:#f1f5f9;font-weight:600;color:var(--muted)}.badge{padding:4px 8px;border-radius:20px;font-size:0.75rem;font-weight:600}.active{background:#d1fae5;color:#059669}.trial{background:#fef3c7;color:#d97706}.suspended{background:#fee2e2;color:#dc2626}.expired{background:#f1f5f9;color:#64748b}button{padding:8px 12px;border-radius:6px;border:none;cursor:pointer;font-size:0.85rem;margin-right:6px}.btn-sm{background:#f1f5f9;color:var(--text)}.btn-primary{background:var(--primary);color:#fff}.btn-danger{background:#dc2626;color:#fff}.search{display:flex;gap:10px;margin-bottom:16px}.search input,.search select{padding:10px;border:1px solid var(--border);border-radius:8px}.search input{flex:1}
    .modal-content { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.row { display: flex; gap: 12px; flex-wrap: wrap; }
.row > * { flex: 1; min-width: 200px; }
label { display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 4px; }
input, select { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30,64,175,0.1); }
</style></head><body>
<div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;flex-wrap:wrap">
    <div style="display:flex;gap:12px;align-items:center">
        <h1 style="margin:0">🏫 Platform Schools</h1>
        <span style="color:var(--muted);white-space:nowrap"><?= count($schools) ?> total schools</span>
    </div>
    <a href="/multi/views/super_admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
</div>
<div class="search"><input type="text" id="search" placeholder="Search by name, email, or county..."><select id="statusFilter"><option value="">All Statuses</option><option value="active">Active</option><option value="trial">Trial</option><option value="suspended">Suspended</option></select></div>
<div style="margin-bottom:16px">
    <button onclick="document.getElementById('admitModal').style.display='flex'" class="btn-primary" style="padding:12px 20px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600">
        ➕ Admit New School
    </button>
</div>

<!-- Admit School Modal -->
<div class="modal" id="admitModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);align-items:center;justify-content:center;z-index:100">
    <div class="modal-content" style="background:#fff;padding:28px;border-radius:16px;width:90%;max-width:600px;max-height:90vh;overflow-y:auto">
        <h2 style="margin-bottom:16px">🏫 Admit New School</h2>
        <form id="admitForm">
            <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">School Name *</label>
                    <input name="school_name" required style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">School Code</label>
                    <input name="school_code" placeholder="e.g. GFA-001" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                </div>
            </div>
            <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px">
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">County</label>
                    <input name="county" placeholder="e.g. Nairobi" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Contact Phone</label>
                    <input name="contact_phone" placeholder="+254..." style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                </div>
            </div>
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Admin Email *</label>
                <input type="email" name="admin_email" required placeholder="admin@school.ke" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
            </div>
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Admin Name *</label>
                <input name="admin_name" required placeholder="e.g. John Doe" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
            </div>
            <div style="margin-bottom:12px">
                <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Admin Password *</label>
                <input type="password" name="admin_password" required minlength="8" placeholder="Min 8 characters" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
            </div>
            <div class="row" style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px">
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Subscription Plan *</label>
                    <select name="plan_id" required style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                        <option value="1">Free (50 students)</option>
                        <option value="2">Standard - KES 5,000/term (200 students)</option>
                        <option value="3" selected>Premium - KES 15,000/term (500 students)</option>
                    </select>
                </div>
                <div style="flex:1;min-width:200px">
                    <label style="display:block;font-size:0.85rem;color:#64748b;margin-bottom:4px">Start Onboarding?</label>
                    <select name="start_onboarding" style="width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px">
                        <option value="1" selected>Yes - Redirect admin to setup wizard</option>
                        <option value="0">No - Skip to dashboard</option>
                    </select>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <button type="button" onclick="document.getElementById('admitModal').style.display='none'" style="flex:1;padding:12px;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer">Cancel</button>
                <button type="submit" style="flex:1;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600">🚀 Admit School</button>
            </div>
        </form>
    </div>
</div>
<table><thead><tr><th>School</th><th>County</th><th>Plan</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead><tbody id="rows">
<?php foreach($schools as $s): $badge = $s['sub_status'] ?: 'expired'; ?>
<tr data-search="<?= strtolower($s['name'].' '.$s['contact_email'].' '.$s['county']) ?>" data-status="<?= $badge ?>">
<td><strong><?= htmlspecialchars($s['name']) ?></strong><br><span style="font-size:0.8rem;color:var(--muted)"><?= htmlspecialchars($s['contact_email']) ?></span></td>
<td><?= $s['county'] ?: '-' ?></td>
<td><?= $s['plan_name'] ?: 'None' ?> <span style="font-size:0.8rem;color:var(--muted)">(KES <?= number_format($s['plan_price']?:0) ?>)</span></td>
<td><?= $s['student_count'] ?></td>
<td><span class="badge <?= $badge ?>"><?= strtoupper($badge) ?></span></td>
<td><button class="btn-sm" onclick="impersonate(<?= $s['id'] ?>)">👁️ Impersonate</button><button class="btn-sm <?= $s['status']=='active'?'btn-danger':'btn-primary' ?>" onclick="toggleSchool(<?= $s['id'] ?>, '<?= $s['status'] ?>')"><?= $s['status']=='active'?'Suspend':'Activate' ?></button></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<script>
document.getElementById('search').addEventListener('input', filter);
document.getElementById('statusFilter').addEventListener('change', filter);
function filter(){const t=document.getElementById('search').value.toLowerCase(),f=document.getElementById('statusFilter').value;document.querySelectorAll('#rows tr').forEach(r=>{const m=r.dataset.search.includes(t),s=!f||r.dataset.status===f;r.style.display=m&&s?'':'none';})}
async function impersonate(id){if(!confirm('Impersonate this school as admin?'))return;const res=await fetch('/multi/api/admin/impersonate.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({school_id:id})});const d=await res.json();if(d.success){alert('Now viewing: '+d.school_name);location.href='/multi/views/admin/dashboard.php';}else alert('Failed: '+d.error);}
async function toggleSchool(id,current){const next=current==='active'?'suspended':'active';if(!confirm(`Set status to "${next}"?`))return;await fetch('/multi/api/admin/update_school_status.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({school_id:id,status:next})});location.reload();}
document.getElementById('admitForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = 'Creating...';
    
    try {
        const res = await fetch('/multi/api/super_admin/admit_school.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(Object.fromEntries(new FormData(e.target)))
        });
        const d = await res.json();
        
        if (d.success) {
            alert(`✅ School "${d.school_name}" admitted successfully!\n\nAdmin: ${d.admin_email}\nPassword: [sent separately]\n\n${d.start_onboarding ? 'Redirecting admin to onboarding wizard...' : 'School ready for immediate use.'}`);
            location.reload();
        } else {
            alert('❌ ' + (d.error || 'Failed to admit school'));
        }
    } catch (err) {
        alert('❌ Network error: ' + err.message);
    } finally {
        btn.disabled = false; btn.textContent = '🚀 Admit School';
    }
});
</script></body></html>