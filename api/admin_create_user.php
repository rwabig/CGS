<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole(['admin']);
header('Content-Type: application/json');
$in=json_decode(file_get_contents('php://input'),true);
$pepper=$_ENV['PASSWORD_PEPPER']??'';
$hash=password_hash(hash('sha256',$in['password'].$pepper),PASSWORD_DEFAULT);
$stmt=Database::$pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');
$stmt->execute([$in['name'],$in['email'],$hash]);
$id=(int)Database::$pdo->lastInsertId();
echo json_encode(['id'=>$id]);
?>
