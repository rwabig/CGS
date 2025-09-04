<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole();
$user = Auth::user();
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Dashboard — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
</head><body>
<header class="site-header"><h1>Welcome, <?=htmlspecialchars($user['name'])?></h1></header>
<main class="grid">
  <a class="card" href="clearance_request.php">New Clearance Request</a>
  <a class="card" href="clearance_status.php">My Clearance Status</a>
  <?php // show admin tools if user has admin role
  $stmt=Database::$pdo->prepare('SELECT COUNT(*) FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=? AND r.slug="admin"');
  $stmt->execute([$_SESSION['uid']]);
  if ($stmt->fetchColumn()) { echo '<a class="card" href="admin/users.php">Admin — Users & Roles</a>'; }
  ?>
  <a class="card" href="logout.php">Log out</a>
</main>
</body></html>
