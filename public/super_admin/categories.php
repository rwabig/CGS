<?php
// public/super_admin/categories.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();

// Fetch departments for dropdown
$departments = $db->query("SELECT d.id, d.name, o.name AS organization_name
                           FROM departments d
                           JOIN organizations o ON d.organization_id = o.id
                           ORDER BY o.name, d.name")->fetchAll(PDO::FETCH_ASSOC);

// Handle Create / Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = $_POST['id'] ?? null;
    $deptId = $_POST['department_id'] ?? null;
    $name   = $_POST['name'] ?? null;

    if ($deptId && $name) {
        if ($id) {
            $stmt = $db->prepare("UPDATE categories
                                  SET department_id=?, name=?, updated_at=NOW()
                                  WHERE id=?");
            $stmt->execute([$deptId, $name, $id]);
            $logAction = "update";
            $logDetails = "Updated category #$id ($name)";
        } else {
            $stmt = $db->prepare("INSERT INTO categories (department_id, name, created_at, updated_at)
                                  VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$deptId, $name]);
            $logAction = "create";
            $logDetails = "Created category ($name)";
        }

        // Audit log
        $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                                 VALUES (?, ?, ?, NOW())");
        $logStmt->execute([Auth::user()['id'], "category_$logAction", $logDetails]);

        header("Location: categories.php?success=1");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM categories WHERE id=?");
    $stmt->execute([$id]);

    $logStmt = $db->prepare("INSERT INTO audit_log (user_id, action, details, created_at)
                             VALUES (?, 'category_delete', ?, NOW())");
    $logStmt->execute([Auth::user()['id'], "Deleted category #$id"]);

    header("Location: categories.php?deleted=1");
    exit;
}

// Fetch Categories
$categories = $db->query("SELECT c.*, d.name AS department_name, o.name AS organization_name
                          FROM categories c
                          JOIN departments d ON c.department_id = d.id
                          JOIN organizations o ON d.organization_id = o.id
                          ORDER BY o.name, d.name, c.name")->fetchAll(PDO::FETCH_ASSOC);

// If editing
$editCat = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id=? LIMIT 1");
    $stmt->execute([$_GET['edit']]);
    $editCat = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Manage Categories</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family: Arial, sans-serif; margin:0; }
    header { background:#1e3a8a; color:white; padding:12px 20px; font-size:1.2rem; font-weight:bold; }
    .container { padding:20px; max-width:1000px; margin:auto; }
    .quick-link { margin:20px 0; display:flex; justify-content:flex-start; }
    .card { background:white; padding:20px; border-radius:10px; box-shadow:0 4px 10px rgba(0,0,0,0.06); text-align:center; width:220px; }
    .card h3 { font-size:15px; margin-bottom:10px; color:#374151; }
    .card a { display:inline-block; background:#2563eb; color:white; padding:8px 14px; border-radius:6px; text-decoration:none; font-size:13px; }
    .card a:hover { background:#1d4ed8; }
    table { width:100%; border-collapse:collapse; margin-top:20px; background:white; border-radius:8px; overflow:hidden; }
    th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; font-size:14px; }
    th { background:#f9fafb; font-weight:600; }
    .btn { display:inline-block; background:#2563eb; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; font-size:13px; }
    .btn:hover { background:#1d4ed8; }
    .btn-danger { background:#dc2626; }
    .btn-danger:hover { background:#b91c1c; }
    form.add-form { background:white; padding:15px; border-radius:8px; margin-top:20px; box-shadow:0 4px 10px rgba(0,0,0,0.05); }
    label { font-weight:bold; font-size:13px; display:block; margin-bottom:4px; }
    select, input { width:100%; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; margin-bottom:10px; }
    footer { background:black; color:white; text-align:center; padding:8px; font-size:13px; margin-top:30px; }
    .alert { padding:8px 12px; border-radius:6px; margin:10px 0; font-size:14px; }
    .alert-success { background:#ecfccb; color:#365314; }
    .alert-danger { background:#fee2e2; color:#991b1b; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <h1>Manage Categories</h1>

  <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">✅ Category saved successfully.</div>
  <?php elseif (isset($_GET['deleted'])): ?>
    <div class="alert alert-danger">🗑 Category deleted successfully.</div>
  <?php endif; ?>

  <!-- Quick Links Card -->
  <div class="card quick-links">
    <h3>Quick Links</h3>
    <a href="dashboard.php"> ⬅ Dashboard</a>
    <a href="categories.php"> 🔄 Refresh</a>
  </div>

  <h2>Categories</h2>
  <?php if (count($categories) > 0): ?>
  <table>
    <tr>
      <th>ID</th>
      <th>Department</th>
      <th>Category</th>
      <th>Actions</th>
    </tr>
    <?php foreach ($categories as $cat): ?>
      <tr>
        <td><?= htmlspecialchars($cat['id']) ?></td>
        <td><?= htmlspecialchars($cat['organization_name']) ?> → <?= htmlspecialchars($cat['department_name']) ?></td>
        <td><?= htmlspecialchars($cat['name']) ?></td>
        <td>
          <a href="categories.php?edit=<?= $cat['id'] ?>" class="btn">Edit</a>
          <a href="categories.php?delete=<?= $cat['id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php else: ?>
    <p>No categories found. Please add one below.</p>
  <?php endif; ?>

  <h3><?= isset($_GET['edit']) ? 'Edit Category' : 'Add Category' ?></h3>
  <form class="add-form" method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($editCat['id'] ?? '') ?>">

    <label for="department_id">Department</label>
    <select name="department_id" id="department_id" required>
      <option value="">-- Select Department --</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= $dept['id'] ?>"
          <?= (isset($editCat['department_id']) && $editCat['department_id'] == $dept['id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($dept['organization_name']) ?> → <?= htmlspecialchars($dept['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="name">Category Name</label>
    <select name="name" id="name" required>
      <?php
      $options = ['Undergraduate','Postgraduate','Diploma','Certificate'];
      foreach ($options as $opt): ?>
        <option value="<?= $opt ?>" <?= (isset($editCat['name']) && $editCat['name'] === $opt) ? 'selected' : '' ?>>
          <?= $opt ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn"><?= isset($editCat) ? 'Update' : 'Add' ?> Category</button>
  </form>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
