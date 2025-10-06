<?php
require_once __DIR__ . '/../../src/bootstrap.php';
Auth::requireRole('super_admin');

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $seedFile = __DIR__ . '/../../database/seed.sql';
    try {
        $db = Database::getConnection();
        $sql = file_get_contents($seedFile);
        $db->exec($sql);
        $message = "✅ Database seeding completed successfully.";
    } catch (PDOException $e) {
        $message = "❌ Error seeding database: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Super Admin Seeding</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20px; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 500px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    .message { background: #e6f7ff; padding: 10px; border: 1px solid #91d5ff; border-radius: 5px; margin-bottom: 20px; }
    button { display: block; width: 100%; margin: 10px 0; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Seed Database</h1>
    <p>Re-run the seed.sql file to restore initial data.</p>

    <?php if (!empty($message)): ?>
      <div class="message"><?= nl2br(htmlspecialchars($message)) ?></div>
    <?php endif; ?>

    <form method="POST">
      <button type="submit" onclick="return confirm('This will reset initial data and may overwrite changes. Proceed?')">Run Seed</button>
    </form>

    <p style="text-align:center; margin-top: 20px;">
      <a href="dashboard.php">&larr; Back to Dashboard</a>
    </p>
  </div>
</div>
<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powerd by: UCC</footer>
</body>
</html>
