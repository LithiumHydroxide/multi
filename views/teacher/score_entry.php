<?php
require_once '../../config.php';

// Auth & Multi-Tenant Check
$schoolId = getAuthenticatedSchoolId();
$userId   = getAuthenticatedUserId();
if (!$schoolId || !$userId) {
    header('Location: ../auth/login.php');
    exit;
}

$pdo = getDBConnection();

// Fetch Classes for Dropdown
$classes = $pdo->query("SELECT id, name, grade_level FROM classes WHERE school_id = $schoolId ORDER BY grade_level, name")->fetchAll();

// Fetch Assessments for Dropdown (default: all for selected class)
$assessments = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Scores | CBC Manager</title>
    <style>
        :root {
            --primary: #1e40af;
            --primary-hover: #1d4ed8;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --ee: #10b981; --ee-bg: #d1fae5;
            --me: #3b82f6; --me-bg: #dbeafe;
            --ae: #f59e0b; --ae-bg: #fef3c7;
            --be: #ef4444; --be-bg: #fee2e2;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); line-height: 1.5; padding: 20px; }

        .container { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .header h1 { font-size: 1.5rem; font-weight: 700; }
        .badge { background: var(--primary); color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; }

        .controls { background: var(--card); padding: 16px; border-radius: var(--radius); box-shadow: var(--shadow); display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .control-group label { display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 4px; }
        .control-group select, .control-group button { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; background: white; }
        .control-group button { background: var(--primary); color: white; border: none; cursor: pointer; transition: 0.2s; font-weight: 600; }
        .control-group button:hover:not(:disabled) { background: var(--primary-hover); }
        .control-group button:disabled { opacity: 0.6; cursor: not-allowed; }

        .table-wrapper { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: #f1f5f9; font-weight: 600; color: var(--text-muted); position: sticky; top: 0; }
        tr:hover { background: #fafafa; }
        input[type="number"] { width: 80px; padding: 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 0.95rem; text-align: center; }
        input[type="number"]:focus { outline: 2px solid var(--primary); border-color: transparent; }

        .level-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; min-width: 50px; text-align: center; }
        .level-EE1, .level-EE2 { background: var(--ee-bg); color: var(--ee); }
        .level-ME1, .level-ME2 { background: var(--me-bg); color: var(--me); }
        .level-AE1, .level-AE2 { background: var(--ae-bg); color: var(--ae); }
        .level-BE1, .level-BE2 { background: var(--be-bg); color: var(--be); }

        .actions { margin-top: 20px; display: flex; gap: 12px; justify-content: flex-end; }
        .btn-save { background: var(--success); color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-save:hover { background: #047857; }
        .btn-clear { background: white; border: 1px solid var(--border); padding: 12px 24px; border-radius: 8px; cursor: pointer; }

        .toast { position: fixed; bottom: 24px; right: 24px; padding: 14px 20px; border-radius: 8px; color: white; font-weight: 500; transform: translateY(100px); opacity: 0; transition: 0.3s; z-index: 1000; }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s linear infinite; margin-right: 8px; vertical-align: middle; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
            .controls { grid-template-columns: 1fr; }
            th, td { padding: 10px; font-size: 0.9rem; }
            input[type="number"] { width: 70px; }
        }
    </style>
<body>
    <div class="container">
        <div class="header" style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <div style="display:flex;gap:12px;align-items:center">
                <h1 style="margin:0">📝 Enter Assessment Scores</h1>
                <span class="badge">CBC Grading Engine</span>
            </div>
            <a href="/multi/views/teacher/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
        </div>

        <div class="controls">
            <div class="control-group">
                <label for="classSelect">Select Class</label>
                <select id="classSelect">
                    <option value="">-- Choose Class --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (Grade <?= $c['grade_level'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="control-group">
                <label for="assessmentSelect">Select Assessment</label>
                <select id="assessmentSelect" disabled>
                    <option value="">-- Choose Assessment --</option>
                </select>
            </div>
            <div class="control-group">
                <label>&nbsp;</label>
                <button id="loadBtn" disabled>Load Student List</button>
            </div>
        </div>

        <div class="table-wrapper">
            <table id="scoreTable">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Student Name</th>
                        <th>Adm. No</th>
                        <th>Marks</th>
                        <th>Achievement Level</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="studentRows">
                    <tr><td colspan="6" class="empty-state">Select a class and assessment to load students</td></tr>
                </tbody>
            </table>
        </div>

        <div class="actions">
            <button class="btn-clear" id="clearBtn">Clear All</button>
            <button class="btn-save" id="saveBtn" disabled>
                <span class="spinner" id="spinner"></span>
                💾 Save Scores
            </button>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        const API_BASE = '/multi/api/assessments';
        const state = {
            classId: null,
            assessmentId: null,
            maxMarks: 0,
            students: []
        };

        // Elements
        const classSel = document.getElementById('classSelect');
        const assessSel = document.getElementById('assessmentSelect');
        const loadBtn = document.getElementById('loadBtn');
        const saveBtn = document.getElementById('saveBtn');
        const clearBtn = document.getElementById('clearBtn');
        const tbody = document.getElementById('studentRows');
        const spinner = document.getElementById('spinner');
        const toast = document.getElementById('toast');

        // 1. Load Assessments when Class changes
        classSel.addEventListener('change', async () => {
            state.classId = classSel.value;
            assessSel.innerHTML = '<option value="">-- Loading --</option>';
            assessSel.disabled = true;
            loadBtn.disabled = true;

            if (!state.classId) {
                assessSel.innerHTML = '<option value="">-- Choose Assessment --</option>';
                return;
            }

            try {
                const res = await fetch(`${API_BASE}/load_assessments.php?class_id=${state.classId}`);
                const data = await res.json();
                assessSel.innerHTML = '<option value="">-- Choose Assessment --</option>';
                data.forEach(a => {
                    assessSel.innerHTML += `<option value="${a.id}" data-max="${a.max_marks}">${a.title} (${a.term})</option>`;
                });
                assessSel.disabled = false;
            } catch (e) {
                assessSel.innerHTML = '<option value="">Error loading assessments</option>';
            }
        });

        // 2. Enable Load Button when Assessment selected
        assessSel.addEventListener('change', () => {
            const opt = assessSel.options[assessSel.selectedIndex];
            state.assessmentId = assessSel.value;
            state.maxMarks = parseFloat(opt.dataset.max) || 100;
            loadBtn.disabled = !state.assessmentId;
        });

        // 3. Load Students
        loadBtn.addEventListener('click', async () => {
            loadBtn.disabled = true;
            loadBtn.textContent = 'Loading...';
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Loading student list...</td></tr>';

            try {
                const res = await fetch(`${API_BASE}/load_students.php?class_id=${state.classId}`);
                state.students = await res.json();
                renderTable();
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">Failed to load students</td></tr>';
            } finally {
                loadBtn.disabled = false;
                loadBtn.textContent = 'Load Student List';
                saveBtn.disabled = false;
            }
        });

        // 4. Render Table
        function renderTable() {
            if (state.students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No students found in this class</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            state.students.forEach((s, idx) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${idx + 1}</td>
                    <td>${s.name}</td>
                    <td>${s.admission_number}</td>
                    <td><input type="number" min="0" max="${state.maxMarks}" data-id="${s.id}" class="mark-input" placeholder="0-${state.maxMarks}"></td>
                    <td><span class="level-badge">-</span></td>
                    <td><input type="text" class="remark-input" data-id="${s.id}" placeholder="Optional"></td>
                `;
                tbody.appendChild(tr);
            });
            attachInputListeners();
        }

        // 5. Real-time Level Calculation
        function attachInputListeners() {
            document.querySelectorAll('.mark-input').forEach(input => {
                input.addEventListener('input', (e) => {
                    const val = parseFloat(e.target.value);
                    const badge = e.target.closest('tr').querySelector('.level-badge');
                    if (isNaN(val) || val < 0) {
                        badge.className = 'level-badge';
                        badge.textContent = '-';
                        return;
                    }
                    const pct = (val / state.maxMarks) * 100;
                    const level = getAchievementLevel(pct);
                    badge.textContent = level;
                    badge.className = `level-badge level-${level}`;
                });
            });
        }

        function getAchievementLevel(pct) {
            if (pct >= 90) return 'EE1';
            if (pct >= 80) return 'EE2';
            if (pct >= 70) return 'ME1';
            if (pct >= 65) return 'ME2';
            if (pct >= 55) return 'AE1';
            if (pct >= 50) return 'AE2';
            if (pct >= 40) return 'BE1';
            return 'BE2';
        }

        // 6. Save Scores
        saveBtn.addEventListener('click', async () => {
            const scores = [];
            document.querySelectorAll('.mark-input').forEach(input => {
                if (input.value !== '') {
                    scores.push({
                        student_id: input.dataset.id,
                        marks: parseFloat(input.value),
                        remark: input.closest('tr').querySelector('.remark-input').value
                    });
                }
            });

            if (scores.length === 0) {
                showToast('Please enter at least one score', 'error');
                return;
            }

            saveBtn.disabled = true;
            spinner.style.display = 'inline-block';

            try {
                const res = await fetch(`${API_BASE}/score_entry.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        assessment_id: state.assessmentId,
                        scores: scores
                    })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(`✅ ${data.message}`, 'success');
                    // Clear inputs after save
                    document.querySelectorAll('.mark-input').forEach(i => i.value = '');
                    document.querySelectorAll('.remark-input').forEach(i => i.value = '');
                    document.querySelectorAll('.level-badge').forEach(b => { b.textContent = '-'; b.className = 'level-badge'; });
                } else {
                    showToast('❌ ' + (data.error || 'Save failed'), 'error');
                }
            } catch (e) {
                showToast('❌ Network error. Check console.', 'error');
            } finally {
                saveBtn.disabled = false;
                spinner.style.display = 'none';
            }
        });

        clearBtn.addEventListener('click', () => {
            if(confirm('Clear all entered marks?')) {
                document.querySelectorAll('.mark-input').forEach(i => i.value = '');
                document.querySelectorAll('.remark-input').forEach(i => i.value = '');
                document.querySelectorAll('.level-badge').forEach(b => { b.textContent = '-'; b.className = 'level-badge'; });
            }
        });

        function showToast(msg, type) {
            toast.textContent = msg;
            toast.className = `toast show ${type}`;
            setTimeout(() => toast.classList.remove('show'), 4000);
        }
    </script>
</body>
</html>