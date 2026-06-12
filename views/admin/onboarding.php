<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();
$school = $pdo->query("SELECT * FROM schools WHERE id = $schoolId")->fetch();

// Redirect if already completed
if ($school['onboarding_completed']) {
    header('Location: /multi/views/admin/dashboard.php');
    exit;
}

// Determine current step from DB
$currentStep = $school['onboarding_step'] ?: 1;

// Allow manual back navigation via URL (?step=X) if it's behind the current DB step
if (isset($_GET['step']) && is_numeric($_GET['step']) && $_GET['step'] < $currentStep) {
    $currentStep = (int)$_GET['step'];
}

// Pre-fill Step 1 data if available
$settingsStmt = $pdo->prepare("SELECT * FROM school_settings WHERE school_id = ?");
$settingsStmt->execute([$schoolId]);
$settings = $settingsStmt->fetch() ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>School Setup Wizard | CBC Manager</title>
<?= getBrandingCSS($schoolId) ?>
<style>
:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
body{background:var(--bg);padding:24px}
.container{max-width:800px;margin:0 auto}
.card{background:var(--card);padding:32px;border-radius:16px;box-shadow:0 4px 12px rgba(0,0,0,0.06)}
.progress{display:flex;gap:8px;margin-bottom:24px}
.step{flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden}
.step.active{background:var(--primary)}
.step-labels{display:flex;justify-content:space-between;font-size:0.75rem;color:var(--muted);margin-bottom:20px}
h1{margin-bottom:8px;color:var(--text)}
p.sub{color:var(--muted);margin-bottom:24px}
label{display:block;font-size:0.85rem;color:var(--muted);margin:10px 0 4px}
input,select,textarea{width:100%;padding:11px;border:1px solid var(--border);border-radius:8px;font-size:0.95rem}
input:focus,select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}
.row{display:flex;gap:12px;flex-wrap:wrap}
.row>*{flex:1;min-width:200px}
.btn{padding:12px 20px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:0.95rem;margin-top:16px;transition:0.2s}
.btn-primary{background:var(--primary);color:#fff}
.btn-primary:hover{background:var(--primary-dark)}
.btn-secondary{background:#f1f5f9;color:var(--text)}
.btn-secondary:hover{background:#e2e8f0}
.actions{display:flex;justify-content:space-between;margin-top:24px}
.hidden{display:none}
.color-preview{display:flex;gap:12px;margin-top:8px}
.color-box{width:60px;height:40px;border-radius:6px;border:2px solid var(--border)}
.checkbox-group{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:8px}
.checkbox-item{display:flex;align-items:center;gap:8px;padding:8px;background:var(--bg);border-radius:6px;cursor:pointer}
.checkbox-item input{width:auto;margin:0}
</style>
</head>
<body>
<div class="container">
<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px">
    <h1 style="margin:0">🏫 School Setup Wizard</h1><a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
    </div>
    <p class="sub">Complete these steps to activate your dashboard.</p>
    
    <div class="progress">
        <div class="step <?= $currentStep>=1?'active':'' ?>"></div>
        <div class="step <?= $currentStep>=2?'active':'' ?>"></div>
        <div class="step <?= $currentStep>=3?'active':'' ?>"></div>
        <div class="step <?= $currentStep>=4?'active':'' ?>"></div>
    </div>
    <div class="step-labels"><span>School Info</span><span>Academics</span><span>Staff</span><span>Students</span></div>

    <!-- Step 1: School Info & Branding -->
    <div id="step1" class="step-content <?= $currentStep==1?'':'hidden' ?>">
        <h2>📋 School Information & Branding</h2>
        <form id="form1">
            <label>School System</label>
            <select name="system_type">
                <option value="CBC" <?= ($settings['system_type'] ?? '') === 'CBC' ? 'selected' : '' ?>>Competency Based Curriculum (CBC)</option>
                <option value="8-4-4" <?= ($settings['system_type'] ?? '') === '8-4-4' ? 'selected' : '' ?>>8-4-4 System</option>
            </select>
            
            <div class="row">
                <div><label>Motto</label><input name="motto" value="<?= htmlspecialchars($settings['motto'] ?? '') ?>" placeholder="e.g. Excellence & Discipline"></div>
                <div><label>Contact Phone</label><input name="phone" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>" placeholder="+254..."></div>
            </div>
            
            <label>Address</label>
            <textarea name="address" rows="2" placeholder="Physical location..."><?= htmlspecialchars($settings['address'] ?? '') ?></textarea>
            
            <div class="row">
                <div>
                    <label>Primary Color</label>
                    <input type="color" name="primary" id="primaryColor" value="<?= htmlspecialchars($settings['primary_color'] ?? '#1e40af') ?>" style="height:44px;padding:4px;cursor:pointer">
                    <div class="color-preview"><div class="color-box" id="primaryPreview" style="background:<?= htmlspecialchars($settings['primary_color'] ?? '#1e40af') ?>"></div></div>
                </div>
                <div>
                    <label>Background Color</label>
                    <input type="color" name="secondary" id="secondaryColor" value="<?= htmlspecialchars($settings['secondary_color'] ?? '#f8fafc') ?>" style="height:44px;padding:4px;cursor:pointer">
                    <div class="color-preview"><div class="color-box" id="secondaryPreview" style="background:<?= htmlspecialchars($settings['secondary_color'] ?? '#f8fafc') ?>"></div></div>
                </div>
            </div>
            
            <label>Upload Logo (Optional)</label>
            <input type="file" name="logo" accept="image/png,image/jpeg">
        </form>
    </div>

    <!-- Step 2: Academic Structure -->
    <div id="step2" class="step-content <?= $currentStep==2?'':'hidden' ?>">
        <h2>📚 Academic Setup</h2>
        <p class="sub">Select grade levels and create your first class.</p>
        <form id="form2">
            <label>Grade Levels Offered</label>
            <div class="checkbox-group">
                <label class="checkbox-item"><input type="checkbox" name="levels[]" value="1"> 🎒 Pre-Primary</label>
                <label class="checkbox-item"><input type="checkbox" name="levels[]" value="2"> 📖 Primary</label>
                <label class="checkbox-item" style="background:#dbeafe"><input type="checkbox" name="levels[]" value="3" checked> 🏫 Junior Secondary</label>
                <label class="checkbox-item"><input type="checkbox" name="levels[]" value="4"> 🎓 Senior Secondary</label>
            </div>
            <div class="row" style="margin-top:16px">
                <div><label>Sample Class Name *</label><input name="class_name" placeholder="e.g. Grade 7 East" required></div>
                <div><label>Stream Code *</label><input name="stream_code" placeholder="e.g. 7E" required></div>
            </div>
            <label style="margin-top:12px">Subjects (comma separated) *</label>
            <input name="subjects" placeholder="e.g. Mathematics, English, Kiswahili" required>
        </form>
    </div>

    <!-- Step 3: Staff -->
    <div id="step3" class="step-content <?= $currentStep==3?'':'hidden' ?>">
        <h2>👨‍ Add Teaching Staff</h2>
        <form id="form3">
            <label>Staff Name *</label><input name="name" required>
            <label>Email *</label><input type="email" name="email" required>
            <label>Password (8+ chars) *</label><input type="password" name="password" required minlength="8">
            <div class="row">
                <div><label>Role *</label><select name="role"><option value="teacher">Teacher</option><option value="non_teaching">Support</option></select></div>
                <div><label>TSC/Specialization</label><input name="specialization" placeholder="e.g. Math"></div>
            </div>
        </form>
    </div>

    <!-- Step 4: Students -->
    <div id="step4" class="step-content <?= $currentStep==4?'':'hidden' ?>">
        <h2>🎓 Import Students</h2>
        <form id="form4">
            <label>Upload CSV (Name, AdmissionNo, ClassId, Gender, DOB)</label>
            <input type="file" name="csv_file" accept=".csv">
            <p class="sub" style="margin-top:8px">Or manually add the first student:</p>
            <div class="row">
                <div><label>Student Name</label><input name="student_name"></div>
                <div><label>Admission No</label><input name="adm_no"></div>
            </div>
            <div class="row">
                <div><label>Gender</label><select name="gender"><option>Male</option><option>Female</option></select></div>
                <div><label>DOB</label><input type="date" name="dob"></div>
            </div>
        </form>
    </div>

    <div class="actions">
        <button type="button" class="btn btn-secondary" id="prevBtn" onclick="goBack()" style="visibility:hidden">← Back</button>
        <button type="button" class="btn btn-primary" id="nextBtn" onclick="goNext()">Next Step →</button>
    </div>
</div>
</div>

<script>
const currentStep = <?= $currentStep ?>;
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

if (currentStep > 1) prevBtn.style.visibility = 'visible';
if (currentStep === 4) nextBtn.textContent = '🚀 Complete Setup & Launch';

// Live color preview
document.getElementById('primaryColor')?.addEventListener('input', function() { document.getElementById('primaryPreview').style.background = this.value; });
document.getElementById('secondaryColor')?.addEventListener('input', function() { document.getElementById('secondaryPreview').style.background = this.value; });

function goBack() {
    if (currentStep > 1) window.location.href = `?step=${currentStep - 1}`;
}

async function goNext() {
    const forms = {1:'form1', 2:'form2', 3:'form3', 4:'form4'};
    const form = document.getElementById(forms[currentStep]);
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    // Basic Validation
    if (currentStep === 2 && (!data.class_name || !data.subjects)) return alert('Please fill required fields.');
    if (currentStep === 3 && (!data.name || !data.email || !data.password)) return alert('Please fill required fields.');
    
    nextBtn.disabled = true; nextBtn.textContent = 'Saving...';
    
    try {
        const res = await fetch('/multi/api/admin/onboarding.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({step: currentStep, ...data})
        });
        const d = await res.json();
        
        if (d.success) {
            if (currentStep < 4) window.location.href = `?step=${currentStep + 1}`;
            else { alert('✅ Setup complete!'); window.location.href = '/multi/views/admin/dashboard.php'; }
        } else {
            alert('❌ ' + (d.error || 'Save failed'));
            nextBtn.disabled = false; nextBtn.textContent = 'Next Step →';
        }
    } catch (err) {
        alert('❌ Network error');
        nextBtn.disabled = false; nextBtn.textContent = 'Next Step →';
    }
}
</script>
</body>
</html>