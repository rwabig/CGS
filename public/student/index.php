<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('student');

$user = Auth::user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Student</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
    header { background: #2563eb; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; justify-content: center; align-items: center; flex-direction: column; text-align: center; }
    .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 500px; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
  </style>
</head>
<body>
<header>CGS | Student</header>
<div class="container">
  <div class="card">
    <h1>Welcome, <?= htmlspecialchars($user['name'] ?? $user['email']) ?></h1>
    <p>You are logged in as <strong>Student</strong>.</p>
    <p>This is a placeholder dashboard. You can submit clearance requests and track progress here.</p>
    <p><a href="../logout.php" style="color:#dc2626;">Logout</a></p>
  </div>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU</footer>
</body>
</html>
