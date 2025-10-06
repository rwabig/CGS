<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
Auth::requireRole('student');

$pageTitle = "Student Dashboard";
$user = Auth::user();
$db = Database::getConnection();

// fetch latest clearance request
$stmt = $db->prepare("
    SELECT id, status
    FROM clearance_requests
    WHERE student_id = :sid
    ORDER BY requested_at DESC
    LIMIT 1
");
$stmt->execute([':sid' => $user['id']]);
$clearance = $stmt->fetch(PDO::FETCH_ASSOC);

$progress = null;
if ($clearance) {
    $reqId = $clearance['id'];
    $stepsTotal = $db->prepare("SELECT COUNT(*) FROM clearance_steps WHERE clearance_request_id=:id");
    $stepsTotal->execute([':id' => $reqId]);
    $total = (int) $stepsTotal->fetchColumn();

    $stepsDone = $db->prepare("SELECT COUNT(*) FROM clearance_steps WHERE clearance_request_id=:id AND status='approved'");
    $stepsDone->execute([':id' => $reqId]);
    $done = (int) $stepsDone->fetchColumn();

    $progress = [
        'status' => $clearance['status'],
        'done'   => $done,
        'total'  => $total
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $pageTitle ?> - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:1000px; margin:20px auto; padding:20px; }
    .cards { display:grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap:20px; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08); position:relative; }
    h2 { font-size:18px; margin-bottom:10px; }
    p { font-size:14px; color:#374151; }
    .btn { display:inline-block; margin-top:10px; background:#2563eb; color:white; padding:8px 14px; border-radius:6px; text-decoration:none; font-size:14px; }
    .btn:hover { background:#1d4ed8; }
    .progress { background:#e5e7eb; border-radius:8px; height:14px; overflow:hidden; }
    .progress-bar { background:#2563eb; height:14px; }
    .status { font-size:13px; margin-top:6px; color:#111; }
    .card.success { border:2px solid #22c55e; }
    .badge { position:absolute; top:12px; right:12px; background:#22c55e; color:white; padding:4px 8px; border-radius:6px; font-size:12px; font-weight:bold; display:flex; align-items:center; gap:4px; }
    .badge svg { width:14px; height:14px; fill:white; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Welcome, <?= htmlspecialchars($user['email']) ?></h1>
  <p>Your hub for managing clearance requests and viewing certificates.</p>

  <div class="cards">
    <div class="card">
      <h2>Clearance Request</h2>
      <p>Start your clearance process, track progress across departments, and check updates.</p>
      <a href="clearance_request.php" class="btn">Start / Track Clearance</a>

      <?php if ($progress): ?>
        <div style="margin-top:15px;">
          <div class="progress">
            <div class="progress-bar" style="width:<?= $progress['total']>0 ? round(($progress['done']/$progress['total'])*100) : 0 ?>%"></div>
          </div>
          <div class="status">
            <?= $progress['done'] ?>/<?= $progress['total'] ?> steps approved &mdash; Status:
            <strong><?= ucfirst(str_replace('_',' ',$progress['status'])) ?></strong>
          </div>
        </div>
      <?php else: ?>
        <p style="margin-top:10px; color:#6b7280;">No clearance started yet.</p>
      <?php endif; ?>
    </div>

    <div class="card <?= ($progress && $progress['status']==='fully_approved') ? 'success' : '' ?>">
      <h2>Clearance Certificate</h2>
      <p>Once fully approved, view and download your official clearance certificate.</p>
      <a href="certificate.php" class="btn">View Certificate</a>

      <?php if ($progress && $progress['status']==='fully_approved'): ?>
        <div class="badge">
          ✅ Ready
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Student Portal</footer>
</body>
</html>
