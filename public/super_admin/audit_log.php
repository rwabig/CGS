<?php
// public/super_admin/audit_log.php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();
$filter = $_GET['filter'] ?? '';
$params = [];
$where = '';

if (!empty($filter)) {
    $where = "WHERE a.action = :filter";
    $params['filter'] = $filter;
}

$stmt = $db->prepare("
    SELECT a.id, a.user_id, u.email AS user_email, a.action, a.details,
           a.ip_address, a.user_agent, a.created_at
    FROM audit_log a
    LEFT JOIN users u ON u.id = a.user_id
    $where
    ORDER BY a.created_at DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get distinct actions for filter dropdown
$actionsStmt = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action ASC");
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Super Admin Audit Log</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 20px; overflow-y: auto; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 1000px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
    table thead { background: #f9fafb; }
    table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
    table tr:nth-child(even) { background: #f3f4f6; }
    select { padding: 6px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
    .details { white-space: pre-wrap; font-size: 12px; color: #444; }
    .ua { max-width: 250px; word-wrap: break-word; font-size: 11px; color: #555; }
    .filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 10px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>System Audit Log</h1>

    <div class="filter-bar">
      <form method="GET" style="display:flex;align-items:center;gap:8px;">
        <label for="filter">Filter by Action:</label>
        <select name="filter" id="filter" onchange="this.form.submit()">
          <option value="">-- All Actions --</option>
          <?php foreach ($actions as $action): ?>
            <option value="<?= htmlspecialchars($action) ?>" <?= $filter === $action ? 'selected' : '' ?>>
              <?= htmlspecialchars(ucwords(str_replace('_', ' ', $action))) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </form>
      <a href="dashboard.php">&larr; Back to Dashboard</a>
    </div>

    <?php if (!empty($logs)): ?>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Action</th>
            <th>Details</th>
            <th>IP Address</th>
            <th>User Agent</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td><?= htmlspecialchars($log['id']) ?></td>
              <td><?= $log['user_email'] ? htmlspecialchars($log['user_email']) : 'System' ?></td>
              <td><?= htmlspecialchars($log['action']) ?></td>
              <td class="details"><?= htmlspecialchars($log['details']) ?></td>
              <td><?= htmlspecialchars($log['ip_address'] ?? '-') ?></td>
              <td class="ua"><?= htmlspecialchars($log['user_agent'] ?? '-') ?></td>
              <td><?= htmlspecialchars($log['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; margin-top: 20px;">No audit logs found for this filter.</p>
    <?php endif; ?>
  </div>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powered by: UCC</footer>
</body>
</html>
