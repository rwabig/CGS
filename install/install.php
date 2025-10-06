<?php
// install/install.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost        = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort        = trim($_POST['db_port'] ?? '5432');
    $dbName        = trim($_POST['db_name'] ?? 'cgs');
    $dbUser        = trim($_POST['db_user'] ?? 'postgres');
    $dbPass        = trim($_POST['db_pass'] ?? '');
    $adminEmail    = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';
    $adminConfirm  = $_POST['admin_confirm'] ?? '';
    $appUrl        = trim($_POST['app_url'] ?? 'http://localhost/CGS');
    $sessionTimeout= intval($_POST['session_timeout'] ?? 1800);

    // Basic validation
    if ($adminPassword !== $adminConfirm) {
        $error = "Passwords do not match.";
    } elseif (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid or empty admin email.";
    } elseif (strlen($adminPassword) < 6) {
        $error = "Admin password must be at least 6 characters.";
    } else {
        try {
            // 1) Connect to PostgreSQL server (no DB selected)
            $dsn = "pgsql:host={$dbHost};port={$dbPort}";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 2) Create database if not exists (silently ignore failure if exists)
            // Note: CREATE DATABASE cannot run inside a transaction; PDO by default autocommit.
            $createDbSql = "SELECT 1 FROM pg_database WHERE datname = " . $pdo->quote($dbName);
            $exists = $pdo->query($createDbSql)->fetchColumn();
            if (!$exists) {
                $pdo->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING='UTF8'");
            }

            // 3) Connect to the newly created database
            $pdo = new PDO("pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // 4) Ensure pgcrypto extension is available (required for crypt / gen_salt / digest)
            try {
                $pdo->exec("CREATE EXTENSION IF NOT EXISTS pgcrypto");
            } catch (PDOException $pe) {
                // If creating extension fails (permission issue) — surface helpful actionable message
                throw new Exception(
                    "Failed to create required extension pgcrypto. "
                    . "Please enable it and re-run installer. Example (as a DB superuser):\n\n"
                    . "  psql -U postgres -d {$dbName} -c \"CREATE EXTENSION IF NOT EXISTS pgcrypto;\"\n\n"
                    . "Error detail: " . $pe->getMessage()
                );
            }

            // 5) Write .env to project root
            $envContent = <<<ENV
APP_NAME=CGS
APP_ENV=local
APP_URL=$appUrl
SESSION_TIMEOUT=$sessionTimeout

DB_HOST=$dbHost
DB_PORT=$dbPort
DB_NAME=$dbName
DB_USER=$dbUser
DB_PASS=$dbPass

APP_KEY=changeme1234567890securekey
DEBUG=true
ENV;
            $envContent = str_replace(["\r\n", "\r"], "\n", $envContent);
            $envPath = __DIR__ . '/../.env';
            if (file_put_contents($envPath, $envContent) === false) {
                throw new Exception("Failed to write .env file at $envPath");
            }

            // 6) Import schema + seed (attempt)
            $schemaPath = __DIR__ . '/../database/schema.sql';
            $seedPath   = __DIR__ . '/../database/seed.sql';
            if (!file_exists($schemaPath) || !file_exists($seedPath)) {
                throw new Exception("Missing schema.sql or seed.sql in /database. Please ensure files exist.");
            }

            $schemaSql = file_get_contents($schemaPath);
            $seedSql   = file_get_contents($seedPath);

            // Execute schema (may include multiple statements)
            $pdo->exec($schemaSql);

            // Execute seed (may include multiple statements)
            $pdo->exec($seedSql);

            // 7) Insert or update super admin using PHP hash (safe & portable)
            $hash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (email, password_hash, is_active, status)
                VALUES (:email, :hash, TRUE, 'active')
                ON CONFLICT (email)
                DO UPDATE SET password_hash = EXCLUDED.password_hash, is_active = TRUE, status = 'active'
            ");
            $stmt->execute(['email' => $adminEmail, 'hash' => $hash]);

            // retrieve user id
            $userIdStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $userIdStmt->execute(['email' => $adminEmail]);
            $userId = $userIdStmt->fetchColumn();

            // 8) Assign roles: super_admin + admin
            // This inserts rows by selecting role ids for desired slugs.
            $pdo->exec("
                INSERT INTO user_roles (user_id, role_id)
                SELECT {$pdo->quote((int)$userId)}::int, id FROM roles WHERE slug IN ('super_admin','admin')
                ON CONFLICT DO NOTHING
            ");

            // 9) Optional logging: try to include Logger if available
            if (file_exists(__DIR__ . '/../src/Logger.php')) {
                try {
                    require_once __DIR__ . '/../src/Logger.php';
                    if (function_exists('Logger_system')) {
                        // noop — old fallback
                    } else if (class_exists('Logger')) {
                        Logger::system('installation', "System Bootstrap Completed with super admin: {$adminEmail}");
                    }
                } catch (Throwable $logErr) {
                    // don't fail installation just because logging failed
                }
            }

            $message = "✅ Installation completed successfully. Super Admin ({$adminEmail}) created with Admin privileges.";
        } catch (Throwable $e) {
            $error = "Installation failed: " . nl2br(htmlspecialchars($e->getMessage()));
        }
    }
}

// helper for escaping output
function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS Installer</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="../public/assets/css/cgs.css" rel="stylesheet">
  <style>
    /* theme kept consistent */
    html, body {margin:0;padding:0;height:100%;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;display:flex;flex-direction:column;}
    footer {text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;}
    .container {flex:1;display:flex;justify-content:center;align-items:center;padding:12px;}
    .installer-card {background:white;max-width:900px;width:95%;max-height:90vh;overflow-y:auto;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.08);padding:20px 28px;display:flex;flex-direction:column;gap:10px;}
    h1 {font-size:22px;font-weight:bold;text-align:left;margin:0 0 5px;}
    p.intro {font-size:13px;color:#6b7280;text-align:left;margin:0 0 12px;}
    h3 {font-size:15px;margin:5px 0;border-bottom:1px solid #e5e7eb;padding-bottom:3px;font-weight:600;}
    .two-col {display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    .grid {display:grid;grid-template-columns:1fr 1fr;gap:10px;}
    label {font-size:13px;font-weight:600;display:block;margin-bottom:3px;}
    .input-group {position:relative;}
    input {width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;}
    input:focus {outline:2px solid #2563eb;outline-offset:1px;}
    .toggle-password {position:absolute;top:50%;right:10px;transform:translateY(-50%);cursor:pointer;font-size:14px;color:#6b7280;}
    .error-msg {font-size:12px;color:#b91c1c;margin-top:3px;display:none;}
    .btn {display:block;width:100%;background:#2563eb;color:white;padding:12px;text-align:center;border-radius:6px;margin-top:10px;font-size:15px;cursor:pointer;transition:background 0.2s ease-in-out;}
    .btn:hover {background:#1d4ed8;}
    .alert {padding:10px 14px;border-radius:6px;margin-bottom:10px;font-size:14px;}
    .alert-error {background:#fee2e2;color:#991b1b;}
    .alert-success {background:#ecfccb;color:#165314;}
  </style>
</head>
<body>
  <div class="container">
    <div class="installer-card">
      <h1>CGS Installer (PostgreSQL)</h1>
      <p class="intro">
        Provide database connection and initial super admin account.<br>
        This installer will write <code>.env</code>, enable <code>pgcrypto</code>, run <code>schema.sql</code> and <code>seed.sql</code>, and create the super admin.
      </p>

      <?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>
      <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <p><a class="btn" href="../public/login.php">Go to Login</a></p>
      <?php endif; ?>

      <?php if (!$message): ?>
      <form method="post" novalidate id="installer-form">
        <div class="two-col">
          <div>
            <h3>Database Settings</h3>
            <div class="grid">
              <div><label>DB Host</label><input name="db_host" value="<?= e($_POST['db_host'] ?? '127.0.0.1') ?>"></div>
              <div><label>DB Port</label><input name="db_port" value="<?= e($_POST['db_port'] ?? '5432') ?>"></div>
            </div>
            <div class="grid">
              <div><label>Database Name</label><input name="db_name" value="<?= e($_POST['db_name'] ?? 'cgs') ?>"></div>
              <div><label>DB User</label><input name="db_user" value="<?= e($_POST['db_user'] ?? 'postgres') ?>"></div>
            </div>
            <div class="input-group">
              <label>DB Password</label>
              <input id="db-pass" name="db_pass" type="password">
              <span class="toggle-password" data-target="db-pass">👁</span>
            </div>
          </div>

          <div>
            <h3>Initial Super Admin</h3>
            <div>
              <label>Admin Email <span style="color:red">*</span></label>
              <input id="admin-email" type="email" required name="admin_email" value="<?= e($_POST['admin_email'] ?? '') ?>">
              <div class="error-msg" id="email-error">Please enter a valid email.</div>
            </div>
            <div class="grid">
              <div class="input-group"><label>Admin Password</label><input id="admin-password" name="admin_password" type="password"><span class="toggle-password" data-target="admin-password">👁</span></div>
              <div class="input-group"><label>Confirm Password</label><input id="admin-confirm" name="admin_confirm" type="password"><span class="toggle-password" data-target="admin-confirm">👁</span></div>
            </div>
            <div class="error-msg" id="password-error">Passwords must match and be at least 6 characters.</div>
          </div>
        </div>

        <h3>App & Session</h3>
        <div class="grid">
          <div><label>App URL</label><input name="app_url" value="<?= e($_POST['app_url'] ?? 'http://localhost/CGS') ?>"></div>
          <div><label>Session Timeout (seconds)</label><input name="session_timeout" value="<?= e($_POST['session_timeout'] ?? '1800') ?>"></div>
        </div>

        <button type="submit" class="btn">Install / Re-run</button>
        <p class="intro">Re-running will overwrite <code>.env</code> and refresh super admin credentials.</p>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>

  <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', () => {
        const target = document.getElementById(icon.dataset.target);
        target.type = target.type === 'password' ? 'text' : 'password';
        icon.textContent = target.type === 'text' ? '🙈' : '👁';
      });
    });

    const emailInput = document.getElementById('admin-email');
    const passInput = document.getElementById('admin-password');
    const confirmInput = document.getElementById('admin-confirm');
    const emailError = document.getElementById('email-error');
    const passError = document.getElementById('password-error');

    function validateEmail() {
      const v = emailInput.value.trim();
      const valid = v.length > 0 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
      emailError.style.display = valid ? 'none' : 'block';
      return valid;
    }
    function validatePasswordMatch() {
      const p = passInput.value;
      const c = confirmInput.value;
      const ok = p.length >= 6 && p === c;
      passError.style.display = ok ? 'none' : 'block';
      return ok;
    }

    emailInput.addEventListener('input', validateEmail);
    passInput.addEventListener('input', validatePasswordMatch);
    confirmInput.addEventListener('input', validatePasswordMatch);

    document.getElementById('installer-form').addEventListener('submit', (e) => {
      if (!validateEmail() || !validatePasswordMatch()) {
        e.preventDefault();
        alert('Please fix validation errors before proceeding.');
      }
    });
  </script>
</body>
</html>
