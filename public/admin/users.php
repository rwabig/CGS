<?php
require_once __DIR__.'/../../src/bootstrap.php';
Auth::requireRole(['admin']);

// handle role assign/remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['assign_role'])) {
    $uid = (int)$_POST['uid'];
    $role_id = (int)$_POST['role_id'];
    Database::$pdo->prepare('INSERT IGNORE INTO user_roles(user_id,role_id) VALUES(?,?)')
      ->execute([$uid, $role_id]);
    header('Location: users.php'); exit;
  }
  if (!empty($_POST['remove_role'])) {
    $uid = (int)$_POST['uid'];
    $role_id = (int)$_POST['role_id'];
    Database::$pdo->prepare('DELETE FROM user_roles WHERE user_id=? AND role_id=?')
      ->execute([$uid, $role_id]);
    header('Location: users.php'); exit;
  }
  if (!empty($_POST['delete_user'])) {
    $uid = (int)$_POST['uid'];
    Database::$pdo->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);
    header('Location: users.php'); exit;
  }
}

$users = Database::$pdo->query('SELECT id,name,email,status,created_at FROM users ORDER BY created_at DESC')->fetchAll();
$roles = Database::$pdo->query('SELECT id,name,slug FROM roles ORDER BY name')->fetchAll();

// helper to fetch roles for a user (we'll do it inside loop)
?>
<html lang="en">
<head><title></title>
<meta charset="utf-8">
<link rel="stylesheet" href="../assets/css/cgs.css">
</head>
<body class="container">
<h2>Users</h2>
<table class="table">
  <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Roles</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($users as $u):
      // current roles for this user
      $stmt = Database::$pdo->prepare('SELECT r.id,r.name,r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
      $stmt->execute([$u['id']]);
      $uRoles = $stmt->fetchAll();
  ?>
    <tr>
      <td><?=e($u['id'])?></td>
      <td><?=e($u['name'])?></td>
      <td><?=e($u['email'])?></td>
      <td><?=e($u['status'])?></td>
      <td>
        <?php if(!$uRoles) echo '—'; else {
          foreach($uRoles as $r) echo '<span style="display:inline-block;margin-right:6px">'.e($r['name']).'</span>';
        } ?>
      </td>
      <td>
        <!-- Assign role -->
        <form method="post" style="display:inline-block;margin-right:6px;">
          <input type="hidden" name="uid" value="<?=e($u['id'])?>">
          <select name="role_id" required>
            <option value="">Assign role…</option>
            <?php foreach($roles as $r): ?>
              <option value="<?=$r['id']?>"><?=e($r['name'])?></option>
            <?php endforeach; ?>
          </select>
          <button name="assign_role" value="1">Assign</button>
        </form>

        <!-- Remove role (choose which to remove) -->
        <?php if($uRoles): ?>
        <form method="post" style="display:inline-block;margin-right:6px;">
          <input type="hidden" name="uid" value="<?=e($u['id'])?>">
          <select name="role_id" required>
            <option value="">Remove role…</option>
            <?php foreach($uRoles as $r): ?>
              <option value="<?=$r['id']?>"><?=e($r['name'])?></option>
            <?php endforeach; ?>
          </select>
          <button name="remove_role" value="1">Remove</button>
        </form>
        <?php endif; ?>

        <!-- Delete user -->
        <form method="post" style="display:inline-block" onsubmit="return confirm('Delete this user?');">
          <input type="hidden" name="uid" value="<?=e($u['id'])?>">
          <button name="delete_user" value="1">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
