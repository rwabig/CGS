<?php
// public/super_admin/sections.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// ---- Fetch directories for dropdown ----
$directories = $db->query("SELECT id, name FROM directories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// ---- Handle Create / Update ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id'] ?? null;
    $dir  = $_POST['directory_id'] ?? null;
    $name = trim($_POST['name'] ?? '');

    if ($dir && $name !== '') {
        if ($id) {
            $stmt = $db->prepare("UPDATE sections SET directory_id=?, name=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$dir, $name, $id]);
            $logAction = "update";
            $logDetails = "Updated section #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO sections (directory_id, name, created_at, updated_at)
                                  VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$dir, $name]);
            $logAction = "create";
            $logDetails = "Created section ($name)";
        }

        $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                      VALUES (?, ?, ?, NOW())")
           ->execute([Auth::user()['id'], "section_$logAction", $logDetails]);

        header("Location: sections.php?success=1");
        exit;
    }
}

// ---- Handle Delete ----
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM sections WHERE id=?");
    $stmt->execute([$id]);

    $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                  VALUES (?, 'section_delete', ?, NOW())")
       ->execute([Auth::user()['id'], "Deleted section #$id"]);

    header("Location: sections.php?deleted=1");
    exit;
}

// ---- Fetch sections with directory context ----
$sections = $db->query("
    SELECT s.id, s.name, d.name AS directory_name
    FROM sections s
    JOIN directories d ON s.directory_id = d.id
    ORDER BY d.name, s.name
")->fetchAll(PDO::FETCH_ASSOC);

// ---- Edit Mode ----
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM sections WHERE id=? LIMIT 1");
    $stmt->execute([$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Super Admin | Sections</title>
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
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Manage Sections</h1>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Section saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Section deleted successfully.</div>
  <?php endif; ?>

  <!-- Quick Links -->
  <div class="card">
    <h3>Quick Links</h3>
    <a href="dashboard.php" class="btn">⬅ Back to Dashboard</a>
    <a href="sections.php" class="btn">🔄 Refresh Sections</a>
  </div>

  <!-- Sections List -->
  <div class="card">
    <h3>Sections</h3>
    <?php if (count($sections) > 0): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Directory</th>
        <th>Section Name</th>
        <th>Actions</th>
      </tr>
      <?php foreach ($sections as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['id']) ?></td>
          <td><?= htmlspecialchars($s['directory_name']) ?></td>
          <td><?= htmlspecialchars($s['name']) ?></td>
          <td>
            <a href="sections.php?edit=<?= $s['id'] ?>" class="btn">Edit</a>
            <a href="sections.php?delete=<?= $s['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p>No sections found. Please add one below.</p>
    <?php endif; ?>
  </div>

  <!-- Add/Edit Form -->
  <div class="card">
    <h3><?= $edit ? 'Edit Section' : 'Add Section' ?></h3>
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($edit['id'] ?? '') ?>">

      <label for="directory_id">Directory</label>
      <select name="directory_id" required>
        <option value="">-- Select Directory --</option>
        <?php foreach ($directories as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($edit['directory_id']??'')==$d['id']?'selected':'' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="name">Section Name</label>
      <input id="name" name="name" value="<?= htmlspecialchars($edit['name'] ?? '') ?>" required>

      <button type="submit" class="btn"><?= $edit ? 'Update' : 'Add' ?> Section</button>
    </form>
  </div>
</div>

<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC
</footer>
</body>
</html>
