<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole();
header('Content-Type: application/json');
$cid=(int)($_GET['cid']??0);
$stmt=Database::$pdo->prepare('SELECT * FROM clearance_steps WHERE clearance_id=? ORDER BY step_order');
$stmt->execute([$cid]);
echo json_encode($stmt->fetchAll());
?>
