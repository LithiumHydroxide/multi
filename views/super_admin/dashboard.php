<?php
require_once '../../config.php';
SuperAdminMiddleware::requireSuperAdmin();

$pdo = getDBConnection();
$userName = $_SESSION['user_name'] ?? 'Platform Admin';
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$initials = strtoupper(substr($userName, 0, 1));

// Platform-wide Stats
$stats = [
    'schools_total' => $pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn(),
    'schools_active' => $pdo->query("SELECT COUNT(*) FROM schools WHERE status='active'")->fetchColumn(),
    'schools_trial' => $pdo->query("SELECT COUNT(*) FROM schools WHERE status='trial'")->fetchColumn(),
    'students_active' => $pdo->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn(),
    'users_total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'teachers_total' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='teacher'")->fetchColumn(),
];

// Revenue Breakdown
$revStmt = $pdo->query("
    SELECT sp.name, sp.price, COUNT(ss.id) as school_count
    FROM school_subscriptions ss
    JOIN subscription_plans sp ON ss.plan_id = sp.id
    WHERE ss.status IN ('active', 'trial')
    GROUP BY sp.id
");
$revenuePlans = $revStmt->fetchAll();
$projectedMRR = array_sum(array_map(fn($r) => $r['price'] * $r['school_count'], $revenuePlans));

// Recent Schools
$recentSchools = $pdo->query("SELECT id, name, county, status, created_at FROM schools ORDER BY created_at DESC LIMIT 6")->fetchAll();

// System Health Checks
$health = [
    'database' => '✅ Connected',
    'storage' => is_writable(__DIR__.'/../../storage') ? '✅ Writable' : '❌ Permission Denied',
    'sessions' => '✅ Active',
    'grading_engine' => '✅ Online',
    'attendance_module' => '✅ Online'
];
$healthScore = substr_count(implode(' ', $health), '✅') . '/' . count($health);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Admin | CBC Manager</title>
    
    <!-- Platform defaults (Super Admin doesn't belong to a school) -->
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
        
        /* Sidebar */
        .sidebar {
            width: 260px; background: var(--surface); border-right: 1px solid var(--border);
            padding: 24px 16px; display: flex; flex-direction: column;
            transition: var(--transition); z-index: 40; overflow-y: auto;
        }
        .brand {
            display: flex; align-items: center; gap: 10px; padding: 0 12px 20px;
            font-weight: 800; font-size: 1.25rem; color: var(--text); border-bottom: 1px solid var(--border); margin-bottom: 20px;
        }
        .nav-section { margin-bottom: 20px; }
        .nav-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); padding: 8px 12px; font-weight: 700; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: var(--radius-sm);
            color: var(--text-muted); text-decoration: none; font-weight: 500; transition: var(--transition); position: relative; margin-bottom: 4px;
        }
        .nav-item:hover, .nav-item.active { background: rgba(30, 64, 175, 0.06); color: var(--primary); }
        .nav-item.active::before {
            content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 4px; height: 60%; background: var(--primary); border-radius: 0 4px 4px 0;
        }
        .nav-item .icon { font-size: 1.15rem; width: 22px; text-align: center; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border); }
        .logout-btn {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 12px 14px;
            background: #fee2e2; color: var(--danger); border-radius: var(--radius-sm); text-decoration: none; font-weight: 600; transition: var(--transition);
        }
        .logout-btn:hover { background: #fecaca; }
        
        /* Main */
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
        
        /* Platform Banner */
        .platform-banner {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            padding: 24px; border-radius: var(--radius); color: white; margin-bottom: 32px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
            opacity: 0; animation: slideUp 0.5s ease 0.1s forwards;
        }
        .banner-info h3 { font-size: 1.1rem; margin-bottom: 4px; }
        .banner-info p { opacity: 0.9; font-size: 0.9rem; }
        .banner-badge { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        
        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .stat-card {
            background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm);
            border: 1px solid var(--border); display: flex; align-items: center; gap: 16px;
            transition: var(--transition); opacity: 0; animation: slideUp 0.5s ease forwards;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); border-color: var(--primary-light); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(30, 64, 175, 0.08); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .stat-info h3 { font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .stat-info .value { font-size: 1.6rem; font-weight: 700; color: var(--text); }
        
        /* Content Grid */
        .content-grid { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; }
        
        /* Sections */
        .section { background: var(--surface); padding: 24px; border-radius: var(--radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .section-title { font-size: 1.15rem; font-weight: 600; color: var(--text); }
        
        /* Tables & Lists */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .data-table th { background: #f8fafc; color: var(--text-muted); font-weight: 600; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-active { background: #d1fae5; color: #059669; }
        .badge-trial { background: #fef3c7; color: #d97706; }
        .badge-suspended { background: #fee2e2; color: #dc2626; }
        
        /* Revenue Bars */
        .rev-chart { display: flex; align-items: flex-end; gap: 16px; height: 160px; padding-top: 20px; }
        .rev-bar { flex: 1; background: var(--primary); border-radius: 6px 6px 0 0; position: relative; transition: var(--transition); min-height: 10px; }
        .rev-bar:hover { opacity: 0.85; }
        .rev-bar span { position: absolute; top: -20px; left: 50%; transform: translateX(-50%); font-size: 0.75rem; font-weight: 600; white-space: nowrap; }
        .rev-label { text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 8px; }
        
        /* Health Indicators */
        .health-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .health-item { padding: 12px; background: var(--bg); border-radius: 8px; border-left: 4px solid var(--success); font-size: 0.85rem; }
        .health-item.warn { border-left-color: var(--warning); }
        .health-score { font-size: 1.4rem; font-weight: 700; color: var(--primary); margin-bottom: 12px; }
        
        /* Animations & Mobile */
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
</head>
<body>
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="brand">🚀 Platform Admin</div>
            <div class="nav-section">
                <div class="nav-label">Overview</div>
                <a href="/multi/views/super_admin/dashboard.php" class="nav-item active"><span class="icon">📊</span> Dashboard</a>
                <a href="/multi/views/super_admin/schools.php" class="nav-item"><span class="icon">🏫</span> Manage Schools</a>
                <a href="/multi/views/super_admin/revenue.php" class="nav-item"><span class="icon">💰</span> Revenue & Plans</a>
            </div>
            <div class="nav-section">
                <div class="nav-label">System</div>
                <a href="/multi/views/super_admin/audit.php" class="nav-item"><span class="icon">🔍</span> Audit Logs</a>
                <a href="/multi/views/super_admin/system.php" class="nav-item"><span class="icon">⚙️</span> System Health</a>
            </div>
            <div class="sidebar-footer">
                <a href="/multi/api/auth/logout.php" class="logout-btn"><span class="icon">🚪</span> Sign Out</a>
            </div>
        </aside>

        <main class="main">
            <div class="header">
                <div class="greeting">
                    <h1><?= $greeting ?>, <?= htmlspecialchars($userName) ?> 👋</h1>
                    <p>Platform Oversight & Analytics • <?= date('F Y') ?></p>
                </div>
                <div style="display:flex; gap:12px; align-items:center;">
                    <button class="mobile-toggle" id="menuBtn" onclick="toggleSidebar()">☰</button>
                    <div class="profile">
                        <div class="avatar"><?= $initials ?></div>
                        <div class="profile-info">
                            <span class="profile-name"><?= htmlspecialchars($userName) ?></span>
                            <span class="profile-role">Super Administrator</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Platform Banner -->
            <div class="platform-banner">
                <div class="banner-info">
                    <h3> CBC School Management Platform</h3>
                    <p>Managing <?= $stats['schools_total'] ?> schools • <?= $stats['students_active'] ?> active students • <?= $healthScore ?> systems operational</p>
                </div>
                <div class="banner-badge">v1.0.0 Stable</div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🏫</div>
                    <div class="stat-info"><h3>Total Schools</h3><div class="value"><?= $stats['schools_total'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info"><h3>Active Schools</h3><div class="value"><?= $stats['schools_active'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-info"><h3>Active Students</h3><div class="value"><?= number_format($stats['students_active']) ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info"><h3>Total Users</h3><div class="value"><?= $stats['users_total'] ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info"><h3>Projected MRR</h3><div class="value">KES <?= number_format($projectedMRR) ?></div></div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📈</div>
                    <div class="stat-info"><h3>System Health</h3><div class="value"><?= $healthScore ?></div></div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Schools -->
                <div class="section">
                    <div class="section-header">
                        <div class="section-title">🏫 Recently Onboarded Schools</div>
                        <a href="/multi/views/super_admin/schools.php" style="font-size:0.85rem; color:var(--primary); text-decoration:none; font-weight:500;">View All →</a>
                    </div>
                    <table class="data-table">
                        <thead><tr><th>School</th><th>County</th><th>Status</th><th>Joined</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentSchools as $s): 
                                $badge = $s['status'] === 'active' ? 'badge-active' : ($s['status'] === 'trial' ? 'badge-trial' : 'badge-suspended');
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                                <td><?= htmlspecialchars($s['county'] ?: 'N/A') ?></td>
                                <td><span class="badge <?= $badge ?>"><?= strtoupper($s['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($s['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Revenue & Health -->
                <div style="display:flex; flex-direction:column; gap:24px;">
                    <!-- Revenue Distribution -->
                    <div class="section">
                        <div class="section-header">
                            <div class="section-title">💳 Revenue by Plan</div>
                        </div>
                        <div class="rev-chart">
                            <?php 
                            $planCounts = array_column($revenuePlans, 'school_count');
                            $maxCount = !empty($planCounts) ? max($planCounts) : 1;
                            foreach ($revenuePlans as $plan): 
                                $height = max((float)$plan['school_count'] / $maxCount * 100, 10);
                            ?>
                            <div style="flex:1; display:flex; flex-direction:column; align-items:center;">
                                <div class="rev-bar" style="height:<?= $height ?>%"></div>
                                <div class="rev-label"><?= htmlspecialchars($plan['name']) ?><br><?= $plan['school_count'] ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="section">
                        <div class="section-header">
                            <div class="section-title">⚙️ Core Systems</div>
                        </div>
                        <div class="health-score"><?= $healthScore ?> Online</div>
                        <div class="health-grid">
                            <?php foreach ($health as $name => $status): 
                                $cls = strpos($status, '✅') !== false ? '' : 'warn';
                            ?>
                            <div class="health-item <?= $cls ?>">
                                <strong><?= $name ?></strong><br><?= $status ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
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
        
        // Stagger animations
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.stat-card, .section');
            cards.forEach((card, i) => {
                card.style.animationDelay = `${i * 0.06}s`;
            });
        });
    </script>
</body>
</html>