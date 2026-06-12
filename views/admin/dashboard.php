<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');
$schoolId = getAuthenticatedSchoolId();
$userName = $_SESSION['user_name'] ?? 'Administrator';
$pdo = getDBConnection();

// Fetch School Info
$schoolStmt = $pdo->prepare("SELECT name, county, contact_email, onboarding_step, onboarding_completed FROM schools WHERE id = ?");
$schoolStmt->execute([$schoolId]);
$school = $schoolStmt->fetch();

// Fetch Settings (Logo)
$settingsStmt = $pdo->prepare("SELECT logo_path FROM school_settings WHERE school_id = ?");
$settingsStmt->execute([$schoolId]);
$settingsRow = $settingsStmt->fetch();
$logoPath = $settingsRow ? $settingsRow['logo_path'] : null;

// Dynamic Greeting
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$initials = strtoupper(substr($userName, 0, 1));

// Quick Stats
$studentsStmt = $pdo->query("SELECT COUNT(*) FROM students WHERE school_id = $schoolId AND status = 'active'")->fetchColumn();
$teachersStmt = $pdo->query("SELECT COUNT(*) FROM teachers WHERE school_id = $schoolId")->fetchColumn();
$classesStmt = $pdo->query("SELECT COUNT(*) FROM classes WHERE school_id = $schoolId")->fetchColumn();
$subjectsStmt = $pdo->query("SELECT COUNT(*) FROM subjects WHERE school_id = $schoolId")->fetchColumn();

// Subscription Info
$subStmt = $pdo->prepare("
    SELECT sp.name as plan_name, sp.price, ss.end_date, ss.status
    FROM school_subscriptions ss
    JOIN subscription_plans sp ON ss.plan_id = sp.id
    WHERE ss.school_id = ? AND ss.status = 'active'
");
$subStmt->execute([$schoolId]);
$subscription = $subStmt->fetch();

// Recent Activity
$recentStmt = $pdo->prepare("
    SELECT s.name as student_name, sub.name as subject, sc.marks_obtained, sc.created_at
    FROM scores sc
    JOIN students s ON sc.student_id = s.id
    JOIN assessments a ON sc.assessment_id = a.id
    JOIN subjects sub ON a.subject_id = sub.id
    WHERE sc.school_id = ?
    ORDER BY sc.created_at DESC
    LIMIT 5
");
$recentStmt->execute([$schoolId]);
$recentActivity = $recentStmt->fetchAll();
$currentTerm = date('m') <= 4 ? '1' : (date('m') <= 8 ? '2' : '3');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CBC Manager</title>
    
    <!-- 1. Hardcoded Default Styles -->
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --primary-dark: #1e3a8a;
            --bg: #f8fafc;
            --surface: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --radius: 16px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        body { background: var(--bg); color: var(--text); line-height: 1.5; }
        .layout { display: flex; min-height: 100vh; position: relative; }
        .sidebar { width: 260px; background: var(--surface); border-right: 1px solid var(--border); padding: 24px 16px; display: flex; flex-direction: column; transition: var(--transition); z-index: 40; }
        .brand { display: flex; align-items: center; gap: 10px; padding: 0 12px 20px; font-weight: 800; font-size: 1.25rem; color: var(--text); border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .nav-section { margin-bottom: 20px; }
        .nav-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); padding: 8px 12px; font-weight: 700; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius-sm); color: var(--text-muted); text-decoration: none; font-weight: 500; transition: var(--transition); position: relative; margin-bottom: 4px; }
        .nav-item:hover, .nav-item.active { background: rgba(30, 64, 175, 0.06); color: var(--primary); }
        .nav-item.active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 4px; height: 60%; background: var(--primary); border-radius: 0 4px 4px 0; }
        .nav-item .icon { font-size: 1.15rem; width: 22px; text-align: center; }
        .premium-badge { margin-left: auto; background: var(--warning); color: #000; font-size: 0.65rem; padding: 2px 6px; border-radius: 10px; font-weight: 700; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border); }
        .logout-btn { display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 14px; background: #fee2e2; color: var(--danger); border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; transition: var(--transition); }
        .logout-btn:hover { background: #fecaca; }
        .main { flex: 1; padding: 32px; overflow-y: auto; max-width: 1400px; margin: 0 auto; width: 100%; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .greeting h1 { font-size: 1.75rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .greeting p { color: var(--text-muted); font-size: 0.95rem; }
        .profile { display: flex; align-items: center; gap: 12px; background: var(--surface); padding: 8px 16px 8px 8px; border-radius: 50px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
        .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), var(--primary-light)); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; }
        .profile-info { display: flex; flex-direction: column; line-height: 1.2; }
        .profile-name { font-weight: 600; font-size: 0.9rem; }
        .profile-role { font-size: 0.75rem; color: var(--text-muted); }
        .mobile-toggle { display: none; background: var(--surface); border: 1px solid var(--border); padding: 10px; border-radius: 10px; cursor: pointer; font-size: 1.2rem; box-shadow: var(--shadow-sm); }
        .onboarding-banner { background: linear-gradient(135deg, var(--warning), #d97706); padding: 20px; border-radius: var(--radius); color: #000; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; opacity: 0; animation: slideUp 0.5s ease forwards; }
        .onboarding-banner h3 { margin-bottom: 4px; font-size: 1.1rem; font-weight: 700; }
        .onboarding-banner p { opacity: 0.85; font-size: 0.9rem; }
        .onboarding-banner .progress-track { background: rgba(0,0,0,0.15); height: 6px; border-radius: 3px; width: 180px; margin-top: 8px; overflow: hidden; }
        .onboarding-banner .progress-fill { background: #000; height: 100%; width: 0%; transition: width 0.5s ease; }
        .onboarding-banner .cta { background: #000; color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: var(--transition); }
        .onboarding-banner .cta:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); display: flex; align-items: center; gap: 16px; transition: var(--transition); opacity: 0; animation: slideUp 0.5s ease forwards; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(30, 64, 175, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-info h3 { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-info .value { font-size: 1.6rem; font-weight: 700; color: var(--text); }
        .subscription-banner { background: linear-gradient(135deg, var(--primary), var(--primary-light)); padding: 24px; border-radius: var(--radius); color: white; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; opacity: 0; animation: slideUp 0.5s ease 0.1s forwards; }
        .sub-info h3 { font-size: 1.1rem; margin-bottom: 4px; }
        .sub-info p { opacity: 0.9; font-size: 0.9rem; }
        .sub-badge { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .section { background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .section-title { font-size: 1.15rem; font-weight: 600; color: var(--text); }
        .actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; }
        .action-card { background: var(--bg); padding: 20px 16px; border-radius: var(--radius-sm); border: 1px solid var(--border); text-align: center; text-decoration: none; color: var(--text); transition: var(--transition); display: flex; flex-direction: column; align-items: center; gap: 10px; opacity: 0; animation: slideUp 0.5s ease forwards; }
        .action-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary); background: rgba(30, 64, 175, 0.02); }
        .action-card .icon-wrap { width: 44px; height: 44px; border-radius: 10px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; transition: var(--transition); }
        .action-card:hover .icon-wrap { transform: scale(1.1); }
        .action-card h4 { font-size: 0.9rem; font-weight: 600; margin: 0; }
        .action-card p { font-size: 0.75rem; color: var(--text-muted); margin: 0; line-height: 1.4; }
        .activity-list { display: flex; flex-direction: column; gap: 12px; }
        .activity-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; border-radius: var(--radius-sm); background: var(--bg); border: 1px solid var(--border); transition: var(--transition); opacity: 0; animation: slideUp 0.5s ease forwards; }
        .activity-item:hover { border-color: var(--primary-light); background: rgba(30, 64, 175, 0.02); }
        .activity-icon { width: 36px; height: 36px; border-radius: 8px; background: var(--primary-bg); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .activity-content { flex: 1; min-width: 0; }
        .activity-title { font-size: 0.9rem; font-weight: 600; color: var(--text); margin-bottom: 2px; }
        .activity-time { font-size: 0.75rem; color: var(--text-muted); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 1024px) { .content-grid { grid-template-columns: 1fr; } }
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

    <!-- 2. Dynamic Branding Injection (Placed AFTER hardcoded styles to override them) -->
    <?= getBrandingCSS($schoolId) ?>
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <?php if ($logoPath): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" style="height:30px; width:auto; object-fit:contain; border-radius:4px;">
                <?php else: ?>
                    <span style="font-size:1.5rem;">🏫</span>
                <?php endif; ?>
                <span><?= htmlspecialchars($school['name']) ?></span>
            </div>
            
            <div class="nav-section">
                <div class="nav-label">Main</div>
                <a href="/multi/views/admin/dashboard.php" class="nav-item active"><span class="icon">📊</span> Dashboard</a>
                <a href="/multi/views/admin/students.php" class="nav-item"><span class="icon">🎓</span> Students</a>
                <a href="/multi/views/admin/teachers.php" class="nav-item"><span class="icon">👨‍🏫</span> Teachers</a>
                <a href="/multi/views/admin/users.php" class="nav-item"><span class="icon">👥</span> Staff</a>
                <a href="/multi/views/admin/inventory.php" class="nav-item"><span class="icon">📦</span> Inventory & Assets <span class="premium-badge">PREMIUM</span></a>
                <a href="/multi/views/admin/parents.php" class="nav-item"><span class="icon">👨‍👩‍</span> Parents & Guardians</a>
                <a href="/multi/views/admin/class_assignments.php" class="nav-item"><span class="icon">📚</span> Class Assignments</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-label">Academic</div>
                <a href="/multi/views/admin/academics.php" class="nav-item"><span class="icon">📖</span> Academics Setup</a>
                <a href="/multi/views/admin/cbc_setup.php" class="nav-item"><span class="icon">📚</span> CBC Setup</a>
                <a href="/multi/views/admin/calendar.php" class="nav-item"><span class="icon">📅</span> Academic Calendar</a>
                <a href="/multi/views/admin/streams_houses.php" class="nav-item"><span class="icon">🏠</span> Streams & Houses</a>
                <a href="/multi/views/admin/timetable.php" class="nav-item"><span class="icon">🗓️</span> Manage Timetable</a>
            </div>
            
            <div class="nav-section">
                <div class="nav-label">Administration</div>
                <a href="/multi/views/admin/settings.php" class="nav-item"><span class="icon">⚙️</span> Dashboard Settings</a>
                <a href="/multi/views/admin/subscription.php" class="nav-item"><span class="icon">💳</span> Subscription</a>
                <a href="/multi/views/admin/reports.php" class="nav-item"><span class="icon">📈</span> Reports & Analytics</a>
            </div>
            
            <div class="sidebar-footer">
                <a href="/multi/api/auth/logout.php" class="logout-btn"><span class="icon">🚪</span> Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <div class="header">
                <div class="greeting">
                    <h1><?= $greeting ?>, <?= htmlspecialchars($userName) ?> 👋</h1>
                    <p><?= htmlspecialchars($school['name'] ?? 'School') ?> • Term <?= $currentTerm ?>, <?= date('Y') ?></p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <button class="mobile-toggle" id="menuBtn" onclick="toggleSidebar()">☰</button>
                    <div class="profile">
                        <div class="avatar"><?= $initials ?></div>
                        <div class="profile-info">
                            <span class="profile-name"><?= htmlspecialchars($userName) ?></span>
                            <span class="profile-role">School Administrator</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Onboarding Progress Banner -->
            <?php if (isset($school['onboarding_completed']) && !$school['onboarding_completed']): ?>
            <div class="onboarding-banner">
                <div>
                    <h3>🚀 Complete Your School Setup</h3>
                    <p>Step <?= max(1, $school['onboarding_step'] ?? 1) ?> of 4</p>
                </div>
                <a href="/multi/views/admin/onboarding.php" class="cta">Continue Setup →</a>
            </div>
            <?php endif; ?>

            <!-- Subscription Banner -->
            <?php if ($subscription): ?>
            <div class="subscription-banner">
                <div class="sub-info">
                    <h3>📦 <?= htmlspecialchars($subscription['plan_name']) ?> Plan</h3>
                    <p>Renews on <?= date('d M Y', strtotime($subscription['end_date'])) ?> • KES <?= number_format($subscription['price']) ?>/termly</p>
                </div>
                <div class="sub-badge">Active</div>
            </div>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-info"><h3>Total Students</h3><div class="value"><?= number_format($studentsStmt) ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👨‍🏫</div>
                    <div class="stat-info"><h3>Teaching Staff</h3><div class="value"><?= number_format($teachersStmt) ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏫</div>
                    <div class="stat-info"><h3>Classes</h3><div class="value"><?= number_format($classesStmt) ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info"><h3>Subjects</h3><div class="value"><?= number_format($subjectsStmt) ?></div></div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Quick Actions -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-title">⚡ Quick Actions</div>
                    </div>
                    <div class="actions-grid">
                        <a href="/multi/views/admin/students.php" class="action-card">
                            <div class="icon-wrap">🎓</div><h4>Add Student</h4><p>Register new student</p>
                        </a>
                        <a href="/multi/views/admin/teachers.php" class="action-card">
                            <div class="icon-wrap">👨‍🏫</div><h4>Add Teacher</h4><p>Hire new staff member</p>
                        </a>
                        <a href="/multi/views/admin/academics.php" class="action-card">
                            <div class="icon-wrap">📖</div><h4>Create Class</h4><p>Set up new class/grade</p>
                        </a>
                        <a href="/multi/views/admin/class_assignments.php" class="action-card">
                            <div class="icon-wrap">📚</div><h4>Assign Subjects</h4><p>Map teachers to classes</p>
                        </a>
                        <a href="/multi/views/admin/reports.php" class="action-card">
                            <div class="icon-wrap">📈</div><h4>View Analytics</h4><p>School performance data</p>
                        </a>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-title">🕐 Recent Activity</div>
                    </div>
                    <div class="activity-list">
                        <?php if (empty($recentActivity)): ?>
                        <div style="text-align:center; padding:20px; color:var(--text-muted);">No recent activity</div>
                        <?php else: ?>
                        <?php foreach ($recentActivity as $i => $activity): ?>
                        <div class="activity-item" style="animation-delay: <?= $i * 0.05 ?>s">
                            <div class="activity-icon">✅</div>
                            <div class="activity-content">
                                <div class="activity-title"><?= htmlspecialchars($activity['student_name']) ?> - <?= htmlspecialchars($activity['subject']) ?></div>
                                <div class="activity-time"><?= number_format($activity['marks_obtained'], 1) ?> marks • <?= date('M d, H:i', strtotime($activity['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('overlay').classList.toggle('active');
        }
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.stat-card, .action-card, .activity-item');
            cards.forEach((card, i) => {
                card.style.animationDelay = `${i * 0.05}s`;
            });
        });
    </script>
</body>
</html>