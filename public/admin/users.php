<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole(['admin']);

// --- Search & Filter ---
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';

// --- Pagination ---
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build WHERE conditions
$where = "WHERE (u.name LIKE ? OR u.email LIKE ?)";
$params = ["%$search%", "%$search%"];

if ($statusFilter === 'active') {
    $where .= " AND u.is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where .= " AND u.is_active = 0";
}

// Count total
$countStmt = Database::$pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch users
$stmt = Database::$pdo->prepare(
    "SELECT u.*, GROUP_CONCAT(r.slug) as role_slugs, GROUP_CONCAT(r.name) as role_names
     FROM users u
     LEFT JOIN user_roles ur ON u.id = ur.user_id
     LEFT JOIN roles r ON ur.role_id = r.id
     $where
     GROUP BY u.id
     ORDER BY u.id DESC
     LIMIT ? OFFSET ?"
);
foreach ($params as $i => $val) {
    $stmt->bindValue($i+1, $val, PDO::PARAM_STR);
}
$stmt->bindValue(count($params)+1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

// Fetch all roles
$allRoles = Database::$pdo->query("SELECT id, name, slug FROM roles ORDER BY name")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="../assets/css/cgs.css">
  <style>
    .badge { background:#eee; border-radius:6px; padding:2px 6px; margin:0 2px; }
    .inactive { color:#a00; font-weight:bold; }
    .filters { margin-bottom: 1em; }
  </style>
  <script>
    function toggleAll(source) {
      const checkboxes = document.querySelectorAll('.user-checkbox');
      checkboxes.forEach(cb => cb.checked = source.checked);
    }
  </script>
</head>
<body class="container">
<h2>Manage Users</h2>

<form method="get" class="filters">
  <input type="text" name="search" placeholder="Search by name or email" value="<?=e($search)?>">
  <select name="status">
    <option value="all" <?= $statusFilter==='all'?'selected':'' ?>>All</option>
    <option value="active" <?= $statusFilter==='active'?'selected':'' ?>>Active</option>
    <option value="inactive" <?= $statusFilter==='inactive'?'selected':'' ?>>Inactive</option>
  </select>
  <button type="submit">Apply</button>
</form>

<form method="post" action="../../api/admin_bulk_action.php">
<table class="table">
  <thead>
    <tr>
      <th><input type="checkbox" onclick="toggleAll(this)"></th>
      <th>ID</th><th>Name</th><th>Email</th><th>Reg No</th><th>Status</th><th>Roles</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($users as $u):
      $userRoles = $u['role_slugs'] ? explode(',', $u['role_slugs']) : [];
      $userRoleNames = $u['role_names'] ? explode(',', $u['role_names']) : [];
    ?>
      <tr>
        <td><input type="checkbox" name="user_ids[]" value="<?=$u['id']?>" class="user-checkbox"></td>
        <td><?=e($u['id'])?></td>
        <td><?=e($u['name'])?></td>
        <td><?=e($u['email'])?></td>
        <td><?=e($u['reg_no'])?></td>
        <td>
          <?php if($u['is_active']): ?>
            <span class="badge">Active</span>
          <?php else: ?>
            <span class="badge inactive">Inactive</span>
          <?php endif; ?>
        </td>
        <td>
          <?php foreach($userRoleNames as $r): ?>
            <span class="badge"><?=e($r)?></span>
          <?php endforeach; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$users): ?>
      <tr><td colspan="7">No users found</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div style="margin-top:1em;">
  <select name="bulk_action" id="bulk_action" required onchange="toggleRoleSelector()">
    <option value="">-- Bulk Action --</option>
    <option value="activate">Activate</option>
    <option value="deactivate">Deactivate</option>
    <option value="assign_roles">Assign Roles</option>
    <option value="clear_roles">Clear Roles</option>
  </select>

  <select name="role_ids[]" id="role_selector" multiple size="3" style="display:none;">
    <?php foreach($allRoles as $role): ?>
      <option value="<?=$role['id']?>"><?=e($role['name'])?></option>
    <?php endforeach; ?>
  </select>

  <button type="submit">Apply</button>
</div>
</form>

<script>
  function toggleRoleSelector() {
    const action = document.getElementById('bulk_action').value;
    const selector = document.getElementById('role_selector');
    if (action === 'assign_roles' || action === 'clear_roles') {
      selector.style.display = 'inline-block';
    } else {
      selector.style.display = 'none';
    }
  }
</script>

<!-- Pagination -->
<div class="pagination">
  <?php if($page > 1): ?>
    <a href="?search=<?=urlencode($search)?>&status=<?=urlencode($statusFilter)?>&page=<?=$page-1?>">Previous</a>
  <?php endif; ?>

  Page <?=$page?> of <?=$totalPages?>

  <?php if($page < $totalPages): ?>
    <a href="?search=<?=urlencode($search)?>&status=<?=urlencode($statusFilter)?>&page=<?=$page+1?>">Next</a>
  <?php endif; ?>
</div>

</body>
</html>
