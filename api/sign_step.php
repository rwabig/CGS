<?php
require_once __DIR__.'/../src/bootstrap.php';
Auth::requireRole(['signatory','admin']);

header('Content-Type: application/json');

$stepId=(int)($_POST['step_id']??0);
$comment=trim($_POST['comment']??'');

// Fetch step + assigned officer
$stmt=Database::$pdo->prepare(
  'SELECT cs.*, d.name AS dept, d.signatory_user_id
   FROM clearance_steps cs
   JOIN departments d ON d.id=cs.department_id
   WHERE cs.id=?'
);
$stmt->execute([$stepId]);
$step=$stmt->fetch();

if(!$step){
  http_response_code(404);
  echo json_encode(['error'=>'Step not found']);
  exit;
}

// Check permissions: only assigned officer OR admin
$isAdmin=false;
$roleStmt=Database::$pdo->prepare(
  'SELECT r.slug FROM user_roles ur
   JOIN roles r ON r.id=ur.role_id
   WHERE ur.user_id=?'
);
$roleStmt->execute([$_SESSION['uid']]);
$roles=array_column($roleStmt->fetchAll(),'slug');
if(in_array('admin',$roles,true)) $isAdmin=true;

if(!$isAdmin && $step['signatory_user_id']!=$_SESSION['uid']){
  http_response_code(403);
  echo json_encode(['error'=>'Forbidden: not your step']);
  exit;
}

// Update clearance step as cleared
$stmt=Database::$pdo->prepare(
  'UPDATE clearance_steps
   SET status="cleared", comment=?, signed_by=?, signed_at=NOW()
   WHERE id=?'
);
$stmt->execute([$comment,$_SESSION['uid'],$stepId]);

echo json_encode(['status'=>'ok','message'=>'Step signed successfully']);
?>
