<?php require_once __DIR__.'/../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD']==='POST'){
  if (Auth::login($_POST['email']??'', $_POST['password']??'')){
    header('Location: dashboard.php'); exit;
  }
  $err='Invalid credentials';
}
?>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
</head><body class="container">
<h2>Login</h2>
<?php if(!empty($err)) echo '<div class="alert">'.$err.'</div>'; ?>
<form method="post" class="card">
  <label>Email <input type="email" name="email" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button type="submit">Login</button>
</form>
</body></html>
