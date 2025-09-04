<?php require_once __DIR__.'/../../src/bootstrap.php'; Auth::requireRole(['admin']);
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['make_admin'])){
  $uid=(int)$_POST['uid'];
  $rid=(int)Database::$pdo->query("SELECT id FROM roles WHERE slug='admin' LIMIT 1")->fetchColumn();
  Database::$pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')->execute([$uid,$rid]);
}
$users=Database::$pdo->query('SELECT id,name,email,status FROM users ORDER BY created_at DESC')->fetchAll();
?>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="../assets/css/cgs.css"><title></title></head>
<body class="container">
<h2>Users</h2>
<table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($users as $u): ?>
<tr>
  <td><?=$u['id']?></td><td><?=htmlspecialchars($u['name'])?></td><td><?=htmlspecialchars($u['email'])?></td><td><?=$u['status']?></td>
  <td>
    <form method="post" style="display:inline">
      <input type="hidden" name="uid" value="<?=$u['id']?>">
      <button name="make_admin" value="1">Grant Admin</button>
    </form>
  </td>
</tr>
<?php endforeach; ?>
</tbody></table>
</body></html>
