<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole();
$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : null;
if ($cid) {
  $stmt=Database::$pdo->prepare('SELECT * FROM clearances WHERE id=? AND user_id=?');
  $stmt->execute([$cid,$_SESSION['uid']]);
  $cl=$stmt->fetch();
} else {
  $stmt=Database::$pdo->prepare('SELECT * FROM clearances WHERE user_id=? ORDER BY id DESC LIMIT 1');
  $stmt->execute([$_SESSION['uid']]);
  $cl=$stmt->fetch();
}
if(!$cl){ echo 'No clearance found.'; exit; }
$steps=Database::$pdo->prepare('SELECT cs.*, d.name AS dept FROM clearance_steps cs JOIN departments d ON d.id=cs.department_id WHERE clearance_id=? ORDER BY step_order');
$steps->execute([$cl['id']]);
$rows=$steps->fetchAll();
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Status — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
<script defer src="assets/js/form-wizard.js"></script>
</head><body class="container">
<h2>Clearance Status (<?=htmlspecialchars($cl['level'])?>)</h2>
<ol class="progress">
<?php foreach($rows as $r): ?>
  <li class="<?= $r['status']==='cleared'?'done':($r['status']==='flagged'?'flagged':'pending') ?>">
    <span><?=htmlspecialchars($r['dept'])?></span>
    <?php if($r['comment']) echo '<small>'.htmlspecialchars($r['comment']).'</small>'; ?>
  </li>
<?php endforeach; ?>
</ol>
</body></html>
