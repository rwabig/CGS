<?php
// public/register.php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/includes/helpers.php';
Auth::guestOnly();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($password) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        try {
            $pdo  = Database::getConnection();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            // ✅ PostgreSQL boolean: TRUE not 1
            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, is_active, status)
                VALUES (:email, :hash, TRUE, 'active')
                RETURNING id
            ");
            $stmt->execute([':email'=>$email, ':hash'=>$hash]);

            $userId = $stmt->fetchColumn();

            if (!$userId) {
                throw new RuntimeException("User ID not returned.");
            }

            // Assign "student" role
            $stmtRole = $pdo->prepare("
                INSERT INTO user_roles (user_id, role_id)
                SELECT :uid, id FROM roles WHERE slug='student'
            ");
            $stmtRole->execute([':uid'=>$userId]);

            // Audit log
            logAudit('user_register', "New user registered with email {$email}");

            // Auto-login
            $_SESSION['user_id'] = $userId;
            header("Location: student/profile.php");
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() == '23505') { // unique_violation
                $error = "This email is already registered.";
            } else {
                $error = "Registration failed. Please try again later.";
                logAudit('db_error', "Register error: " . $e->getMessage());
            }
        } catch (Throwable $e) {
            $error = "Unexpected error occurred.";
            logAudit('system_error', $e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Account - CGS</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    html, body {margin:0;padding:0;height:100%;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;display:flex;flex-direction:column;}
    footer {text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;}
    .container {flex:1;display:flex;justify-content:center;align-items:center;padding:12px;}
    .register-card {background:white;max-width:400px;width:95%;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.08);padding:20px 28px;display:flex;flex-direction:column;gap:10px;}
    h1 {font-size:22px;font-weight:bold;text-align:left;margin:0 0 5px;}
    p.intro {font-size:13px;color:#6b7280;text-align:left;margin:0 0 12px;}
    label {font-size:13px;font-weight:600;display:block;margin-bottom:3px;}
    .input-group {position:relative;margin-bottom:8px;}
    input {width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;}
    input:focus {outline:2px solid #2563eb;outline-offset:1px;}
    .toggle-password {position:absolute;top:50%;right:10px;transform:translateY(-50%);cursor:pointer;font-size:14px;color:#6b7280;}
    .btn {display:block;width:100%;background:#2563eb;color:white;padding:12px;text-align:center;border-radius:6px;margin-top:10px;font-size:15px;cursor:pointer;transition:background 0.2s ease-in-out;}
    .btn:hover {background:#1d4ed8;}
    .alert-error {background:#fee2e2;color:#991b1b;padding:10px;border-radius:6px;font-size:14px;margin-bottom:10px;}
    .links {margin-top:8px;font-size:13px;text-align:center;}
    .links a {color:#2563eb;text-decoration:none;}
  </style>
</head>
<body>
  <div class="container">
    <div class="register-card">
      <h1>Create Account</h1>
      <p class="intro">Fill in your details to create a CGS account.</p>

      <?php if (!empty($error)): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div>
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="input-group">
          <label>Password</label>
          <input id="password" type="password" name="password" required>
          <span class="toggle-password" data-target="password">👁</span>
        </div>
        <div class="input-group">
          <label>Confirm Password</label>
          <input id="confirm_password" type="password" name="confirm_password" required>
          <span class="toggle-password" data-target="confirm_password">👁</span>
        </div>

        <button type="submit" class="btn">Create Account</button>
      </form>

      <div class="links">
        <p>Already have an account? <a href="login.php">Login</a></p>
        <p><a href="index.php">← Back to Home</a></p>
      </div>
    </div>
  </div>

  <footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>

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
