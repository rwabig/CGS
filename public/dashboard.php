<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();

$db = Database::getConnection();
$stmt = $db->prepare("
    SELECT r.slug FROM roles r
    INNER JOIN user_roles ur ON ur.role_id = r.id
    WHERE ur.user_id = :user_id
");
$stmt->execute(['user_id' => $user['id']]);
$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Redirect based on priority: super_admin > admin > signatory > student
if (in_array('super_admin', $roles)) {
    header("Location: super_admin/dashboard.php");
    exit;
} elseif (in_array('admin', $roles)) {
    header("Location: admin/dashboard.php");
    exit;
} elseif (in_array('signatory', $roles)) {
    header("Location: signatory/dashboard.php");
    exit;
} elseif (in_array('student', $roles)) {
    header("Location: student/dashboard.php");
    exit;
} else {
    // ✅ Log event into audit_log
    try {
        $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                                 VALUES (:user_id, :action, :details, NOW())");
        $logStmt->execute([
            'user_id' => $user['id'],
            'action' => 'no_roles_access',
            'details' => 'User tried to access dashboard but has no roles assigned.'
        ]);
    } catch (PDOException $e) {
        // Fails silently to avoid breaking page rendering
    }

    // Fallback page if a user has no roles
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <title>CGS | Dashboard</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="assets/css/cgs.css" rel="stylesheet">
      <style>
        body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .fallback-card { background: white; border-radius: 12px; padding: 30px; text-align: center; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 400px; width: 90%; }
        h1 { font-size: 20px; margin-bottom: 10px; }
        p { font-size: 14px; color: #555; }
        a { color: #2563eb; text-decoration: none; }
        /* Toast styling */
        .toast {
          visibility: hidden;
          min-width: 250px;
          background-color: #dc2626;
          color: white;
          text-align: center;
          border-radius: 8px;
          padding: 12px;
          position: fixed;
          z-index: 9999;
          left: 50%;
          bottom: 30px;
          transform: translateX(-50%);
          font-size: 14px;
          opacity: 0;
          transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }
        .toast.show {
          visibility: visible;
          opacity: 1;
        }
      </style>
    </head>
    <body>
      <div class="fallback-card">
        <h1>No Role Assigned</h1>
        <p>Your account does not have any roles assigned. Please contact an administrator to get access.</p>
        <p><a href="logout.php">Logout</a></p>
      </div>

      <!-- Toast -->
      <div id="toast" class="toast">⚠️ Your account has no roles assigned. Contact admin.</div>

      <script>
        // Show toast automatically
        window.onload = () => {
          const toast = document.getElementById('toast');
          toast.classList.add('show');
          setTimeout(() => toast.classList.remove('show'), 4000);
        };
      </script>
    </body>
    </html>
    <?php
}
