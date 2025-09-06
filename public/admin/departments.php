<?php
require_once __DIR__.'/../../src/bootstrap.php';
Auth::requireRole(['admin']);

// create department
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])) {
  $name = trim($_POST['name']);
  if ($name !== '') {
    $slug = strtolower(preg_replace('/\s+/', '_', $name));
    Database::$pdo->prepare('INSERT INTO departments(name,slug) VALUES(?,?)')
      ->execute([$name, $slug]);
    header('Location: departments.php'); exit;
  }
}

// assign/unassign signatory
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assign'])) {
  $deptId = (int)$_POST['dept_id'];
  $userId = (int)($_POST['user_id'] ?? 0) ?: null;
  $stmt = Database::$pdo->prepare('UPDATE departments SET signatory_user_id=? WHERE id=?');
  $stmt->execute([$userId, $deptId]);
  header('Location: departments.php'); exit;
}

// load departments with current signatory
$depts = Database::$pdo->query(
  'SELECT d.*, u.name AS signatory_name
   FROM departments d
   LEFT JOIN users u ON u.id = d.signatory_user_id
   ORDER BY d.id'
)->fetchAll();

// load users who have signatory role

$signatories = Database::$pdo->query(
  "SELECT u.id,u.name,u.email
   FROM users u
   JOIN user_roles ur ON ur.user_id = u.id
   JOIN roles r ON r.id = ur.role_id
   WHERE r.slug = 'signatory'
   ORDER BY u.name"
)->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="../assets/css/cgs.css">
</head>
<body class="container">
<h2>Departments</h2>

<form method="post" class="card">
  <input type="hidden" name="create" value="1">
  <label>Department Name <input name="name" required></label>
  <button type="submit">Add Department</button>
</form>

<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Signatory</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach($depts as $d): ?>
    <tr>
      <td><?=e($d['id'])?></td>
      <td><?=e($d['name'])?></td>
      <td><?=e($d['slug'])?></td>
      <td><?= $d['signatory_name'] ? e($d['signatory_name']) : '—' ?></td>
      <td>
        <form method="post" style="display:inline-block">
          <input type="hidden" name="assign" value="1">
          <input type="hidden" name="dept_id" value="<?=e($d['id'])?>">
          <select name="user_id">
            <option value="">— None —</option>
            <?php foreach($signatories as $s): ?>
              <option value="<?=e($s['id'])?>" <?= $s['id'] == $d['signatory_user_id'] ? 'selected' : '' ?>>
                <?=e($s['name'])?> (<?=e($s['email'])?>)
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
