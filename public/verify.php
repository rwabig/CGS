<?php require_once __DIR__.'/../src/bootstrap.php';
$code=$_GET['code']??'';
$stmt=Database::$pdo->prepare('SELECT c.*, u.name, u.reg_no FROM certificates cert JOIN clearances c ON cert.clearance_id=c.id JOIN users u ON u.id=c.user_id WHERE cert.verification_code=?');
$stmt->execute([$code]);
$rec=$stmt->fetch();
?>
<html lang="en"><head><title></title><meta charset="utf-8"><link rel="stylesheet" href="assets/css/cgs.css"></head>
<body class="container">
<h2>Certificate Verification</h2>
<?php if($rec): ?>
<p>✅ Verified certificate for <?=e($rec['name'])?> (Reg No: <?=e($rec['reg_no'])?>, Level: <?=e($rec['level'])?>, Year: <?=e($rec['completion_year'])?>).</p>
<?php else: ?>
<p>❌ Invalid or expired code.</p>
<?php endif; ?>
</body></html>
