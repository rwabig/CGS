<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

// Allow Admin + Super Admin
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();

$userId = (int)($_GET['user'] ?? 0);
if (!$userId) redirect('users.php');

// Protect core system accounts
$protectedEmails = [];
$envDefault = getenv('DEFAULT_SUPER_ADMIN_EMAIL');
if ($envDefault) $protectedEmails[] = $envDefault;
$protectedEmails[] = 'rwabig@gmail.com';

// Get base user info
$stmt = $db->prepare("SELECT u.email, r.slug AS role
                      FROM users u
                      JOIN user_roles ur ON ur.user_id = u.id
                      JOIN roles r ON ur.role_id = r.id
                      WHERE u.id=:id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) redirect('users.php');
$email = $user['email'];
$role  = $user['role'];

$error = '';
$success = '';

// Load existing profile data
$profile = [];
switch ($role) {
    case 'student':
        $stmt = $db->prepare("SELECT full_name, registration_number, phone FROM student_profiles WHERE user_id=:id");
        break;
    case 'signatory': // staff
        $stmt = $db->prepare("SELECT full_name, phone, position_title, cheque_number FROM staff_profiles WHERE user_id=:id");
        break;
    case 'admin':
        $stmt = $db->prepare("SELECT full_name, phone, position_title, cheque_number FROM admin_profiles WHERE user_id=:id");
        break;
    default:
        $stmt = null;
}
if ($stmt) {
    $stmt->execute([':id' => $userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Fetch signatory titles from workflows
$signatoryTitles = $db->query("SELECT DISTINCT signatory_title FROM workflows ORDER BY signatory_title")
                      ->fetchAll(PDO::FETCH_COLUMN);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (in_array($email, $protectedEmails, true)) {
        $error = "This account is protected and cannot be edited.";
    } else {
        try {
            if ($role === 'student') {
                $stmt = $db->prepare("UPDATE student_profiles
                                      SET full_name=:fn, registration_number=:reg, phone=:ph, updated_at=NOW()
                                      WHERE user_id=:id");
                $stmt->execute([
                    ':fn' => trim($_POST['full_name'] ?? ''),
                    ':reg'=> trim($_POST['registration_number'] ?? ''),
                    ':ph' => trim($_POST['phone'] ?? ''),
                    ':id' => $userId
                ]);
            } elseif ($role === 'signatory') {
                $stmt = $db->prepare("UPDATE staff_profiles
                                      SET full_name=:fn, phone=:ph, position_title=:pt, cheque_number=:chq, updated_at=NOW()
                                      WHERE user_id=:id");
                $stmt->execute([
                    ':fn' => trim($_POST['full_name'] ?? ''),
                    ':ph' => trim($_POST['phone'] ?? ''),
                    ':pt' => trim($_POST['position_title'] ?? ''),
                    ':chq'=> trim($_POST['cheque_number'] ?? ''),
                    ':id' => $userId
                ]);
            } elseif ($role === 'admin') {
                $stmt = $db->prepare("UPDATE admin_profiles
                                      SET full_name=:fn, phone=:ph, position_title=:pt, cheque_number=:chq, updated_at=NOW()
                                      WHERE user_id=:id");
                $stmt->execute([
                    ':fn' => trim($_POST['full_name'] ?? ''),
                    ':ph' => trim($_POST['phone'] ?? ''),
                    ':pt' => trim($_POST['position_title'] ?? ''),
                    ':chq'=> trim($_POST['cheque_number'] ?? ''),
                    ':id' => $userId
                ]);
            }

            logAudit('edit_user', "Edited profile for $email (role=$role)");
            $success = "User profile updated successfully.";
        } catch (Throwable $e) {
            $error = "Update failed: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Edit User - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:600px; margin:30px auto; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h1 { font-size:20px; margin-bottom:15px; }
    label { font-weight:600; font-size:13px; display:block; margin:8px 0 3px; }
    input, select { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; }
    .btn { background:#2563eb; color:white; padding:10px 14px; border:none; border-radius:6px; cursor:pointer; margin-top:12px; }
    .btn:hover { background:#1d4ed8; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
  </style>
</head>
<body>
 <?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Edit <?= ucfirst($role) ?> Profile</h1>

    <?php if ($error): ?><div class="alert-error"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= $success ?></div><?php endif; ?>

    <form method="post">
      <label>Email (read-only)</label>
      <input type="text" value="<?= htmlspecialchars($email) ?>" disabled>

      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? $profile['full_name'] ?? '') ?>" required>

      <?php if ($role === 'student'): ?>
        <label>Registration Number</label>
        <input type="text" name="registration_number" value="<?= htmlspecialchars($_POST['registration_number'] ?? $profile['registration_number'] ?? '') ?>">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? $profile['phone'] ?? '') ?>">
      <?php elseif (in_array($role, ['signatory','admin'])): ?>
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? $profile['phone'] ?? '') ?>">

        <label>Position Title</label>
        <select name="position_title" required>
          <option value="">-- Select Position --</option>
          <?php foreach ($signatoryTitles as $title): ?>
            <option value="<?= htmlspecialchars($title) ?>"
              <?= (($profile['position_title'] ?? '') === $title || ($_POST['position_title'] ?? '') === $title) ? 'selected' : '' ?>>
              <?= htmlspecialchars($title) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Cheque Number</label>
        <input type="text" name="cheque_number" value="<?= htmlspecialchars($_POST['cheque_number'] ?? $profile['cheque_number'] ?? '') ?>">
      <?php endif; ?>

      <button type="submit" class="btn">Save Changes</button>
    </form>

    <p style="margin-top:15px;"><a href="users.php?role=<?= htmlspecialchars($role) ?>">← Back to Users</a></p>
  </div>
</div>
<footer style="text-align:center;padding:8px 0;background:#000;color:white;font-size:13px;">
    © <?= date('Y') ?> University Digital Clearance System | Admin Panel
  </footer>
</body>
</html>
