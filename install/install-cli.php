<?php
// Run: php install/install-cli.php
function ask($q,$def=null){ echo $q.($def?" [$def]":"").": "; $ans=trim(fgets(STDIN)); return $ans!==''?$ans:$def; }
$dbh = ask('DB host','127.0.0.1');
$dbp = ask('DB port','3306');
$dbn = ask('DB name','cgs');
dbu = ask('DB user','root');
dbpw= ask('DB password');
$adminName = ask('Admin name');
$adminEmail= ask('Admin email');
$adminPass = ask('Admin password');
$appUrl = ask('App URL','http://localhost/CGS/public');
$env = "APP_ENV=local\nAPP_DEBUG=true\nAPP_URL=$appUrl\n\nDB_HOST=$dbh\nDB_PORT=$dbp\nDB_DATABASE=$dbn\nDB_USERNAME=$dbu\nDB_PASSWORD=$dbpw\n\nSESSION_SECRET=".bin2hex(random_bytes(16))."\nCSRF_SECRET=".bin2hex(random_bytes(16))."\nPASSWORD_PEPPER=".bin2hex(random_bytes(16))."\n";
file_put_contents(__DIR__.'/../.env',$env);
$pdo=new PDO("mysql:host=$dbh;port=$dbp",$dbu,$dbpw,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbn` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$pdo->exec("USE `$dbn`;");
$pdo->exec(file_get_contents(__DIR__.'/../database/schema.sql'));
$pdo->exec(file_get_contents(__DIR__.'/../database/seed.sql'));
$pepper = preg_match('/PASSWORD_PEPPER=(.*)/',$env,$m)?$m[1]:'';
$hash=password_hash(hash('sha256',$adminPass.$pepper),PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute([$adminName,$adminEmail,$hash]);
$adminId=(int)$pdo->lastInsertId();
$roleId=(int)$pdo->query("SELECT id FROM roles WHERE slug='admin' LIMIT 1")->fetchColumn();
$pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$adminId,$roleId]);
echo "\n✅ CGS installed. Visit $appUrl to log in.\n";
?>
