<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole();
require_once __DIR__.'/../src/Certificate.php';

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

// check if all cleared
$allCleared = count($rows)>0 && !array_filter($rows, fn($r)=>$r['status']!=='cleared');

// check if certificate already exists
$stmt2=Database::$pdo->prepare('SELECT * FROM certificates WHERE clearance_id=? LIMIT 1');
$stmt2->execute([$cl['id']]);
$cert=$stmt2->fetch();
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Status — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
<script defer src="assets/js/form-wizard.js"></script>
</head><body class="container">
<h2>Clearance Status (<?=e($cl['level'])?>)</h2>
<ol class="progress">
<?php foreach($rows as $r): ?>
  <li class="<?= $r['status']==='cleared'?'done':($r['status']==='flagged'?'flagged':'pending') ?>">
    <span><?=e($r['dept'])?></span>
    <?php if($r['comment']) echo '<small>'.e($r['comment']).'</small>'; ?>
  </li>
<?php endforeach; ?>
</ol>

<?php if($allCleared): ?>
  <div class="card">
    <?php if($cert): ?>
      <p>✅ Certificate issued. <a href="certificates/<?=basename($cert['file_path'])?>" target="_blank">Download Certificate</a></p>
    <?php else: ?>
      <form method="post">
        <button name="generate_cert" value="1">Download Certificate</button>
      </form>
      <?php
        if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['generate_cert'])){
          try{
            $file=Certificate::generate($cl['id'],$_SESSION['uid']);
            header('Location: certificates/'.basename($file));
            exit;
          }catch(Exception $e){ echo '<p>Error: '.e($e->getMessage()).'</p>'; }
        }
      ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

</body></html>
