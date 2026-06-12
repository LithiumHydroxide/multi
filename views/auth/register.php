<?php require_once '../../config.php'; if(getAuthenticatedUserId()) header('Location: /multi/views/teacher/dashboard.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register School | CBC Manager</title>
<style>:root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--text:#0f172a;--muted:#64748b;--border:#e2e8f0;--danger:#dc2626}*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}body{background:var(--bg);color:var(--text);display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px}.card{background:var(--card);padding:32px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.08);width:100%;max-width:480px}h1{text-align:center;margin-bottom:20px;font-size:1.5rem;color:var(--primary)}.form-group{margin-bottom:14px}label{display:block;font-size:0.85rem;color:var(--muted);margin-bottom:5px;font-weight:500}input{width:100%;padding:11px;border:1px solid var(--border);border-radius:8px;font-size:0.95rem}input:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(30,64,175,0.1)}.btn{width:100%;padding:12px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600;font-size:1rem;cursor:pointer;margin-top:8px}.btn:hover{background:#1d4ed8}.btn:disabled{opacity:0.7}.alert{padding:10px;border-radius:6px;font-size:0.85rem;margin-bottom:14px;display:none}.error{background:#fee2e2;color:var(--danger);border:1px solid #fecaca}.success{background:#d1fae5;color:#059669;border:1px solid #a7f3d0}a{text-align:center;display:block;margin-top:16px;color:var(--muted);text-decoration:none;font-size:0.9rem}a:hover{color:var(--primary)}</style></head>
<body>
<div class="card">
    <h1>🏫 Register Your School</h1>
    <div id="alert" class="alert"></div>
    <form id="regForm">
        <div class="form-group"><label>School Name</label><input type="text" name="school_name" required placeholder="e.g. Greenfield Academy"></div>
        <div class="form-group"><label>County</label><input type="text" name="county" required placeholder="e.g. Nairobi"></div>
        <div class="form-group"><label>Admin Name</label><input type="text" name="admin_name" required placeholder="e.g. John Doe"></div>
        <div class="form-group"><label>Admin Email</label><input type="email" name="admin_email" required placeholder="admin@school.ke"></div>
        <div class="form-group"><label>Password (min 8 chars)</label><input type="password" name="admin_password" required minlength="8"></div>
        <button type="submit" class="btn" id="submitBtn">Create Account</button>
    </form>
    <a href="/multi/views/auth/login.php">← Already have an account? Login</a>
</div>
<script>
const form = document.getElementById('regForm');
const btn = document.getElementById('submitBtn');
const alert = document.getElementById('alert');
form.addEventListener('submit', async e => {
    e.preventDefault(); btn.disabled = true; btn.textContent = 'Creating...'; alert.style.display = 'none';
    try {
        const res = await fetch('/multi/api/auth/register_school.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(Object.fromEntries(new FormData(form)))
        });
        const data = await res.json();
        if(data.success) { alert.className = 'alert success'; alert.textContent = data.message; alert.style.display = 'block'; setTimeout(() => location.href = '/multi/views/auth/login.php', 2000); }
        else { alert.className = 'alert error'; alert.textContent = data.error || 'Registration failed'; alert.style.display = 'block'; }
    } catch { alert.className = 'alert error'; alert.textContent = 'Network error'; alert.style.display = 'block'; }
    finally { btn.disabled = false; btn.textContent = 'Create Account'; }
});
</script>
</body>
</html>