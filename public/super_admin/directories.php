<?php
// public/super_admin/directories.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// ---- Fetch organizations for dropdown ----
$organizations = $db->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ---- Handle Create / Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?? null;
    $org  = $_POST['organization_id'] ?? null;
    $name = trim($_POST['name'] ?? '');

    if ($org && $name !== '') {
        if ($id) {
            $stmt = $db->prepare("UPDATE directories SET organization_id=?, name=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$org, $name, $id]);
            $logAction = "update";
            $logDetails = "Updated directory #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO directories (organization_id, name, created_at, updated_at)
                                  VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$org, $name]);
            $logAction = "create";
            $logDetails = "Created directory ($name)";
        }

        $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                      VALUES (?, ?, ?, NOW())")
           ->execute([Auth::user()['id'], "directory_$logAction", $logDetails]);

        header("Location: directories.php?success=1");
        exit;
    }
}

// ---- Handle Delete ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM directories WHERE id=?");
    $stmt->execute([$id]);

    $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                  VALUES (?, 'directory_delete', ?, NOW())")
       ->execute([Auth::user()['id'], "Deleted directory #$id"]);

    header("Location: directories.php?deleted=1");
    exit;
}

// ---- Fetch directories with organization context ----
$directories = $db->query("
    SELECT d.id, d.name, o.name AS organization_name
    FROM directories d
    JOIN organizations o ON d.organization_id = o.id
    ORDER BY o.name, d.name
")->fetchAll(PDO::FETCH_ASSOC);

// ---- Edit mode ----
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM directories WHERE id=? LIMIT 1");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Super Admin | Directories</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:1000px; margin:0 auto; padding:20px; }
    h1 { font-size:22px; margin-bottom:15px; }
    .card { background:#fff; padding:18px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.05); margin-bottom:20px; }
    label { font-weight:bold; font-size:13px; display:block; margin-top:10px; }
    select, input { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-top:4px; }
    .btn { background:#2563eb; color:white; padding:8px 12px; border:none; border-radius:6px; text-decoration:none; font-size:14px; cursor:pointer; }
    .btn:hover { background:#1d4ed8; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }
    table { width:100%; border-collapse:collapse; background:white; margin-top:15px; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
    th { background:#f9fafb; }
    .alert { padding:10px; border-radius:6px; margin:10px 0; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }
    .actions {
      display:flex;
      gap:6px;
    }
    .actions a {
      flex:1;
      text-align:center;
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Manage Directories</h1>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Directory saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Directory deleted successfully.</div>
  <?php endif; ?>

  <!-- Quick Links -->
  <div class="card">
    <h3>Quick Links</h3>
    <a href="dashboard.php" class="btn">⬅ Back to Dashboard</a>
    <a href="directories.php" class="btn">🔄 Refresh Directories</a>
  </div>

  <!-- Directory List -->
  <div class="card">
    <h3>Directories</h3>
    <?php if (count($directories) > 0): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Organization</th>
        <th>Directory Name</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($directories as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['id']) ?></td>
          <td><?= htmlspecialchars($d['organization_name']) ?></td>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td class="actions">
            <a href="directories.php?edit=<?= $d['id'] ?>" class="btn">Edit</a>
            <a href="directories.php?delete=<?= $d['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p>No directories found. Please add one below.</p>
    <?php endif; ?>
  </div>

  <!-- Add/Edit Form -->
  <div class="card">
    <h3><?= $edit ? 'Edit Directory' : 'Add Directory' ?></h3>
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">

      <label for="organization_id">Organization</label>
      <select name="organization_id" required>
        <option value="">-- Select Organization --</option>
        <?php foreach ($organizations as $org): ?>
          <option value="<?= $org['id'] ?>" <?= ($edit['organization_id']??'')==$org['id']?'selected':'' ?>>
            <?= htmlspecialchars($org['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="name">Directory Name</label>
      <input id="name" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>

      <button type="submit" class="btn"><?= $edit ? 'Update' : 'Add' ?> Directory</button>
    </form>
  </div>
</div>

<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC
</footer>
</body>
</html>
