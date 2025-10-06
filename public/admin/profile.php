<?php
// public/admin/profile.php
require_once __DIR__ . '/../../src/bootstrap.php';

// Allow both Admin and Super Admin
if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$user = Auth::user();
$db   = Database::getConnection();

$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$error = '';
$success = '';

// Fetch profile
$stmt = $db->prepare("SELECT full_name, phone, position_title, cheque_number, avatar
                      FROM admin_profiles WHERE user_id = :id");
$stmt->execute([':id' => $user['id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Fetch signatory titles from workflows
$signatoryTitles = $db->query("SELECT DISTINCT signatory_title FROM workflows ORDER BY signatory_title")
                      ->fetchAll(PDO::FETCH_COLUMN);

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position_title'] ?? '');
    $cheque   = trim($_POST['cheque_number'] ?? '');

    // Handle photo upload
    $photoPath = $profile['avatar'] ?? null;
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png'];
        if (!in_array($ext, $allowed)) {
            $error = "Invalid photo format. Allowed: jpg, jpeg, png.";
        } else {
            $newName = 'admin_' . $user['id'] . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $dest)) {
                $photoPath = 'uploads/' . $newName;
            } else {
                $error = "Failed to upload the photo.";
            }
        }
    }

    if (!$error) {
        try {
            $stmt = $db->prepare("
                INSERT INTO admin_profiles (user_id, full_name, phone, position_title, cheque_number, avatar, updated_at)
                VALUES (:uid, :fn, :ph, :pos, :chq, :av, NOW())
                ON CONFLICT (user_id) DO UPDATE
                SET full_name = EXCLUDED.full_name,
                    phone = EXCLUDED.phone,
                    position_title = EXCLUDED.position_title,
                    cheque_number = EXCLUDED.cheque_number,
                    avatar = EXCLUDED.avatar,
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':uid' => $user['id'],
                ':fn'  => $fullName,
                ':ph'  => $phone,
                ':pos' => $position,
                ':chq' => $cheque,
                ':av'  => $photoPath
            ]);

            logAudit('admin_profile_update', "Updated admin profile for user_id={$user['id']}");
            $success = "Profile updated successfully.";

            // Refresh profile
            $stmt = $db->prepare("SELECT full_name, phone, position_title, cheque_number, avatar
                                  FROM admin_profiles WHERE user_id = :id");
            $stmt->execute([':id' => $user['id']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Profile - CGS</title>
  <link rel="stylesheet" href="../assets/css/cgs.css">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; }
    .container { max-width:700px; margin:30px auto; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h2 { text-align:center; margin-bottom:15px; }
    label { font-weight:600; margin-top:10px; display:block; }
    input[type=text], input[type=file], select { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; margin-top:5px; }
    button { background:#2563eb; color:white; padding:10px; border:none; border-radius:6px; cursor:pointer; width:100%; margin-top:15px; }
    button:hover { background:#1d4ed8; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
    .alert-success { background:#ecfccb; color:#166534; padding:10px; border-radius:6px; margin-bottom:10px; }
    img { margin-top:15px; border-radius:8px; border:2px solid #004080; }
    .back-link { display:inline-block; margin-bottom:15px; text-decoration:none; color:#2563eb; }
    .back-link:hover { text-decoration:underline; }
    footer { background:black; color:white; text-align:center; padding:8px; font-size:13px; margin-top:30px; }
  </style>
</head>
<body>
<header>Clearance for Graduating Students (CGS)</header>
<div class="container">
  <div class="card">
    <a href="users.php?role=admin" class="back-link">&larr; Back to Admins</a>
    <h2>Update Profile</h2>

    <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Full Name</label>
      <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>" required>

      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">

      <label>Position Title</label>
      <select name="position_title" required>
        <option value="">-- Select Position --</option>
        <?php foreach ($signatoryTitles as $title): ?>
          <option value="<?= htmlspecialchars($title) ?>"
            <?= (($profile['position_title'] ?? '') === $title) ? 'selected' : '' ?>>
            <?= htmlspecialchars($title) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Cheque Number</label>
      <input type="text" name="cheque_number" value="<?= htmlspecialchars($profile['cheque_number'] ?? '') ?>">

      <label>Profile Photo</label>
      <input type="file" name="photo" accept="image/*">
      <?php if (!empty($profile['avatar'])): ?>
        <div style="text-align:center;">
          <img src="../<?= htmlspecialchars($profile['avatar']) ?>" alt="Admin Photo" width="120" height="120">
        </div>
      <?php endif; ?>

      <button type="submit">Save Profile</button>
    </form>
  </div>
</div>
<footer>
  © <?= date('Y') ?> University Digital Clearance System | Case study: ARU | Developed by Rwabigimbo &amp; Team | Empowered by UCC
</footer>
</body>
</html>
