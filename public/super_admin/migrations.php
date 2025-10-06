<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');
require_once __DIR__ . '/../../src/MigrationManager.php';

$migrationManager = new MigrationManager();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['run_migrations'])) {
        $message = $migrationManager->runMigrations();
    } elseif (isset($_POST['rollback'])) {
        $message = $migrationManager->rollbackLastBatch();
    }
}

// Fetch migration history
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT batch, migration, action, created_at, details
                        FROM migrations_log ORDER BY batch DESC, id DESC");
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $history = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Super Admin Migrations</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; padding: 20px; overflow-y: auto; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 800px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    .message { background: #e6f7ff; padding: 10px; border: 1px solid #91d5ff; border-radius: 5px; margin-bottom: 20px; }
    button { display: block; width: 100%; margin: 10px 0; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
    button.danger { background: #dc2626; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    table thead { background: #f9fafb; }
    table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    table th { font-weight: bold; }
    table tr:nth-child(even) { background: #f3f4f6; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
    .details { white-space: pre-wrap; font-size: 12px; color: #444; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Database Migrations</h1>
    <p>Run pending migrations or rollback the last batch if needed.</p>

    <?php if (!empty($message)): ?>
      <div class="message"><?= nl2br(htmlspecialchars($message)) ?></div>
    <?php endif; ?>

    <form method="POST">
      <button type="submit" name="run_migrations">Run Pending Migrations</button>
      <button type="submit" name="rollback" class="danger" onclick="return confirm('Rollback last batch? This may result in data loss.')">Rollback Last Batch</button>
    </form>

    <?php if (!empty($history)): ?>
      <h2 style="margin-top: 25px;">Migration History</h2>
      <table>
        <thead>
          <tr>
            <th>Batch</th>
            <th>Migration</th>
            <th>Action</th>
            <th>Timestamp</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['batch']) ?></td>
              <td><?= htmlspecialchars($row['migration']) ?></td>
              <td><?= htmlspecialchars(strtoupper($row['action'])) ?></td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td class="details"><?= htmlspecialchars($row['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p style="text-align:center; margin-top: 20px;">No migration history available.</p>
    <?php endif; ?>

    <p style="text-align:center; margin-top: 20px;">
      <a href="dashboard.php">&larr; Back to Dashboard</a>
    </p>
  </div>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
