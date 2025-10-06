<?php
// public/login.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/bootstrap.php';
Auth::guestOnly();

$error = '';
$success = '';

if (isset($_GET['logged_out']) && $_GET['logged_out'] == 1) {
    $success = "You have been logged out successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($email, $password)) {
        // Update last_login timestamp
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([Auth::user()['id']]);
        } catch (PDOException $e) {
            // Fail silently if logging last_login fails (don't block login)
            error_log("Failed to update last_login for {$email}: " . $e->getMessage());
        }

        // Redirect based on role
        if (Auth::hasRole('super_admin')) {
            header('Location: super_admin/dashboard.php');
        } elseif (Auth::hasRole('admin')) {
            header('Location: admin/dashboard.php');
        } elseif (Auth::hasRole('signatory')) {
            header('Location: signatory/dashboard.php');
        } elseif (Auth::hasRole('student')) {
            header('Location: student/dashboard.php');
        } else {
            header('Location: dashboard.php'); // fallback
        }
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    html, body {margin:0;padding:0;height:100%;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;display:flex;flex-direction:column;}
    footer {text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;}
    .container {flex:1;display:flex;justify-content:center;align-items:center;padding:12px;}
    .login-card {background:white;max-width:400px;width:95%;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.08);padding:20px 28px;display:flex;flex-direction:column;gap:10px;}
    h1 {font-size:22px;font-weight:bold;text-align:center;margin:0 0 10px;}
    p.intro {font-size:13px;color:#6b7280;text-align:center;margin:0 0 12px;}
    label {font-size:13px;font-weight:600;display:block;margin-bottom:3px;}
    .input-group {position:relative;}
    input {width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;}
    input:focus {outline:2px solid #2563eb;outline-offset:1px;}
    .toggle-password {position:absolute;top:50%;right:10px;transform:translateY(-50%);cursor:pointer;font-size:14px;color:#6b7280;}
    .btn {display:block;width:100%;background:#2563eb;color:white;padding:12px;text-align:center;border-radius:6px;margin-top:10px;font-size:15px;cursor:pointer;transition:background 0.2s ease-in-out;border:none;}
    .btn:hover {background:#1d4ed8;}
    .alert {padding:10px 14px;border-radius:6px;margin-bottom:10px;font-size:14px;}
    .alert-error {background:#fee2e2;color:#991b1b;}
    .alert-success {background:#ecfccb;color:#166534;}
    .links {text-align:center;font-size:13px;margin-top:5px;}
    .links a {color:#2563eb;text-decoration:none;}
  </style>
</head>
<body>
  <div class="container">
    <div class="login-card">
      <h1>CGS Login</h1>
      <p class="intro">Enter your credentials to access the system.</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
          <span class="toggle-password" data-target="password">👁</span>
        </div>

        <button type="submit" class="btn">Login</button>
      </form>
      <div class="links">
        <p><a href="register.php">Don't have an account? Register</a></p>
        <p><a href="index.php">← Back to Home</a></p>
      </div>
    </div>
  </div>

  <footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powered by: UCC</footer>

  <script>
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', () => {
        const target = document.getElementById(icon.dataset.target);
        target.type = target.type === 'password' ? 'text' : 'password';
        icon.textContent = target.type === 'text' ? '🙈' : '👁';
      });
    });
  </script>
</body>
</html>
