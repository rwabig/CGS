<?php
// public/admin/assign_signatory.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    redirect('../login.php');
}

$db = Database::getConnection();
$userId = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$error = '';
$success = '';

if ($userId <= 0) {
    redirect('users.php?role=signatory');
}

// Get signatory info
$stmt = $db->prepare("
    SELECT u.id, u.email, sp.full_name
    FROM users u
    JOIN staff_profiles sp ON sp.user_id = u.id
    WHERE u.id = :id
");
$stmt->execute([':id' => $userId]);
$signatory = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$signatory) {
    redirect('users.php?role=signatory');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departmentId = $_POST['department_id'] ?? null;
    $categoryId   = $_POST['category_id'] ?? null;
    $directoryId  = $_POST['directory_id'] ?? null;
    $sectionId    = $_POST['section_id'] ?? null;

    if ($departmentId && $categoryId) {
        $stmt = $db->prepare("
            INSERT INTO signatory_assignments (user_id, department_id, category_id, directory_id, section_id, created_at)
            VALUES (:uid, :dept, :cat, :dir, :sec, NOW())
            ON CONFLICT (user_id, department_id, category_id, directory_id, section_id) DO NOTHING
        ");
        $stmt->execute([
            ':uid'  => $userId,
            ':dept' => $departmentId,
            ':cat'  => $categoryId,
            ':dir'  => $directoryId ?: null,
            ':sec'  => $sectionId ?: null
        ]);

        logAudit('assign_signatory', "Assigned signatory={$userId} dept=$departmentId cat=$categoryId dir=$directoryId sec=$sectionId");
        $success = "Signatory assignment saved successfully.";
    } else {
        $error = "Please select department and category at minimum.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Assign Signatory - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:700px; margin:auto; padding:20px; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }
    h1 { font-size:22px; margin-bottom:15px; text-align:center; }
    label { font-size:13px; font-weight:600; display:block; margin:10px 0 3px; }
    select { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; font-size:14px; }
    .btn { display:inline-block; background:#2563eb; color:white; padding:10px 16px; border:none; border-radius:6px; font-size:14px; cursor:pointer; margin-top:15px; }
    .btn:hover { background:#1d4ed8; }
    .alert { padding:10px; border-radius:6px; margin-bottom:15px; font-size:14px; }
    .alert-error { background:#fee2e2; color:#991b1b; }
    .alert-success { background:#ecfccb; color:#166534; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Assign Signatory</h1>
    <p><strong>Signatory:</strong> <?= htmlspecialchars($signatory['full_name'] ?? '-') ?> (<?= htmlspecialchars($signatory['email']) ?>)</p>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post">
      <label>Department</label>
      <select name="department_id" id="department-select" required>
        <option value="">-- Select Department --</option>
      </select>

      <label>Category</label>
      <select name="category_id" id="category-select" required>
        <option value="">-- Select Category --</option>
      </select>

      <label>Directory</label>
      <select name="directory_id" id="directory-select">
        <option value="">-- Optional: Directory --</option>
      </select>

      <label>Section</label>
      <select name="section_id" id="section-select">
        <option value="">-- Optional: Section --</option>
      </select>

      <button type="submit" class="btn">Save Assignment</button>
      <a href="users.php?role=signatory" class="btn" style="background:#6b7280;">Cancel</a>
    </form>
  </div>
</div>
</body>
</html>
