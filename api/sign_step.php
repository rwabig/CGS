<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole(['signatory','admin']);
$id=(int)($_POST['step_id']??0); $comment=trim($_POST['comment']??'');
$stmt=Database::$pdo->prepare('UPDATE clearance_steps SET status="cleared", comment=?, signed_by=?, signed_at=NOW() WHERE id=?');
$stmt->execute([$comment,$_SESSION['uid'],$id]);
header('Location: ../public/clearance_status.php?cid='.(int)($_POST['cid']??0));
?>
