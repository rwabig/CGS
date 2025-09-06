<?php
require_once __DIR__.'/../../src/bootstrap.php';
Auth::requireRole(['admin']);

// fetch workflows
$workflows = Database::$pdo->query(
  'SELECT * FROM workflows ORDER BY level, step_order'
)->fetchAll();

// fetch departments for dropdown
$departments = Database::$pdo->query(
  'SELECT id,name FROM departments ORDER BY name'
)->fetchAll();

// add new workflow step
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_step'])) {
  $level = $_POST['level'];
  $dept_id = (int)$_POST['department_id'];
  $order = (int)$_POST['step_order'];
  Database::$pdo->prepare(
    'INSERT INTO workflows(level,department_id,step_order) VALUES(?,?,?)'
  )->execute([$level,$dept_id,$order]);
  header("Location: workflows.php"); exit;
}

// delete step
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_step'])) {
  $id = (int)$_POST['id'];
  Database::$pdo->prepare('DELETE FROM workflows WHERE id=?')->execute([$id]);
  header("Location: workflows.php"); exit;
}
?>
<html lang="en">
    <head><title></title>
<meta charset="utf-8">
<link rel="stylesheet" href="../assets/css/cgs.css">
</head>
<body class="container">
<h2>Workflows</h2>

<form method="post" class="card">
  <input type="hidden" name="add_step" value="1">
  <label>Level
    <select name="level" required>
      <option value="undergraduate">Undergraduate</option>
      <option value="postgraduate">Postgraduate</option>
    </select>
  </label>
  <label>Department
    <select name="department_id" required>
      <?php foreach($departments as $d): ?>
        <option value="<?=$d['id']?>"><?=e($d['name'])?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <label>Step Order <input type="number" name="step_order" min="1" required></label>
  <button type="submit">Add Step</button>
</form>

<table class="table">
  <thead><tr><th>ID</th><th>Level</th><th>Department</th><th>Order</th><th>Action</th></tr></thead>
  <tbody>
    <?php foreach($workflows as $w):
      $deptName = '';
      foreach($departments as $d){ if($d['id']==$w['department_id']) $deptName=$d['name']; }
    ?>
    <tr>
      <td><?=$w['id']?></td>
      <td><?=$w['level']?></td>
      <td><?=e($deptName)?></td>
      <td><?=$w['step_order']?></td>
      <td>
        <form method="post" style="display:inline" onsubmit="return confirm('Delete this step?');">
          <input type="hidden" name="id" value="<?=$w['id']?>">
          <button name="delete_step" value="1">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
