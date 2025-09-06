<?php require_once __DIR__.'/../src/bootstrap.php';
if (Auth::check()) { header('Location: /CGS/public/dashboard.php'); exit; }
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>CGS — Clearance Gateway System</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
<script defer src="assets/js/app.js"></script>
</head><body>
<header class="site-header"><h1>CGS — Clearance for Graduating Students</h1></header>
<main>
  <p>Track student clearance (UG/PG). Please <a href="login.php">sign in</a> or <a href="register.php">create an account</a>.</p>
</main>
</body></html>
