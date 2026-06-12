<?php require_once '../../config.php'; AuthMiddleware::requireAuth(); AuthMiddleware::requireRole('school_admin'); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>School Setup & Branding | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);padding:24px}.container{max-width:700px;margin:0 auto}.card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.05)}.info{background:#dbeafe;border-left:4px solid var(--primary);padding:12px;border-radius:6px;margin-bottom:16px;font-size:0.85rem;color:#1e40af}label{display:block;font-size:0.85rem;color:#64748b;margin:8px 0 4px}input,select,textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:6px}.row{display:flex;gap:12px}.btn{padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;width:100%;font-weight:600}.btn-reset{background:#fee2e2;color:#dc2626;margin-top:10px}.preview{margin:16px 0;padding:20px;background:var(--bg);border-radius:8px;border:2px dashed var(--border)}.preview-box{display:flex;gap:12px;margin-top:10px}.p-btn{padding:8px 16px;border-radius:6px;color:#fff;font-weight:500}.p-sidebar{padding:10px;border-radius:6px;color:#fff;font-weight:500}</style></head><body>
<div class="container"><div class="card"><div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:12px;flex-wrap:wrap"><h1 style="margin:0">🏫 School Setup & Branding</h1><a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a></div>
<div class="info">ℹ️ You can update your school branding <strong>at any time</strong>. Changes apply instantly across all dashboards. Leave colors empty to use platform defaults.</div>
<form id="setupForm" enctype="multipart/form-data">
<label>System Type</label><select name="system"><option value="CBC">Competency Based Curriculum (CBC)</option><option value="8-4-4">8-4-4 System</option></select>
<div class="row"><div style="flex:1"><label>Primary Color (Buttons, Headings, Sidebar)</label><input type="color" name="primary" id="cPrimary" value="#1e40af" style="height:44px;padding:4px"></div>
<div style="flex:1"><label>Background/Accent Color</label><input type="color" name="secondary" id="cSecondary" value="#f8fafc" style="height:44px;padding:4px"></div></div>
<label>Motto (Optional)</label><input name="motto" placeholder="e.g. Discipline & Excellence">
<label>Contact Phone</label><input name="phone" placeholder="+254...">
<label>School Address</label><textarea name="address" rows="2" placeholder="Physical location, P.O. Box..."></textarea>
<label>Upload Logo (Max 2MB, PNG/JPG)</label><input type="file" name="logo" accept="image/png, image/jpeg, image/webp">
<button type="submit" class="btn">💾 Save Branding</button>
<button type="button" class="btn btn-reset" onclick="resetBranding()">↺ Reset to Platform Defaults</button>
</form>
<div class="preview"><strong>Live Preview:</strong><div class="preview-box"><div class="p-btn" id="prevBtn">Button Sample</div><div class="p-sidebar" id="prevSide">Sidebar Active</div></div></div></div></div>
<script>
const pColor = document.getElementById('cPrimary');
const sColor = document.getElementById('cSecondary');
const prevBtn = document.getElementById('prevBtn');
const prevSide = document.getElementById('prevSide');

function updatePreview() {
    const p = pColor.value;
    const s = sColor.value;
    prevBtn.style.background = p;
    prevSide.style.background = adjustBrightness(p, -60);
    document.documentElement.style.setProperty('--bg', s);
}

pColor.addEventListener('input', updatePreview);
sColor.addEventListener('input', updatePreview);
updatePreview();

function adjustBrightness(hex, amount) {
    const r = Math.max(0, Math.min(255, parseInt(hex.slice(1,3),16) + amount));
    const g = Math.max(0, Math.min(255, parseInt(hex.slice(3,5),16) + amount));
    const b = Math.max(0, Math.min(255, parseInt(hex.slice(5,7),16) + amount));
    return `#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
}

async function init(){const res=await fetch('/multi/api/admin/school_setup.php?action=get');const d=await res.json();if(d.primary)pColor.value=d.primary;if(d.secondary)sColor.value=d.secondary;if(d.system)document.querySelector('[name="system"]').value=d.system;if(d.motto)document.querySelector('[name="motto"]').value=d.motto;if(d.contact_phone)document.querySelector('[name="phone"]').value=d.contact_phone;if(d.address)document.querySelector('[name="address"]').value=d.address;updatePreview();}

document.getElementById('setupForm').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);const res=await fetch('/multi/api/admin/school_setup.php?action=save',{method:'POST',body:fd});const d=await res.json();if(d.success){alert('✅ Branding saved & applied instantly.');location.reload();}else alert('❌ '+d.error);});

async function resetBranding(){if(!confirm('Reset all branding to platform defaults? This cannot be undone.'))return;await fetch('/multi/api/admin/school_setup.php?action=reset',{method:'POST'});alert('✅ Reset complete. Refreshing to apply defaults.');location.reload();}

init();
</script></body></html>