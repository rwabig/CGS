<?php require_once __DIR__.'/../src/bootstrap.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
if(!$input){ http_response_code(400); echo json_encode(['error'=>'Bad payload']); exit; }
$name=trim($input['name']??''); $email=trim($input['email']??''); $pass=$input['password']??'';
$pepper=$_ENV['PASSWORD_PEPPER']??''; $hash=password_hash(hash('sha256',$pass.$pepper),PASSWORD_DEFAULT);
$stmt=Database::$pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');
$stmt->execute([$name,$email,$hash]);
http_response_code(201); echo json_encode(['status'=>'ok']);
?>

