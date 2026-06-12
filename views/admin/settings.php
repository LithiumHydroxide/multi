<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Get school info
$school = $pdo->query("SELECT * FROM schools WHERE id = $schoolId")->fetch();

// Get settings with proper defaults
$settingsStmt = $pdo->prepare("SELECT * FROM school_settings WHERE school_id = ?");
$settingsStmt->execute([$schoolId]);
$settings = $settingsStmt->fetch();

// If no settings exist, create defaults
if (!$settings) {
    $pdo->prepare("INSERT INTO school_settings (school_id, primary_color, secondary_color, dashboard_layout, default_term) VALUES (?, ?, ?, ?, ?)")
        ->execute([$schoolId, '#1e40af', '#f8fafc', 'default', 1]);
    $settings = [
        'primary_color' => '#1e40af',
        'secondary_color' => '#f8fafc',
        'dashboard_layout' => 'default',
        'default_term' => 1,
        'motto' => '',
        'system_type' => 'CBC'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Settings | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:800px;margin:0 auto}
.card{background:var(--card);padding:28px;border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,0.06);margin-bottom:20px}
h1{margin-bottom:8px}
p.sub{color:var(--muted);margin-bottom:20px}
label{display:block;font-size:0.85rem;color:var(--muted);margin:10px 0 4px}
input,select,textarea{width:100%;padding:11px;border:1px solid var(--border);border-radius:8px;font-size:0.95rem}
input:focus,select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.row{display:flex;gap:12px;flex-wrap:wrap}
.row>*{flex:1;min-width:200px}
.btn{padding:12px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.95rem;margin-top:16px;transition:0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark)}
.tabs{display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:8px}
.tab{padding:8px 16px;border-radius:8px;cursor:pointer;font-weight:500;color:var(--muted)}
.tab.active{background:var(--primary-bg);color:var(--primary)}
.tab:hover{background:var(--primary-bg-hover)}
.tab-content{display:none}
.tab-content.active{display:block}
.color-input-wrapper{display:flex;gap:10px;align-items:center}
.color-input-wrapper input[type="color"]{width:60px;height:44px;padding:2px;border-radius:6px;cursor:pointer}
.color-input-wrapper input[type="text"]{flex:1}
</style>
</head>
<body>
<div class="container">
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px">
<h1 style="margin:0">⚙️ Dashboard Settings</h1><a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
</div>
<p class="sub">Customize your dashboard appearance, preferences, and system behavior. Changes apply instantly.</p>

<div class="tabs">
    <div class="tab active" data-tab="general">General</div>
    <div class="tab" data-tab="branding">Branding & Colors</div>
    <div class="tab" data-tab="preferences">Dashboard Preferences</div>
</div>

<div class="card tab-content active" id="general">
    <h3>🏫 School Information</h3>
    <form id="generalForm">
        <label>School Name</label>
        <input name="name" value="<?= htmlspecialchars($school['name']) ?>">
        
        <label>County</label>
        <input name="county" value="<?= htmlspecialchars($school['county'] ?? '') ?>">
        
        <label>System Type</label>
        <select name="system_type">
            <option value="CBC" <?= ($settings['system_type'] ?? 'CBC') === 'CBC' ? 'selected' : '' ?>>CBC</option>
            <option value="8-4-4" <?= ($settings['system_type'] ?? 'CBC') === '8-4-4' ? 'selected' : '' ?>>8-4-4</option>
        </select>
        
        <button class="btn btn-primary" type="submit">💾 Save General</button>
    </form>
</div>

<div class="card tab-content" id="branding">
    <h3>🎨 Branding & Dashboard Colors</h3>
    <form id="brandingForm">
        <div class="row">
            <div>
                <label>Primary Color</label>
                <div class="color-input-wrapper">
                    <input type="color" name="primary" id="primaryColor" value="<?= htmlspecialchars($settings['primary_color'] ?? '#1e40af') ?>">
                    <input type="text" id="primaryColorText" value="<?= htmlspecialchars($settings['primary_color'] ?? '#1e40af') ?>" readonly>
                </div>
            </div>
            <div>
                <label>Background/Accent</label>
                <div class="color-input-wrapper">
                    <input type="color" name="secondary" id="secondaryColor" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#f8fafc') ?>">
                    <input type="text" id="secondaryColorText" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#f8fafc') ?>" readonly>
                </div>
            </div>
        </div>
        
        <label>Motto</label>
        <input name="motto" value="<?= htmlspecialchars($settings['motto'] ?? '') ?>">
        
        <label>Logo (Optional, Max 2MB)</label>
        <input type="file" name="logo" accept="image/png,image/jpeg">
        
        <button class="btn btn-primary" type="submit">💾 Update Branding</button>
    </form>
</div>

<div class="card tab-content" id="preferences">
    <h3>📊 Dashboard Preferences</h3>
    <form id="prefsForm">
        <label>Default Layout</label>
        <select name="dashboard_layout">
            <option value="default" <?= ($settings['dashboard_layout'] ?? 'default') === 'default' ? 'selected' : '' ?>>Standard</option>
            <option value="compact" <?= ($settings['dashboard_layout'] ?? 'default') === 'compact' ? 'selected' : '' ?>>Compact</option>
            <option value="analytics" <?= ($settings['dashboard_layout'] ?? 'default') === 'analytics' ? 'selected' : '' ?>>Analytics-First</option>
        </select>
        
        <label>Default Term</label>
        <select name="default_term">
            <option value="1" <?= ($settings['default_term'] ?? 1) == 1 ? 'selected' : '' ?>>Term 1</option>
            <option value="2" <?= ($settings['default_term'] ?? 1) == 2 ? 'selected' : '' ?>>Term 2</option>
            <option value="3" <?= ($settings['default_term'] ?? 1) == 3 ? 'selected' : '' ?>>Term 3</option>
        </select>
        
        <label>Academic Year Start</label>
        <input type="date" name="academic_year_start" value="<?= htmlspecialchars($settings['academic_year_start'] ?? date('Y-m-d', strtotime('January 1'))) ?>">
        
        <button class="btn btn-primary" type="submit">💾 Save Preferences</button>
    </form>
</div>
</div>

<script>
// Tab switching
document.querySelectorAll('.tab').forEach(t=>t.addEventListener('click',()=>{
    document.querySelectorAll('.tab').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(x=>x.classList.remove('active'));
    t.classList.add('active');
    document.getElementById(t.dataset.tab).classList.add('active');
}));

// Color picker sync
document.getElementById('primaryColor').addEventListener('input', function() {
    document.getElementById('primaryColorText').value = this.value;
});
document.getElementById('secondaryColor').addEventListener('input', function() {
    document.getElementById('secondaryColorText').value = this.value;
});

// Form handlers
async function handleForm(id, endpoint) {
    document.getElementById(id).addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const btn = e.target.querySelector('button');
        btn.disabled = true; 
        btn.textContent = 'Saving...';
        
        try {
            const res = await fetch(endpoint, {method:'POST', body:fd});
            const d = await res.json();
            if(d.success) { 
                alert('✅ Saved! Refreshing to apply changes.'); 
                location.reload(); 
            } else {
                alert('❌ ' + (d.error || 'Save failed'));
            }
        } catch(err) {
            alert('❌ Network error: ' + err.message);
        }
        
        btn.disabled = false; 
        btn.textContent = '💾 Save';
    });
}

handleForm('generalForm', '/multi/api/admin/settings.php?action=general');
handleForm('brandingForm', '/multi/api/admin/settings.php?action=branding');
handleForm('prefsForm', '/multi/api/admin/settings.php?action=prefs');
</script>
</body>
</html>