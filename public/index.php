<?php
// public/index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/bootstrap.php';

if (Auth::check()) {
    // Fetch roles for logged-in user
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT r.slug FROM roles r
        INNER JOIN user_roles ur ON ur.role_id = r.id
        WHERE ur.user_id = :user_id
    ");
    $stmt->execute(['user_id' => Auth::user()['id']]);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Redirect based on priority
    if (in_array('super_admin', $roles)) {
        header("Location: super_admin/dashboard.php");
    } elseif (in_array('admin', $roles)) {
        header("Location: admin/dashboard.php");
    } elseif (in_array('signatory', $roles)) {
        header("Location: staff/dashboard.php");
    } elseif (in_array('student', $roles)) {
        header("Location: student/dashboard.php");
    } else {
        header("Location: dashboard.php");
    }
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Welcome</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      background: #f3f4f6;
      font-family: Arial, Helvetica, sans-serif;
      display: flex;
      flex-direction: column;
    }
    footer {
      text-align: center;
      padding: 8px 0;
      background: #000;
      color: white;
      font-size: 13px;
    }
    .container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 12px;
    }
    .home-card {
      background: white;
      max-width: 420px;
      width: 95%;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.08);
      padding: 28px 28px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      text-align: center;
    }
    h1 {
      font-size: 24px;
      font-weight: bold;
      margin: 0 0 10px;
    }
    p.intro {
      font-size: 14px;
      color: #6b7280;
      margin: 0 0 16px;
    }
    .btn {
      display: block;
      width: 100%;
      background: #2563eb;
      color: white;
      padding: 12px;
      text-align: center;
      border-radius: 6px;
      margin-top: 8px;
      font-size: 15px;
      cursor: pointer;
      transition: background 0.2s ease-in-out;
      text-decoration: none;
    }
    .btn:hover {
      background: #1d4ed8;
    }
    .btn-secondary {
      background: #6b7280;
    }
    .btn-secondary:hover {
      background: #4b5563;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="home-card">
      <h1>🎓 Welcome to CGS</h1>
      <p class="intro">
        University <strong>Clearance for Graduating Students</strong><br><br>
        Please log in or register to continue.
      </p>

      <a href="login.php" class="btn">🔑 Login</a>
      <a href="register.php" class="btn btn-secondary">📝 Register</a>
    </div>
  </div>

  <footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
