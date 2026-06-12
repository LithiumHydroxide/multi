<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher');
$schoolId = getAuthenticatedSchoolId();
$userId   = getAuthenticatedUserId();
$userName = $_SESSION['user_name'] ?? 'Teacher';
$pdo      = getDBConnection();

// Quick Stats
$classesStmt = $pdo->prepare("SELECT COUNT(*) FROM class_subject_teacher WHERE teacher_id = (SELECT id FROM teachers WHERE user_id = ?)");
$classesStmt->execute([$userId]);
$classCount = $classesStmt->fetchColumn();

$studentsStmt = $pdo->prepare("SELECT COUNT(DISTINCT student_id) FROM scores WHERE graded_by = ? AND school_id = ?");
$studentsStmt->execute([$userId, $schoolId]);
$scoresCount = $studentsStmt->fetchColumn();

$today = date('Y-m-d');
$attStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE marked_by = ? AND school_id = ? AND date = ?");
$attStmt->execute([$userId, $schoolId, $today]);
$todayAttCount = $attStmt->fetchColumn();
$currentTerm = date('m') <= 4 ? '1' : (date('m') <= 8 ? '2' : '3');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard | CBC Manager</title>

    <!-- 1. Default Fallback Styles (Loaded First) -->
    <style>
        :root {
            --primary: #1e40af; --primary-light: #3b82f6; --primary-dark: #1e3a8a;
            --bg: #f8fafc; --surface: #ffffff; --text: #0f172a; --text-muted: #64748b;
            --border: #e2e8f0; --shadow-sm: 0 1px 2px rgba(0,0,0,0.04); --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
            --radius: 16px; --radius-sm: 12px; --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); line-height: 1.5; }
        .layout { display: flex; min-height: 100vh; position: relative; }
        .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 24px 16px; display: flex; flex-direction: column; transition: var(--transition); z-index: 40; }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 12px 20px; font-weight: 800; font-size: 1.1rem; color: var(--text); border-bottom: 1px solid var(--border); margin-bottom: 20px; min-height: 40px; }
        .brand img { height: 28px; width: auto; object-fit: contain; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius-sm); color: var(--text-muted); text-decoration: none; font-weight: 500; transition: var(--transition); position: relative; margin-bottom: 4px; }
        .nav-item:hover, .nav-item.active { background: rgba(30, 64, 175, 0.06); color: var(--primary); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 4px; height: 60%; background: var(--primary); border-radius: 0 4px 4px 0; }
        .nav-item .icon { font-size: 1.15rem; width: 22px; text-align: center; }
        .premium-badge { margin-left: auto; background: var(--warning); color: #000; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; font-weight: 700; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border); }
        .logout-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 14px; background: #fee2e2; color: var(--danger); border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; transition: var(--transition); }
        .logout-btn:hover { background: #fecaca; }
        .main { flex: 1; padding: 32px; overflow-y: auto; max-width: 1400px; margin: 0 auto; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 16px; }
        .greeting h1 { font-size: 1.75rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .greeting p { color: var(--text-muted); font-size: 0.95rem; }
        .profile { display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 8px 16px 8px 8px; border-radius: 50px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
        .profile-info { display: flex; flex-direction: column; line-height: 1.2; }
        .profile-name { font-weight: 600; font-size: 0.9rem; }
        .profile-role { font-size: 0.75rem; color: var(--text-muted); }
        .mobile-toggle { display: none; background: var(--surface); border: 1px solid var(--border); padding: 10px; border-radius: 10px; cursor: pointer; font-size: 1.2rem; box-shadow: var(--shadow-sm); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; align-items: center; gap: 16px; transition: var(--transition); opacity: 0; animation: slideUp 0.5s ease forwards; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-info h3 { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-info .value { font-size: 1.6rem; font-weight: 700; color: var(--text); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-title { font-size: 1.15rem; font-weight: 600; color: var(--text); }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
        .action-card { background: var(--surface); padding: 24px 16px; border-radius: var(--radius); border: 1px solid var(--border); text-align: center; text-decoration: none; color: var(--text); transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 12px; opacity: 0; animation: slideUp 0.5s ease forwards; }
        .action-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: var(--primary); background: rgba(30, 64, 175, 0.02); }
        .action-card .icon-wrap { width: 48px; height: 48px; border-radius: 12px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; transition: var(--transition); }
        .action-card:hover .icon-wrap { transform: scale(1.05); background: var(--primary); color: var(--text-on-primary); }
        .action-card h4 { font-size: 0.95rem; font-weight: 600; margin: 0; }
        .action-card p { font-size: 0.78rem; color: var(--text-muted); margin: 0; line-height: 1.4; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -100%; height: 100vh; box-shadow: var(--shadow-lg); }
            .sidebar.open { left: 0; }
            .main { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .mobile-toggle { display: block; }
            .overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.3); opacity: 0; pointer-events: none; transition: var(--transition); z-index: 30; }
            .overlay.active { opacity: 1; pointer-events: auto; }
        }
    </style>

    <!-- 2. School Branding Injection (Placed AFTER defaults to override them) -->
    <?= getBrandingCSS($schoolId) ?>

</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <?php 
                // Fetch logo dynamically if available
                $settingsStmt = $pdo->prepare("SELECT logo_path FROM school_settings WHERE school_id = ?");
                $settingsStmt->execute([$schoolId]);
                $logoPath = $settingsStmt->fetchColumn();
                if ($logoPath): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo">
                <?php else: ?>
                    <span style="font-size:1.5rem;">🏫</span>
                <?php endif; ?>
                <span><?= htmlspecialchars($pdo->query("SELECT name FROM schools WHERE id=$schoolId")->fetchColumn()) ?></span>
            </div>
            <nav>
    <!-- Core -->
    <a href="/multi/views/teacher/dashboard.php" class="nav-item active"><span class="icon">🎓</span> Dashboard</a>
    
    <!-- Academics & CBC -->
    <a href="/multi/views/teacher/score_entry.php" class="nav-item"><span class="icon">📝</span> Record Scores</a>
    <a href="/multi/views/teacher/strand_entry.php" class="nav-item"><span class="icon">🧩</span> Strand Assessments</a>
    <a href="/multi/views/teacher/report_card.php" class="nav-item"><span class="icon">🧾</span> Reports</a>
    
    <!-- Operations -->
    <a href="/multi/views/teacher/attendance.php" class="nav-item"><span class="icon">✅</span> Attendance</a>
    <a href="/multi/views/teacher/timetable.php" class="nav-item"><span class="icon">⏰</span> Timetable</a>
    
    <!-- Insights & Advanced -->
    <a href="/multi/views/teacher/analytics.php" class="nav-item"><span class="icon">📊</span> Class Analytics</a>
    <a href="/multi/views/teacher/student_analysis.php" class="nav-item"><span class="icon">🌟</span> Learner Insights <span class="premium-badge">PREMIUM</span></a>
</nav>
            <div class="sidebar-footer">
                <a href="/multi/api/auth/logout.php" class="logout-btn"><span class="icon">🚪</span> Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <div class="header">
                <div class="greeting">
                    <h1><?= date('H') < 12 ? 'Good morning' : (date('H') < 18 ? 'Good afternoon' : 'Good evening') ?>, <?= htmlspecialchars($userName) ?> 👋</h1>
                    <p>Here's your overview for Term <?= $currentTerm ?>, <?= date('Y') ?></p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <button class="mobile-toggle" id="menuBtn" onclick="toggleSidebar()">☰</button>
                    <div class="profile">
                        <div class="avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                        <div class="profile-info">
                            <span class="profile-name"><?= htmlspecialchars($userName) ?></span>
                            <span class="profile-role">Teacher</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon">🏫</div><div class="stat-info"><h3>Assigned Classes</h3><div class="value"><?= $classCount ?></div></div></div>
                <div class="stat-card"><div class="stat-icon">✅</div><div class="stat-info"><h3>Scores Entered</h3><div class="value"><?= $scoresCount ?></div></div></div>
                <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-info"><h3>Attendance Marked</h3><div class="value"><?= $todayAttCount ?></div></div></div>
                <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-info"><h3>Current Term</h3><div class="value">Term <?= $currentTerm ?></div></div></div>
            </div>

            <div class="section-header"><div class="section-title">⚡ Quick Actions</div></div>
            <div class="actions-grid">
                <a href="/multi/views/teacher/score_entry.php" class="action-card"><div class="icon-wrap">📝</div><h4>Enter New Scores</h4><p>Input marks & auto-grade to CBC levels</p></a>
                <a href="/multi/views/teacher/attendance.php" class="action-card"><div class="icon-wrap">📅</div><h4>Mark Attendance</h4><p>Track daily presence & generate reports</p></a>
                <a href="/multi/views/teacher/report_card.php" class="action-card"><div class="icon-wrap">📊</div><h4>Generate Reports</h4><p>View & print CBC-compliant report cards</p></a>
                <a href="/multi/views/teacher/timetable.php" class="action-card"><div class="icon-wrap">🗓️</div><h4>View Timetable</h4><p>Check your daily class schedule</p></a>
            </div>
        </main>
    </div>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.stat-card, .action-card').forEach((card, i) => {
                card.style.animationDelay = `${i * 0.06}s`;
            });
        });
    </script>
</body>
</html>