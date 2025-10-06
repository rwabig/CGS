<?php
// public/super_admin/programs.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// Fetch categories (with hierarchy)
$categories = $db->query("
    SELECT c.id, c.name AS category_name, d.name AS department_name, o.name AS organization_name
    FROM categories c
    JOIN departments d ON c.department_id = d.id
    JOIN organizations o ON d.organization_id = o.id
    ORDER BY o.name, d.name, c.name
")->fetchAll(PDO::FETCH_ASSOC);

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['id'] ?? null;
    $categoryId = $_POST['category_id'] ?? null;
    $name       = trim($_POST['name'] ?? '');

    if ($categoryId && $name !== '') {
        if ($id) {
            $stmt = $db->prepare("UPDATE programs SET category_id=?, name=?, updated_at=NOW() WHERE id=?");
            $stmt->execute([$categoryId, $name, $id]);
            $logAction = "update";
            $logDetails = "Updated program #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO programs (category_id, name, created_at, updated_at)
                                  VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$categoryId, $name]);
            $logAction = "create";
            $logDetails = "Created program ($name)";
        }
        $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                      VALUES (?, ?, ?, NOW())")
           ->execute([Auth::user()['id'], "program_$logAction", $logDetails]);

        header("Location: programs.php?success=1");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM programs WHERE id=?");
    $stmt->execute([$id]);

    $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                  VALUES (?, 'program_delete', ?, NOW())")
       ->execute([Auth::user()['id'], "Deleted program #$id"]);

    header("Location: programs.php?deleted=1");
    exit;
}

// Fetch programs with context
$programs = $db->query("
    SELECT p.id, p.name, c.name AS category_name, d.name AS department_name, o.name AS organization_name
    FROM programs p
    JOIN categories c ON p.category_id = c.id
    JOIN departments d ON c.department_id = d.id
    JOIN organizations o ON d.organization_id = o.id
    ORDER BY o.name, d.name, c.name, p.name
")->fetchAll(PDO::FETCH_ASSOC);

// Editing program
$editProgram = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM programs WHERE id=? LIMIT 1");
    $stmt->execute([$_GET['edit']]);
    $editProgram = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Manage Programs</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family: Arial, sans-serif; margin:0; }
    header { background:#1e3a8a; color:white; padding:12px 20px; font-size:1.2rem; font-weight:bold; }
    .container { padding:20px; max-width:1100px; margin:auto; }

    .card { background:white; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.08); margin-bottom:20px; padding:16px; }
    .card h3 { margin-top:0; margin-bottom:12px; }

    .quick-links a { display:inline-block; margin-right:8px; background:#2563eb; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:13px; }
    .quick-links a:hover { background:#1d4ed8; }

    table { width:100%; border-collapse:collapse; background:white; border-radius:8px; overflow:hidden; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; vertical-align:middle; }
    th { background:#f9fafb; font-weight:600; }
    tr:last-child td { border-bottom:none; }

    .actions { display:flex; gap:8px; }
    .actions a { flex:1; text-align:center; }

    .btn { display:inline-block; background:#2563eb; color:white; padding:5px 10px; border-radius:6px; text-decoration:none; font-size:13px; }
    .btn:hover { background:#1d4ed8; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }

    form.add-form { background:white; padding:15px; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
    label { font-weight:bold; font-size:13px; display:block; margin-bottom:4px; }
    select, input { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-bottom:10px; }

    .alert { padding:8px 12px; border-radius:6px; margin:10px 0; font-size:14px; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }

    footer { background:black; color:white; text-align:center; padding:8px; font-size:13px; margin-top:30px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <h1>Manage Programs</h1>
  <!-- Quick Links Card -->
  <div class="card quick-links">
    <h3>Quick Links</h3>
    <a href="dashboard.php"> ⬅ Dashboard</a>
    <a href="programs.php"> 🔄 Refresh</a>
  </div>

  <!-- Alerts -->
  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Program saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Program deleted successfully.</div>
  <?php endif; ?>

  <!-- Programs Table -->
  <div class="card">
    <h3>Programs</h3>
    <?php if (count($programs) > 0): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Organization</th>
        <th>Department</th>
        <th>Category</th>
        <th>Program Name</th>
        <th style="width:160px; text-align:center;">Actions</th>
      </tr>
      <?php foreach ($programs as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['id']) ?></td>
          <td><?= htmlspecialchars($p['organization_name']) ?></td>
          <td><?= htmlspecialchars($p['department_name']) ?></td>
          <td><?= htmlspecialchars($p['category_name']) ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td>
            <div class="actions">
              <a href="programs.php?edit=<?= $p['id'] ?>" class="btn">Edit</a>
              <a href="programs.php?delete=<?= $p['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <?php else: ?>
      <p>No programs found. Please add one below.</p>
    <?php endif; ?>
  </div>

  <!-- Add/Edit Form -->
  <div class="card">
    <h3><?= isset($_GET['edit']) ? 'Edit Program' : 'Add Program' ?></h3>
    <form class="add-form" method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($editProgram['id'] ?? '') ?>">

      <label for="category_id">Category</label>
      <select name="category_id" id="category_id" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= (isset($editProgram['category_id']) && $editProgram['category_id'] == $cat['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat['organization_name']) ?> → <?= htmlspecialchars($cat['department_name']) ?> → <?= htmlspecialchars($cat['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label for="name">Program Name</label>
      <input id="name" name="name" value="<?= htmlspecialchars($editProgram['name'] ?? '') ?>" required>

      <button type="submit" class="btn"><?= isset($editProgram) ? 'Update' : 'Add' ?> Program</button>
    </form>
  </div>

</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>

