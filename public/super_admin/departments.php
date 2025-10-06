<?php
// public/super_admin/departments.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// Fetch all organizations for dropdowns
$organizations = $db->query("SELECT id, name FROM organizations ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $orgId = $_POST['organization_id'] ?? null;

    if ($name !== '' && $orgId) {
        if ($id) {
            $stmt = $db->prepare("UPDATE departments SET name=?, organization_id=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $orgId, $id]);

            $logAction = "update";
            $logDetails = "Updated department #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO departments (name, organization_id) VALUES (?, ?)");
            $stmt->execute([$name, $orgId]);

            $logAction = "create";
            $logDetails = "Created department ($name)";
        }

        // Log to audit_log
        $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                                 VALUES (?, ?, ?, NOW())");
        $logStmt->execute([Auth::user()['id'], "department_$logAction", $logDetails]);

        header("Location: departments.php?success=1");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM departments WHERE id=?");
    $stmt->execute([$id]);

    $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                             VALUES (?, 'department_delete', ?, NOW())");
    $logStmt->execute([Auth::user()['id'], "Deleted department #$id"]);

    header("Location: departments.php?deleted=1");
    exit;
}

// Fetch all departments with organization names
$stmt = $db->query("
    SELECT d.id, d.name, d.organization_id, o.name AS organization_name
    FROM departments d
    INNER JOIN organizations o ON o.id = d.organization_id
    ORDER BY o.name ASC, d.name ASC
");
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle edit mode
$editDept = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM departments WHERE id=? LIMIT 1");
    $stmt->execute([$_GET['edit']]);
    $editDept = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Manage Departments</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family: Arial, sans-serif; margin:0; }
    header { background:#1e3a8a; color:white; padding:12px 20px; font-size:1.2rem; font-weight:bold; }
    .container { padding:20px; max-width:900px; margin:auto; }
    table { width:100%; border-collapse:collapse; margin-top:20px; background:white; border-radius:8px; overflow:hidden; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
    th { background:#f9fafb; font-weight:600; }
    tr:last-child td { border-bottom:none; }
    .btn { display:inline-block; background:#2563eb; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:13px; }
    .btn:hover { background:#1d4ed8; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }
    form.add-form { background:white; padding:15px; border-radius:8px; margin-top:20px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
    label { font-weight:bold; font-size:13px; display:block; margin-bottom:4px; }
    input, select { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-bottom:10px; }
    footer { background:black; color:white; text-align:center; padding:8px; font-size:13px; margin-top:30px; }
    .alert { padding:8px 12px; border-radius:6px; margin:10px 0; font-size:14px; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
 <h1>Manage Depertments</h1>
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Department saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Department deleted successfully.</div>
  <?php endif; ?>
<!-- Quick Link Card -->
  <div class="card">
    <h3>Quick Links</h3>
    <a href="dashboard.php" class="btn">⬅ Back to Dashboard</a>
    <a href="departments.php" class="btn">🔄 Refresh Workflows</a>
  </div>
  <h2>Departments</h2>
  <?php if (count($departments) > 0): ?>
  <table>
    <tr>
      <th>ID</th>
      <th>Department Name</th>
      <th>Organization</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($departments as $dept): ?>
      <tr>
        <td><?= htmlspecialchars($dept['id']) ?></td>
        <td><?= htmlspecialchars($dept['name']) ?></td>
        <td><?= htmlspecialchars($dept['organization_name']) ?></td>
        <td>
          <a href="departments.php?edit=<?= $dept['id'] ?>" class="btn">Edit</a>
          <a href="departments.php?delete=<?= $dept['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p>No departments found. Please add one below.</p>
  <?php endif; ?>

  <h3><?= isset($editDept) ? 'Edit Department' : 'Add Department' ?></h3>
  <form class="add-form" method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($editDept['id'] ?? '') ?>">

    <label for="organization_id">Organization</label>
    <select id="organization_id" name="organization_id" required>
      <option value="">-- Select Organization --</option>
      <?php foreach ($organizations as $org): ?>
        <option value="<?= $org['id'] ?>" <?= isset($editDept['organization_id']) && $editDept['organization_id'] == $org['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($org['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="name">Department Name</label>
    <input id="name" name="name" value="<?= htmlspecialchars($editDept['name'] ?? '') ?>" required>

    <button type="submit" class="btn"><?= isset($editDept) ? 'Update' : 'Add' ?> Department</button>
  </form>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC </footer>
</body>
</html>
