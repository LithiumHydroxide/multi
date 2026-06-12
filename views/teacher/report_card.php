<?php
require_once '../../config.php';
require_once '../../app/Helpers/KJSEACalculator.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('teacher'); // Or school_admin
$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Fetch Branding & Logo
$settingsStmt = $pdo->prepare("SELECT primary_color, logo_path FROM school_settings WHERE school_id = ?");
$settingsStmt->execute([$schoolId]);
$settings = $settingsStmt->fetch();
$logoPath = $settings['logo_path'] ?? null;

$classes = $pdo->query("SELECT id, name FROM classes WHERE school_id = $schoolId ORDER BY grade_level, name")->fetchAll();
$studentId = $_GET['student_id'] ?? null;

if (!$studentId) {
// 1. Selector View
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Report | CBC Manager</title>
    <?= getBrandingCSS($schoolId) ?>
    <style>
        :root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
        body{background:var(--bg);padding:24px}
        .container{max-width:900px;margin:0 auto}
        .header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
        .card{background:var(--card);padding:24px;border-radius:12px;box-shadow:0 2px 4px rgba(0,0,0,0.05)}
        .sel{display:flex;gap:12px;margin-bottom:24px;align-items:flex-end}
        select,button{padding:12px;border:1px solid var(--border);border-radius:8px;font-size:0.95rem}
        button{background:var(--primary);color:#fff;cursor:pointer;border:none}
        button:hover{opacity:0.9}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
        .student-card{background:#fff;padding:16px;border:1px solid var(--border);border-radius:8px;cursor:pointer;transition:0.2s}
        .student-card:hover{border-color:var(--primary);box-shadow:0 4px 12px rgba(0,0,0,0.1);transform:translateY(-2px)}
        .name{font-weight:600;color:var(--text)}
        .adm{font-size:0.8rem;color:var(--muted);margin-top:4px}
        .empty-state{text-align:center;padding:40px;color:var(--muted);grid-column:1/-1}
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 Generate Report Card</h1>
        <a href="/multi/views/teacher/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid var(--border);border-radius:6px;text-decoration:none;color:var(--text);font-weight:500">← Back</a>
    </div>
    <div class="card">
        <div class="sel">
            <div style="flex:1">
                <label style="display:block;font-size:0.8rem;color:var(--muted);margin-bottom:6px">Select Class</label>
                <select id="cls" style="width:100%">
                    <option value="">-- Select a Class --</option>
                    <?php foreach($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button onclick="loadStudents()"> Load Students</button>
        </div>
        <div class="grid" id="list">
            <div class="empty-state">Select a class to view students.</div>
        </div>
    </div>
</div>

<script>
async function loadStudents(){
    const cid = document.getElementById('cls').value;
    const list = document.getElementById('list');
    
    if(!cid) {
        list.innerHTML = '<div class="empty-state">Please select a class first.</div>';
        return;
    }
    
    list.innerHTML = '<div class="empty-state">Loading students...</div>';
    
    try {
        // ✅ FIX: Pass class_id to the API for server-side filtering
        const res = await fetch(`/multi/api/student/crud.php?action=list&class_id=${cid}`);
        
        if (!res.ok) {
            throw new Error('Network response was not ok');
        }
        
        const d = await res.json();
        
        // ✅ FIX: Ensure the response is an array before processing
        if (!Array.isArray(d)) {
            throw new Error(d.error || 'Invalid data format from server');
        }
        
        if(d.length === 0){
            list.innerHTML = '<div class="empty-state">No active students found in this class.</div>';
            return;
        }
        
        list.innerHTML = d.map(st => `
            <div class="student-card" onclick="window.location.href='?student_id=${st.id}'">
                <div class="name">🎓 ${st.name}</div>
                <div class="adm">ADM: ${st.admission_number}</div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading students:', error);
        list.innerHTML = `<div class="empty-state" style="color:var(--danger)">Error loading students: ${error.message}</div>`;
    }
}
</script>
</body>
</html>
<?php
exit;
}

// 2. Report Generation View
$calculator = new KJSEACalculator();
$report = $calculator->getStudentReport((int)$studentId, $schoolId);
if (isset($report['error'])) die('<h3>Error: ' . htmlspecialchars($report['error']) . '</h3>');
$student = $report['student'];
$subjects = $report['subjects'];
$kjsea = $report['kjsea'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card | <?= htmlspecialchars($student['name']) ?></title>
    <?= getBrandingCSS($schoolId) ?>
    <style>
        :root{--primary:#1e40af;--border:#cbd5e1;--bg:#f8fafc;--text:#0f172a;--muted:#64748b;--success:#059669;--warning:#d97706;--info:#2563eb}
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Segoe UI','Roboto',system-ui,sans-serif;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;padding:20px}
        .report{max-width:900px;margin:0 auto;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);overflow:hidden}
        .header{background:linear-gradient(135deg, var(--primary) 0%, #0c3a8f 100%);color:#fff;padding:40px;text-align:center;position:relative;overflow:hidden}
        .header::before{content:'';position:absolute;top:-50%;right:-10%;width:300px;height:300px;background:rgba(255,255,255,0.1);border-radius:50%;animation:float 6s ease-in-out infinite}
        .header::after{content:'';position:absolute;bottom:-50%;left:-5%;width:250px;height:250px;background:rgba(255,255,255,0.05);border-radius:50%;animation:float 8s ease-in-out infinite reverse}
        @keyframes float{0%,100%{transform:translateY(0px)}50%{transform:translateY(20px)}}
        .header h1{font-size:2rem;letter-spacing:1px;margin-bottom:4px;position:relative;z-index:1}
        .header p{font-size:1rem;opacity:0.95;position:relative;z-index:1}
        .meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;padding:32px;background:#f8fafc;border-bottom:2px solid #e2e8f0}
        .meta-item{display:flex;flex-direction:column}
        .meta-label{font-size:0.75rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:700;margin-bottom:4px}
        .meta-value{font-size:1rem;color:var(--text);font-weight:600}
        .content{padding:40px}
        .section-title{font-size:1.3rem;color:var(--primary);margin-bottom:20px;border-bottom:3px solid var(--primary);padding-bottom:12px;display:flex;align-items:center;gap:10px}
        .table-wrapper{margin-bottom:32px;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
        .performance-table{width:100%;border-collapse:collapse;background:#fff}
        .performance-table thead{background:linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);}
        .performance-table th{padding:16px;text-align:center;font-weight:700;color:var(--muted);font-size:0.9rem;border-bottom:2px solid #cbd5e1}
        .performance-table td{padding:14px 16px;border-bottom:1px solid #e2e8f0;text-align:center}
        .performance-table td:first-child{text-align:left;font-weight:600;color:var(--primary)}
        .performance-table tbody tr:hover{background:#f8fafc}
        .performance-table tbody tr:nth-child(even){background:#fafbfc}
        .grade-badge{display:inline-block;padding:6px 14px;border-radius:20px;font-weight:700;font-size:0.85rem;letter-spacing:0.5px}
        .grade-badge.ee{background:linear-gradient(135deg, #d1fae5, #a7f3d0);color:#065f46}
        .grade-badge.me{background:linear-gradient(135deg, #dbeafe, #bfdbfe);color:#1e40af}
        .grade-badge.ae{background:linear-gradient(135deg, #fef3c7, #fde68a);color:#92400e}
        .grade-badge.be{background:linear-gradient(135deg, #fee2e2, #fecaca);color:#7f1d1d}
        .kjsea-box{background:linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);border-left:6px solid var(--info);padding:24px;border-radius:12px;margin-bottom:32px}
        .kjsea-box h3{color:var(--info);margin-bottom:8px;font-size:1.1rem}
        .kjsea-table{width:100%;margin-top:16px}
        .kjsea-table th{background:#e0f2fe;color:var(--info);padding:12px;text-align:center;font-weight:700}
        .kjsea-table td{padding:12px;border-bottom:1px solid #cffafe;text-align:center}
        .kjsea-table td:first-child{text-align:left;font-weight:600;color:var(--text)}
        .remarks-box{background:#fef8e7;border-left:6px solid #f59e0b;padding:20px;border-radius:8px;margin-bottom:24px}
        .remarks-box h4{color:#d97706;margin-bottom:8px}
        .signature-section{display:grid;grid-template-columns:repeat(2,1fr);gap:40px;margin-top:48px;padding-top:32px;border-top:2px solid #e2e8f0}
        .signature-block{text-align:center}
        .signature-line{border-top:2px solid var(--muted);margin-bottom:8px;height:50px}
        .signature-name{font-size:0.85rem;color:var(--muted);font-weight:600;text-transform:uppercase}
        .actions{text-align:center;padding:24px;background:#f8fafc;border-top:2px solid #e2e8f0;display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
        .btn{padding:12px 32px;background:var(--primary);color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:0.3s;font-size:0.95rem;text-decoration:none}
        .btn:hover{background:#1d4ed8;transform:translateY(-2px);box-shadow:0 8px 16px rgba(30,64,175,0.3)}
        .btn-secondary{background:#6b7280}
        .btn-secondary:hover{background:#4b5563}
        .no-data{text-align:center;padding:32px;color:var(--muted);font-style:italic}
        @media print{
            body{background:#fff;padding:0}
            .report{box-shadow:none;border-radius:0}
            .actions{display:none}
            .header::before,.header::after{display:none}
        }
    </style>
</head>
<body>
<div class="report">
    <div class="header">
        <!-- SCHOOL LOGO -->
        <?php if ($logoPath): ?>
            <img src="<?= htmlspecialchars($logoPath) ?>" alt="School Logo" style="max-height: 70px; margin-bottom: 15px; object-fit: contain; position: relative; z-index: 1;">
        <?php endif; ?>
        
        <h1>📊 ACADEMIC PERFORMANCE REPORT</h1>
        <p>CBC Learning Areas Assessment & Progress Report</p>
    </div>
    
    <div class="meta">
        <div class="meta-item">
            <div class="meta-label">Student Name</div>
            <div class="meta-value"><?= htmlspecialchars($student['name']) ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Admission Number</div>
            <div class="meta-value"><?= htmlspecialchars($student['admission_number']) ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Class / Stream</div>
            <div class="meta-value"><?= htmlspecialchars($student['class_name']) ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Grade Level</div>
            <div class="meta-value">Grade <?= $student['grade_level'] ?? 'N/A' ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">Report Generated</div>
            <div class="meta-value"><?= date('d M Y') ?></div>
        </div>
        <div class="meta-item">
            <div class="meta-label">School</div>
            <div class="meta-value"><?= htmlspecialchars($pdo->query("SELECT name FROM schools WHERE id=$schoolId")->fetchColumn()) ?></div>
        </div>
    </div>

    <div class="content">
        <div class="section-title">📈 Learning Area Performance</div>
        <div class="table-wrapper">
            <table class="performance-table">
                <thead>
                    <tr>
                        <th>Learning Area</th>
                        <th>Term 1 (%)</th>
                        <th>Term 2 (%)</th>
                        <th>Term 3 (%)</th>
                        <th>Overall (%)</th>
                        <th>Achievement Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (empty($subjects)):
                        echo '<tr><td colspan="6" class="no-data">No assessment data available</td></tr>';
                    else:
                        foreach ($subjects as $subj):
                            $lvl = substr($subj['final_level'], 0, 2);
                            $cls = strtolower($lvl);
                            $overall = number_format($subj['overall_avg'],1);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($subj['name']) ?></td>
                                <td><?= number_format($subj['term_1_avg'],1) ?>%</td>
                                <td><?= number_format($subj['term_2_avg'],1) ?>%</td>
                                <td><?= number_format($subj['term_3_avg'],1) ?>%</td>
                                <td><strong><?= $overall ?>%</strong></td>
                                <td><span class="grade-badge <?= $cls ?>"><?= htmlspecialchars($subj['final_level']) ?></span></td>
                            </tr>
                            <?php 
                        endforeach;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($kjsea && !empty($kjsea)): ?>
        <div class="kjsea-box">
            <h3>🎓 KJSEA Composite Score Projection</h3>
            <p style="font-size:0.9rem;color:var(--muted);margin-bottom:12px">
                Weighted Score: 20% Grade 7 + 20% Grade 8 + 60% Grade 9 Final Assessment
            </p>
            <table class="kjsea-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Composite Score (%)</th>
                        <th>Projected Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kjsea as $code => $data): ?>
                    <tr>
                        <td><?= htmlspecialchars($code) ?></td>
                        <td><strong><?= number_format($data['projected_kjsea'],1) ?>%</strong></td>
                        <td><span class="grade-badge <?= strtolower(substr($data['level'],0,2)) ?>"><?= htmlspecialchars($data['level']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="remarks-box">
            <h4> Teacher Remarks & Comments</h4>
            <p style="font-size:0.95rem;line-height:1.6">
                The student has demonstrated consistent performance across learning areas.
                Commendable effort is noted in class participation and assignment completion.
                Continue to focus on enhancing practical skills in science and mathematics concepts.
            </p>
        </div>

        <div class="signature-section">
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Class Teacher Signature & Date</div>
            </div>
            <div class="signature-block">
                <div class="signature-line"></div>
                <div class="signature-name">Principal / Head Teacher & Seal</div>
            </div>
        </div>
    </div>
    
    <div class="actions">
        <button class="btn" onclick="window.print()">🖨️ Print / Save as PDF</button>
        <a href="/multi/views/teacher/report_card.php" class="btn btn-secondary">← Select Another Student</a>
    </div>
</div>
</body>
</html>