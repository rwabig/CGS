<?php require_once __DIR__.'/../../src/bootstrap.php'; Auth::requireRole(['admin']);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $name=trim($_POST['name']);
  $slug=strtolower(preg_replace('/\s+/','_', $name));
  Database::$pdo->prepare('INSERT INTO roles(name,slug) VALUES(?,?)')->execute([$name,$slug]);
}
$roles=Database::$pdo->query('SELECT * FROM roles ORDER BY id')->fetchAll();
?>
<html lang="en"><head><title></title><meta charset="utf-8"><link rel="stylesheet" href="../assets/css/cgs.css"></head>
<body class="container">
<h2>Roles</h2>
<form method="post" class="card">
  <label>Role Name <input name="name" required></label>
  <button type="submit">Add</button>
</form>
<table class="table"><tr><th>ID</th><th>Name</th><th>Slug</th></tr>
<?php foreach($roles as $r): ?><tr><td><?=$r['id']?></td><td><?=e($r['name'])?></td><td><?=$r['slug']?></td></tr><?php endforeach; ?>
</table>
</body></html>
