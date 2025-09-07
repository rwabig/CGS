<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole(['admin']); // or restrict to super_admin if you prefer

// --- Filters ---
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (a.action LIKE ? OR a.details LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total
$countStmt = Database::$pdo->prepare(
    "SELECT COUNT(*)
     FROM audit_log a
     JOIN users u ON a.admin_id = u.id
     $where"
);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch logs
$stmt = Database::$pdo->prepare(
    "SELECT a.*, u.name as admin_name, u.email as admin_email
     FROM audit_log a
     JOIN users u ON a.admin_id = u.id
     $where
     ORDER BY a.created_at DESC
     LIMIT ? OFFSET ?"
);

foreach ($params as $i => $val) {
    $stmt->bindValue($i+1, $val, PDO::PARAM_STR);
}
$stmt->bindValue(count($params)+1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(count($params)+2, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Audit Log</title>
  <link rel="stylesheet" href="../assets/css/cgs.css">
  <style>
    .table td { vertical-align: top; }
    .filters { margin-bottom: 1em; }
    .details { font-size: 0.9em; color: #444; }
  </style>
</head>
<body class="container">
<h2>Audit Log</h2>

<form method="get" class="filters">
  <input type="text" name="search" placeholder="Search logs, actions, admins" value="<?=e($search)?>">
  <button type="submit">Search</button>
</form>

<table class="table">
  <thead>
    <tr>
      <th>ID</th><th>Admin</th><th>Action</th><th>Details</th><th>Timestamp</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($logs as $log): ?>
      <tr>
        <td><?=e($log['id'])?></td>
        <td>
          <?=e($log['admin_name'])?><br>
          <small><?=e($log['admin_email'])?></small>
        </td>
        <td><strong><?=e($log['action'])?></strong></td>
        <td class="details"><?=nl2br(e($log['details']))?></td>
        <td><?=e($log['created_at'])?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?>
      <tr><td colspan="5">No logs found</td></tr>
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
