<?php
// Simple 2-step installer: collects DB + admin, writes .env, runs SQL
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $db = $_POST['db'] ?? [];
  $admin = $_POST['admin'] ?? [];
  $env = "APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=".($_POST['app_url'] ?? '')."\n\n".
         "DB_HOST={$db['host']}\nDB_PORT={$db['port']}\nDB_DATABASE={$db['name']}\nDB_USERNAME={$db['user']}\nDB_PASSWORD={$db['pass']}\n\n".
         'SESSION_SECRET='.bin2hex(random_bytes(16))."\n".
         'CSRF_SECRET='.bin2hex(random_bytes(16))."\n".
         'PASSWORD_PEPPER='.bin2hex(random_bytes(16))."\n";
  file_put_contents(dirname(__DIR__).'/.env', $env);

  // connect and create DB/schema/seed
  $pdo = new PDO("mysql:host={$db['host']};port={$db['port']}", $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
  $pdo->exec("USE `{$db['name']}`;");
  $pdo->exec(file_get_contents(dirname(__DIR__).'/database/schema.sql'));
  $pdo->exec(file_get_contents(dirname(__DIR__).'/database/seed.sql'));

  // create default admin
  $pepper = getenv('PASSWORD_PEPPER') ?: substr($env, strpos($env,'PASSWORD_PEPPER=')+16, 32);
  $hash = password_hash(hash('sha256', $admin['password'].$pepper), PASSWORD_DEFAULT);
  $stmt = $pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)');
  $stmt->execute([$admin['name'], $admin['email'], $hash]);
  $adminId = (int)$pdo->lastInsertId();
  // grant admin role
  $roleId = (int)$pdo->query("SELECT id FROM roles WHERE slug='admin' LIMIT 1")->fetchColumn();
  $pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$adminId,$roleId]);

  header('Location: ../public/login.php');
  exit;
}
?>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>CGS Installer</title>
<link rel="stylesheet" href="../public/assets/css/cgs.css"/>
</head>
<body class="container">
  <h1>CGS Installer</h1>
  <form method="post">
    <fieldset>
      <legend>Application</legend>
      <label>App URL <input name="app_url" value="http://localhost/CGS/public" required></label>
    </fieldset>
    <fieldset>
      <legend>Database</legend>
      <label>Host <input name="db[host]" value="127.0.0.1" required></label>
      <label>Port <input name="db[port]" value="3306" required></label>
      <label>Name <input name="db[name]" value="cgs" required></label>
      <label>User <input name="db[user]" value="root" required></label>
      <label>Password <input type="password" name="db[pass]"></label>
    </fieldset>
    <fieldset>
      <legend>Administrator</legend>
      <label>Name <input name="admin[name]" required></label>
      <label>Email <input type="email" name="admin[email]" required></label>
      <label>Password <input type="password" name="admin[password]" minlength="8" required></label>
    </fieldset>
    <button type="submit">Install</button>
  </form>
</body>
</html>
