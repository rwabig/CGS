<?php
// public/super_admin/organizations.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name !== '' && $code !== '') {
        if ($id) {
            $stmt = $db->prepare("UPDATE organizations SET name=?, code=?, description=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$name, $code, $desc, $id]);

            $logAction = "update";
            $logDetails = "Updated organization #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO organizations (name, code, description, created_at, updated_at)
                                  VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$name, $code, $desc]);

            $logAction = "create";
            $logDetails = "Created organization ($name)";
        }

        // Log to audit_log
        $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                                 VALUES (?, ?, ?, NOW())");
        $logStmt->execute([Auth::user()['id'], "organization_$logAction", $logDetails]);

        header("Location: organizations.php?success=1");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM organizations WHERE id=?");
    $stmt->execute([$id]);

    $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                             VALUES (?, 'organization_delete', ?, NOW())");
    $logStmt->execute([Auth::user()['id'], "Deleted organization #$id"]);

    header("Location: organizations.php?deleted=1");
    exit;
}

// Fetch Organizations
$organizations = $db->query("SELECT * FROM organizations ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Manage Organizations</title>
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
    input, textarea { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-bottom:10px; }
    footer { background:black; color:white; text-align:center; padding:8px; font-size:13px; margin-top:30px; }
    .alert { padding:8px 12px; border-radius:6px; margin:10px 0; font-size:14px; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
 <h1>Manage Organization</h1>
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Organization saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Organization deleted successfully.</div>
  <?php endif; ?>
<!-- Quick Links -->
  <div class="card">
    <h3>Quick Links</h3>
    <a href="dashboard.php" class="btn">⬅ Back to Dashboard</a>
    <a href="organizations.php" class="btn">🔄 Refresh Directories</a>
  </div>
  <h2>Organizations</h2>
  <?php if (count($organizations) > 0): ?>
  <table>
    <tr>
      <th>ID</th>
      <th>Code</th>
      <th>Name</th>
      <th>Description</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($organizations as $org): ?>
      <tr>
        <td><?= htmlspecialchars($org['id']) ?></td>
        <td><?= htmlspecialchars($org['code']) ?></td>
        <td><?= htmlspecialchars($org['name']) ?></td>
        <td><?= htmlspecialchars($org['description']) ?></td>
        <td>
          <a href="organizations.php?edit=<?= $org['id'] ?>" class="btn">Edit</a>
          <a href="organizations.php?delete=<?= $org['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p>No organizations found. Please add one below.</p>
  <?php endif; ?>

  <h3><?= isset($_GET['edit']) ? 'Edit Organization' : 'Add Organization' ?></h3>
  <?php
  $editOrg = null;
  if (isset($_GET['edit'])) {
      $stmt = $db->prepare("SELECT * FROM organizations WHERE id=? LIMIT 1");
      $stmt->execute([$_GET['edit']]);
      $editOrg = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  ?>
  <form class="add-form" method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($editOrg['id'] ?? '') ?>">
    <label for="code">Organization Code</label>
    <input id="code" name="code" value="<?= htmlspecialchars($editOrg['code'] ?? '') ?>" required>

    <label for="name">Organization Name</label>
    <input id="name" name="name" value="<?= htmlspecialchars($editOrg['name'] ?? '') ?>" required>

    <label for="description">Description</label>
    <textarea id="description" name="description"><?= htmlspecialchars($editOrg['description'] ?? '') ?></textarea>

    <button type="submit" class="btn"><?= isset($editOrg) ? 'Update' : 'Add' ?> Organization</button>
  </form>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
