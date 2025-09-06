<?php require_once __DIR__.'/../../src/bootstrap.php'; Auth::requireRole(['admin']);
$steps=Database::$pdo->query('SELECT * FROM clearance_steps ORDER BY id DESC LIMIT 100')->fetchAll();
?>
<html lang="en"><head><title></title><meta charset="utf-8"><link rel="stylesheet" href="../assets/css/cgs.css"></head>
<body class="container">
<h2>Clearance Steps (latest 100)</h2>
<table class="table"><tr><th>ID</th><th>Clearance</th><th>Department</th><th>Status</th></tr>
<?php foreach($steps as $s): ?><tr><td><?=$s['id']?></td><td><?=$s['clearance_id']?></td><td><?=$s['department_id']?></td><td><?=$s['status']?></td></tr><?php endforeach; ?>
</table>
</body></html>
