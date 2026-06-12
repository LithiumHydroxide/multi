<?php
require_once '../../config.php';
AuthMiddleware::requireAuth();
AuthMiddleware::requireRole('school_admin');

$schoolId = getAuthenticatedSchoolId();
$pdo = getDBConnection();

// Current plan
$stmt = $pdo->prepare("SELECT sp.name, sp.price, sp.billing_cycle, sp.features_json, ss.end_date FROM school_subscriptions ss JOIN subscription_plans sp ON ss.plan_id = sp.id WHERE ss.school_id = ?");
$stmt->execute([$schoolId]);
$currentPlan = $stmt->fetch();

// All available plans
$plans = $pdo->query("SELECT id, name, price, billing_cycle, max_students FROM subscription_plans ORDER BY price")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription & Modules | CBC Manager</title>
    <style>
        :root{--primary:#1e40af;--bg:#f8fafc;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b;--success:#059669;--radius:12px}
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',system-ui,sans-serif}
        body{background:var(--bg);color:var(--text);padding:24px}
        .container{max-width:900px;margin:0 auto}
        h1{margin-bottom:20px;font-size:1.5rem}
        .current-plan{background:var(--card);padding:20px;border-radius:var(--radius);box-shadow:0 2px 4px rgba(0,0,0,0.05);margin-bottom:24px;border-left:4px solid var(--success)}
        .plans-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px}
        .plan-card{background:var(--card);border:2px solid var(--border);border-radius:var(--radius);padding:20px;cursor:pointer;transition:0.2s;position:relative}
        .plan-card:hover,.plan-card.selected{border-color:var(--primary);box-shadow:0 4px 12px rgba(30,64,175,0.1)}
        .plan-card h3{margin-bottom:8px}
        .price{font-size:1.4rem;font-weight:700;color:var(--primary);margin-bottom:12px}
        .features{list-style:none;margin-bottom:16px}
        .features li{padding:4px 0;color:var(--muted);font-size:0.9rem}
        .features li::before{content:"✓ ";color:var(--success)}
        .module-toggle{display:flex;gap:12px;margin-top:12px;padding-top:12px;border-top:1px dashed var(--border)}
        .toggle{display:flex;align-items:center;gap:6px;font-size:0.85rem;color:var(--muted);cursor:pointer}
        .toggle input{accent-color:var(--primary)}
        .actions{margin-top:24px;text-align:center}
        .btn{padding:12px 24px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:600;cursor:pointer}
        .btn:hover{background:#1d4ed8}
        .badge{position:absolute;top:12px;right:12px;background:var(--success);color:#fff;padding:4px 8px;border-radius:20px;font-size:0.7rem;font-weight:600}
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
            <h1 style="margin:0">💳 Subscription & Module Selection</h1>
            <a href="/multi/views/admin/dashboard.php" style="padding:8px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;text-decoration:none;color:#0f172a;font-weight:500;font-size:0.9rem;cursor:pointer;transition:0.2s;display:inline-flex;align-items:center;gap:6px;white-space:nowrap" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">← Back</a>
        </div>
        
        <?php if($currentPlan): ?>
        <div class="current-plan">
            <strong>Current Plan:</strong> <?= htmlspecialchars($currentPlan['name']) ?> | 
            <strong>Price:</strong> KES <?= number_format($currentPlan['price']) ?>/<?= $currentPlan['billing_cycle'] ?> | 
            <strong>Expires:</strong> <?= date('d M Y', strtotime($currentPlan['end_date'])) ?>
        </div>
        <?php endif; ?>

        <h2 style="margin-bottom:12px;">Choose Your Plan & Modules</h2>
        <div class="plans-grid" id="plansGrid">
            <?php foreach($plans as $p): ?>
            <div class="plan-card" data-id="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                <?php if($currentPlan && $currentPlan['name'] === $p['name']): ?><span class="badge">ACTIVE</span><?php endif; ?>
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <div class="price">KES <?= number_format($p['price']) ?> <span style="font-size:0.8rem;color:var(--muted)">/<?= $p['billing_cycle'] ?></span></div>
                <ul class="features">
                    <li>Up to <?= $p['max_students'] ?> students</li>
                    <li>✅ Manual Timetable</li>
                    <?php if($p['id'] >= 3): ?><li>🤖 AI Timetable Generator</li><li>📊 Advanced Analytics</li><li>👨‍👩‍👧 Parent Portal</li><?php endif; ?>
                </ul>
                <div class="module-toggle">
                    <label class="toggle"><input type="checkbox" checked disabled> MIS Platform</label>
                    <label class="toggle"><input type="checkbox" name="module_payments" data-plan="<?= $p['id'] ?>"> Payment Gateway</label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <button class="btn" id="activateBtn">Activate Selected Plan</button>
        </div>
    </div>

    <script>
        const grid = document.getElementById('plansGrid');
        const activateBtn = document.getElementById('activateBtn');
        let selectedPlan = null;

        grid.addEventListener('click', (e) => {
            const card = e.target.closest('.plan-card');
            if (!card) return;
            grid.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedPlan = parseInt(card.dataset.id);
            activateBtn.disabled = false;
        });

        activateBtn.addEventListener('click', async () => {
            if (!selectedPlan) return alert('Please select a plan first');
            activateBtn.textContent = 'Activating...';
            activateBtn.disabled = true;

            const paymentsCheckbox = document.querySelector(`.plan-card[data-id="${selectedPlan}"] input[name="module_payments"]`);
            const modules = { payments: paymentsCheckbox?.checked || false };

            try {
                const res = await fetch('/multi/api/subscription/activate_plan.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ plan_id: selectedPlan, modules })
                });
                const data = await res.json();
                if (data.success) {
                    alert('✅ Plan activated! Refresh to see updates.');
                    location.reload();
                } else {
                    alert('❌ ' + (data.error || 'Activation failed'));
                }
            } catch (err) {
                alert('❌ Network error');
            } finally {
                activateBtn.textContent = 'Activate Selected Plan';
                activateBtn.disabled = false;
            }
        });
    </script>
</body>
</html>