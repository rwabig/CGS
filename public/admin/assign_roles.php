<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();

$userId = (int)($_GET['user'] ?? 0);
if (!$userId) redirect('users.php');

// Protected emails
$protectedEmails = [];
$envDefault = getenv('DEFAULT_SUPER_ADMIN_EMAIL');
if ($envDefault) $protectedEmails[] = $envDefault;
$protectedEmails[] = 'rwabig@gmail.com';

$stmt = $db->prepare("SELECT email FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$email = $stmt->fetchColumn();
if (!$email) redirect('users.php');

$error = '';
$success = '';

// Handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($email, $protectedEmails, true)) {
        $error = "Protected account roles cannot be changed.";
    } else {
        $selectedRoles = $_POST['roles'] ?? [];

        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM user_roles WHERE user_id=:id")->execute([':id' => $userId]);

            $stmt = $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:uid, :rid)");
            foreach ($selectedRoles as $rid) {
                $stmt->execute([':uid' => $userId, ':rid' => (int)$rid]);
            }

            $db->commit();
            logAudit('assign_roles', "Assigned roles to $email: " . implode(',', $selectedRoles));
            $success = "Roles updated successfully.";
        } catch (Throwable $e) {
            $db->rollBack();
            $error = "Failed to assign roles: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Fetch roles and current assignments
$roles = $db->query("SELECT id, name, slug FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$assigned = $db->prepare("SELECT role_id FROM user_roles WHERE user_id=:id");
$assigned->execute([':id' => $userId]);
$assignedIds = $assigned->fetchAll(PDO::FETCH_COLUMN);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Assign Roles - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:600px; margin:30px auto; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h1 { font-size:20px; margin-bottom:15px; }
    label { display:block; margin:6px 0; }
    .btn { background:#2563eb; color:white; padding:8px 14px; border:none; border-radius:6px; cursor:pointer; margin-top:10px; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
  </style>
</head>
<body>
 <?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Assign Roles for <?= htmlspecialchars($email) ?></h1>

    <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>

    <form method="post">
      <?php foreach ($roles as $r): ?>
        <label>
          <input type="checkbox" name="roles[]" value="<?= $r['id'] ?>"
            <?= in_array($r['id'], $assignedIds) ? 'checked' : '' ?>>
          <?= htmlspecialchars($r['name']) ?> (<?= htmlspecialchars($r['slug']) ?>)
        </label>
      <?php endforeach; ?>
      <button type="submit" class="btn">Save Roles</button>
    </form>

    <p><a href="users.php">← Back to Users</a></p>
  </div>
</div>
<footer style="text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;">
    © <?= date('Y') ?> University Digital Clearance System | Admin Panel
  </footer>
</body>
</html>
