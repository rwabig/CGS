<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole(['admin']);
header('Content-Type: application/json');
$in=json_decode(file_get_contents('php://input'),true);
$uid=(int)$in['user_id']; $role=$in['role'];
$rid=(int)Database::$pdo->prepare('SELECT id FROM roles WHERE slug=? LIMIT 1')->execute([$role])?:0;
if($rid){
  Database::$pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$uid,$rid]);
  echo json_encode(['status'=>'ok']);
}else{
  http_response_code(404); echo json_encode(['error'=>'role not found']);
}
?>
