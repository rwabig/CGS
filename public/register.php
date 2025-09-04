<?php require_once __DIR__.'/../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name=trim($_POST['name']??'');
  $email=trim($_POST['email']??'');
  $pass=$_POST['password']??'';
  $reg = trim($_POST['reg_no']??'');
  $pepper = $_ENV['PASSWORD_PEPPER'] ?? '';
  $hash = password_hash(hash('sha256',$pass.$pepper), PASSWORD_DEFAULT);
  $stmt=Database::$pdo->prepare('INSERT INTO users(name,email,password_hash,reg_no) VALUES(?,?,?,?)');
  try{ $stmt->execute([$name,$email,$hash,$reg]);
       $uid=(int)Database::$pdo->lastInsertId();
       // grant student role
       $rid=(int)Database::$pdo->query("SELECT id FROM roles WHERE slug='student' LIMIT 1")->fetchColumn();
       Database::$pdo->prepare('INSERT INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$uid,$rid]);
       header('Location: login.php?registered=1'); exit;
  }catch(Exception $e){ $err=$e->getMessage(); }
}
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Create Account — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
</head><body class="container">
<h2>Create Account</h2>
<?php if(!empty($err)) echo '<div class="alert">'.htmlspecialchars($err).'</div>'; ?>
<form method="post" class="card">
  <label>Full name <input name="name" required></label>
  <label>Email <input type="email" name="email" required></label>
  <label>Registration No. <input name="reg_no"></label>
  <label>Password <input type="password" name="password" minlength="8" required></label>
  <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Log in</a>.</p>
</body></html>
