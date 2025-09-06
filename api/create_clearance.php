<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole(['student']);
header('Content-Type: application/json');
$in=json_decode(file_get_contents('php://input'),true);
$level=$in['level']; $program=$in['program']; $year=(int)$in['completion_year'];
$stmt=Database::$pdo->prepare('INSERT INTO clearances(user_id,level,program,completion_year,status) VALUES(?,?,?,?,"in_progress")');
$stmt->execute([$_SESSION['uid'],$level,$program,$year]);
$cid=(int)Database::$pdo->lastInsertId();
echo json_encode(['clearance_id'=>$cid]);
?>
