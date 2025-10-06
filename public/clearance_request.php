<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole('student');

$user = Auth::user();
$pdo = Database::getConnection();

// Check if a clearance request already exists
$stmt = $pdo->prepare("SELECT id, status FROM clearance_requests WHERE student_id = ?");
$stmt->execute([$user['id']]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Request Clearance</title>
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
    header { background: #1e3a8a; color: white; padding: 12px 20px; font-weight: bold; font-size: 1.2rem; }
    .container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 20px; }
    .card {
      background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08);
      max-width: 500px; width: 95%; padding: 20px; text-align: center;
    }
    h1 { margin-top: 0; font-size: 1.4rem; }
    p { font-size: 0.95rem; color: #555; margin-bottom: 15px; }
    .btn-request {
      display: inline-block; background: #2563eb; color: white;
      padding: 12px 16px; border-radius: 6px; text-decoration: none;
      font-weight: bold; font-size: 1rem; cursor: pointer;
    }
    .btn-request:disabled { background: #9ca3af; cursor: not-allowed; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; margin-top: auto; }
    .toast {
      position: fixed; bottom: 20px; right: 20px;
      background: #2563eb; color: white; padding: 10px 16px; border-radius: 6px;
      opacity: 0; transform: translateY(20px); transition: opacity 0.3s, transform 0.3s;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
  </style>
</head>
<body>
<header>CGS | Request Clearance</header>

<div class="container">
  <div class="card">
    <h1>Request Your Clearance</h1>
    <?php if ($request): ?>
      <p>Your clearance request is already <strong><?= htmlspecialchars($request['status']) ?></strong>.</p>
      <a href="clearance_status.php" class="btn-request">View Clearance Status</a>
    <?php else: ?>
      <p>Click the button below to initiate your clearance process. This will generate all clearance steps for your department and start the approval workflow.</p>
      <button id="requestBtn" class="btn-request">Request Clearance</button>
    <?php endif; ?>
  </div>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU</footer>

<div id="toast" class="toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('requestBtn');
  const toast = document.getElementById('toast');

  if (btn) {
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      try {
        const res = await fetch('../api/create_clearance.php', { method: 'POST' });
        const data = await res.json();

        toast.textContent = data.message || 'Clearance request created successfully!';
        toast.classList.add('show');

        if (data.status === 'success') {
          setTimeout(() => window.location.href = 'clearance_status.php', 1800);
        } else {
          btn.disabled = false;
        }

        setTimeout(() => toast.classList.remove('show'), 3000);
      } catch (err) {
        toast.textContent = '❌ Failed to create clearance. Try again.';
        toast.classList.add('show');
        btn.disabled = false;
        setTimeout(() => toast.classList.remove('show'), 3000);
      }
    });
  }
});
</script>
</body>
</html>
?>
