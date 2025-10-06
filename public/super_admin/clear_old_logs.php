<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$db = Database::getConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cutoff = $_POST['cutoff_date'] ?? '';
    $archive = isset($_POST['archive']) ? true : false;

    if (empty($cutoff)) {
        $message = "❌ Please select a cutoff date.";
    } else {
        try {
            // Fetch logs before deleting
            $stmt = $db->prepare("SELECT * FROM audit_log WHERE created_at < ?");
            $stmt->execute([$cutoff]);
            $oldLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = count($oldLogs);

            // Archive if requested
            $archiveFile = null;
            if ($archive && $count > 0) {
                $archiveFile = __DIR__ . "/../../storage/audit_log_archive_" . date('Ymd_His') . ".json";
                if (!is_dir(dirname($archiveFile))) {
                    mkdir(dirname($archiveFile), 0777, true);
                }
                file_put_contents($archiveFile, json_encode($oldLogs, JSON_PRETTY_PRINT));
            }

            // Delete logs
            $deleteStmt = $db->prepare("DELETE FROM audit_log WHERE created_at < ?");
            $deleteStmt->execute([$cutoff]);

            // Prepare message
            $message = $count > 0
                ? "✅ Successfully cleared {$count} audit log entries older than {$cutoff}" . ($archive ? " (archived to storage)." : ".")
                : "ℹ No logs older than {$cutoff} found.";

            // Log this maintenance action
            $logStmt = $db->prepare("
                INSERT INTO audit_log (user_id, action, details, created_at)
                VALUES (?, 'maintenance', ?, NOW())
            ");
            $details = "Cleared {$count} audit log entries older than {$cutoff}"
                     . ($archive ? " (archived to " . basename($archiveFile) . ")" : "")
                     . ". Performed by: " . Auth::user()['email'];
            $logStmt->execute([Auth::id(), $details]);

        } catch (PDOException $e) {
            $message = "❌ Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Super Admin - Clear Old Logs</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 20px; overflow-y: auto; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 700px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    .message { margin: 10px 0; padding: 10px; border-radius: 6px; font-size: 14px; }
    .message.success { background: #dcfce7; color: #166534; }
    .message.error { background: #fee2e2; color: #991b1b; }
    form { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; }
    input[type="date"] { padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
    label { font-weight: 600; font-size: 0.9rem; }
    button { background: #2563eb; color: white; border: none; border-radius: 6px; padding: 10px; cursor: pointer; font-size: 1rem; }
    button:hover { background: #1d4ed8; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Audit Log Maintenance</h1>
    <p>Select a cutoff date to delete logs older than that date. You can optionally archive them before deletion.</p>

    <?php if (!empty($message)): ?>
      <div class="message <?= strpos($message, '✅') === 0 ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <form method="post" onsubmit="return confirm('Are you sure? This will delete logs permanently.');">
      <label for="cutoff_date">Cutoff Date</label>
      <input type="date" name="cutoff_date" id="cutoff_date" required>

      <label>
        <input type="checkbox" name="archive" value="1">
        Archive logs to JSON before deleting
      </label>

      <button type="submit">Clear Old Logs</button>
    </form>

    <p style="text-align:center; margin-top: 20px;">
      <a href="audit_log.php">&larr; Back to Audit Log</a>
    </p>
  </div>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
