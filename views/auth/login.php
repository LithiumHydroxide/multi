<?php
require_once '../../config.php';
if (getAuthenticatedUserId()) {
    header('Location: /multi/views/teacher/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CBC School Manager</title>
    <style>
        :root { --primary: #1e40af; --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --muted: #64748b; --border: #e2e8f0; --danger: #dc2626; --radius: 12px; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .login-card { background: var(--card); padding: 40px 32px; border-radius: var(--radius); box-shadow: 0 10px 25px rgba(0,0,0,0.08); width: 100%; max-width: 420px; }
        .logo { text-align: center; margin-bottom: 24px; font-size: 1.5rem; font-weight: 700; color: var(--primary); }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 0.85rem; color: var(--muted); margin-bottom: 6px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; transition: 0.2s; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1); }
        .btn { width: 100%; padding: 12px; background: var(--primary); color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: 0.2s; margin-top: 8px; }
        .btn:hover { background: #1d4ed8; }
        .btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .alert { padding: 10px; border-radius: 6px; font-size: 0.85rem; margin-bottom: 16px; display: none; }
        .alert-error { background: #fee2e2; color: var(--danger); border: 1px solid #fecaca; }
        .footer { text-align: center; margin-top: 24px; font-size: 0.8rem; color: var(--muted); }
        .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 0.8s linear infinite; margin-right: 8px; vertical-align: middle; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">🏫 CBC Manager</div>
        <div id="errorAlert" class="alert alert-error"></div>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" required placeholder="teacher@school.ke" autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" required placeholder="••••••••" autocomplete="current-password">
            </div>
            <button type="submit" class="btn" id="submitBtn">
                <span class="spinner" id="spinner"></span>
                Sign In
            </button>
        </form>
        <div class="footer">
            Demo: admin@greenfield-demo.ke / password123
        </div>
    </div>

    <script>
        const form = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const errorAlert = document.getElementById('errorAlert');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            errorAlert.style.display = 'none';

            try {
                const res = await fetch('/multi/api/auth/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value
                    })
                });
                const data = await res.json();

                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.error || 'Login failed');
                }
            } catch (err) {
                showError('Network error. Please try again.');
            } finally {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });

        function showError(msg) {
            errorAlert.textContent = msg;
            errorAlert.style.display = 'block';
        }
    </script>
</body>
</html>