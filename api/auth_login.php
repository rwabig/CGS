<?php require_once __DIR__.'/../src/bootstrap.php';
header('Content-Type: application/json');
$in=json_decode(file_get_contents('php://input'),true);
if(!$in){ http_response_code(400); echo json_encode(['error'=>'Bad payload']); exit; }
if(Auth::login($in['email'],$in['password'])){
  echo json_encode(['status'=>'ok']);
}else{
  http_response_code(401); echo json_encode(['error'=>'Invalid']);
}
?>
