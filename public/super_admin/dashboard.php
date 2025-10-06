<?php
// public/super_admin/dashboard.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';
Auth::requireRole('super_admin');

// ensure pageTitle is available to menu
$pageTitle = 'Super Admin Dashboard';

$db = Database::getConnection();

$stats = [
    'Organizations' => quickCount('organizations', $db),
    'Departments'   => quickCount('departments', $db),
    'Categories'    => quickCount('categories', $db),
    'Programs'      => quickCount('programs', $db),
    'Directories'   => quickCount('directories', $db),
    'Sections'      => quickCount('sections', $db),
    'Workflows'     => quickCount('workflows', $db),
    'Audit log'     => quickCount('audit_log', $db),
    'Clear old logs'     => quickCount('clear_old_logs', $db),


];

$pages = [
    'Organizations' => 'organizations.php',
    'Departments'   => 'departments.php',
    'Categories'    => 'categories.php',
    'Programs'      => 'programs.php',
    'Directories'   => 'directories.php',
    'Sections'      => 'sections.php',
    'Workflows'     => 'workflows.php',
    'Audit log'     => 'audit_log.php',
    'Clear old logs'     => 'clear_old_logs.php',

];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Super Admin Dashboard - CGS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body {margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;}
    .container {max-width:1200px;margin:0 auto;padding:20px;}
    h1 {font-size:22px;margin-bottom:10px;}
    .grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;}
    .card {
      background:white;
      border-radius:10px;
      box-shadow:0 4px 10px rgba(0,0,0,0.06);
      padding:20px;
      text-align:center;
      transition:transform 0.15s ease-in-out;
    }
    .card:hover {transform:translateY(-2px);}
    .card-title {font-size:16px;font-weight:bold;margin:0 0 5px;}
    .card-count {font-size:28px;color:#2563eb;margin:0 0 10px;}
    .btn {
      background:#2563eb;
      color:white;
      padding:6px 10px;
      border:none;
      border-radius:6px;
      text-decoration:none;
      display:inline-block;
      font-size:13px;
      transition:background 0.2s ease-in-out;
    }
    .btn:hover {background:#1d4ed8;}
  </style>
</head>
<body>
  <?php
    // include shared menu (safe path)
    $menuPath = __DIR__ . '/../includes/menu.php';
    if (file_exists($menuPath)) {
        include $menuPath;
    } else {
        echo "<!-- menu.php missing at $menuPath -->";
    }
  ?>

  <div class="container">
    <h1>Super Admin Dashboard</h1>
    <p style="color:#6b7280;font-size:14px;margin-bottom:15px;">
      Manage organizational structure, workflows and system-level data.
    </p>

    <div class="grid">
      <?php foreach ($stats as $name => $count): ?>
        <div class="card">
          <div class="card-title"><?= htmlspecialchars($name) ?></div>
          <div class="card-count"><?= $count ?></div>
          <a class="btn" href="<?= htmlspecialchars($pages[$name]) ?>">Manage</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <footer style="text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;">
    © <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC
  </footer>
</body>
</html>
