<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
Auth::requireRole('signatory');

$pageTitle = "Signatory Dashboard";
$user = Auth::user();
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
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.08); }
    h2 { font-size:18px; margin-bottom:10px; }
    p { font-size:14px; color:#374151; }
    .btn { display:inline-block; margin-top:10px; background:#2563eb; color:white; padding:8px 14px; border-radius:6px; text-decoration:none; font-size:14px; }
    .btn:hover { background:#1d4ed8; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Welcome, <?= htmlspecialchars($user['email']) ?></h1>
  <p>Here you can review and sign clearance requests assigned to you.</p>

  <div class="cards">
    <div class="card">
      <h2>Pending Clearance Tasks</h2>
      <p>Review student clearance requests and approve or reject as appropriate.</p>
      <a href="clearance_tasks.php" class="btn">View Tasks</a>
    </div>
  </div>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Signatory Portal</footer>
</body>
</html>
