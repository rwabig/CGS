<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole(['admin']);

// --- Search ---
$search = trim($_GET['search'] ?? '');

// --- Pagination ---
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Count total
$countStmt = Database::$pdo->prepare(
    "SELECT COUNT(*)
     FROM workflows w
     JOIN departments d ON d.id = w.department_id
     WHERE d.name LIKE ? OR w.level LIKE ?"
);
$countStmt->execute(["%$search%", "%$search%"]);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch workflows
$stmt = Database::$pdo->prepare(
    "SELECT w.*, d.name as dept_name
     FROM workflows w
     JOIN departments d ON d.id = w.department_id
     WHERE d.name LIKE ? OR w.level LIKE ?
     ORDER BY w.level, w.step_order
     LIMIT ? OFFSET ?"
);
$stmt->bindValue(1, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(2, "%$search%", PDO::PARAM_STR);
$stmt->bindValue(3, $perPage, PDO::PARAM_INT);
$stmt->bindValue(4, $offset, PDO::PARAM_INT);
$stmt->execute();
$workflows = $stmt->fetchAll();

// Fetch departments
$departments = Database::$pdo->query("SELECT id,name FROM departments ORDER BY name")->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="../assets/css/cgs.css">
</head>
<body class="container">
<h2>Manage Workflows</h2>

<form method="get" style="margin-bottom:1em;">
  <input type="text" name="search" placeholder="Search by level or department" value="<?=e($search)?>">
  <button type="submit">Search</button>
</form>

<form method="post" action="../../api/admin_add_workflow.php" class="card" style="margin-bottom:1em;">
  <label>Level:
    <select name="level">
      <option value="undergraduate">Undergraduate</option>
      <option value="postgraduate">Postgraduate</option>
    </select>
  </label>
  <label>Department:
    <select name="department_id">
      <?php foreach($departments as $d): ?>
        <option value="<?=$d['id']?>"><?=e($d['name'])?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Step Order: <input type="number" name="step_order" min="1" required></label>
  <button type="submit">Add Step</button>
</form>

<table class="table">
  <thead>
    <tr>
      <th>ID</th><th>Level</th><th>Department</th><th>Order</th><th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($workflows as $w): ?>
      <tr>
        <td><?=e($w['id'])?></td>
        <td><?=e($w['level'])?></td>
        <td><?=e($w['dept_name'])?></td>
        <td><?=e($w['step_order'])?></td>
        <td>
          <form method="post" action="../../api/admin_delete_workflow.php" style="display:inline;" onsubmit="return confirm('Delete this step?');">
            <input type="hidden" name="id" value="<?=$w['id']?>">
            <button type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(!$workflows): ?>
      <tr><td colspan="5">No workflows found</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<!-- Pagination -->
<div class="pagination">
  <?php if($page > 1): ?>
    <a href="?search=<?=urlencode($search)?>&page=<?=$page-1?>">Previous</a>
  <?php endif; ?>

  Page <?=$page?> of <?=$totalPages?>

  <?php if($page < $totalPages): ?>
    <a href="?search=<?=urlencode($search)?>&page=<?=$page+1?>">Next</a>
  <?php endif; ?>
</div>

</body>
</html>
