<?php
require_once __DIR__.'/../../src/bootstrap.php';
Auth::requireRole(['signatory','admin']);

$uid = $_SESSION['uid'];

// check admin
$roleStmt = Database::$pdo->prepare(
  'SELECT r.slug FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=?'
);
$roleStmt->execute([$uid]);
$roles = array_column($roleStmt->fetchAll(), 'slug');
$isAdmin = in_array('admin', $roles, true);

if ($isAdmin) {
  $stmt = Database::$pdo->query(
    'SELECT cs.id, cs.status, cs.comment, u.name AS student_name, u.reg_no,
            d.name AS dept, c.level, c.program, c.completion_year, cs.id AS step_id
     FROM clearance_steps cs
     JOIN clearances c ON c.id = cs.clearance_id
     JOIN users u ON u.id = c.user_id
     JOIN departments d ON d.id = cs.department_id
     WHERE cs.status = "pending"
     ORDER BY cs.id DESC'
  );
} else {
  $stmt = Database::$pdo->prepare(
    'SELECT cs.id, cs.status, cs.comment, u.name AS student_name, u.reg_no,
            d.name AS dept, c.level, c.program, c.completion_year, cs.id AS step_id
     FROM clearance_steps cs
     JOIN clearances c ON c.id = cs.clearance_id
     JOIN users u ON u.id = c.user_id
     JOIN departments d ON d.id = cs.department_id
     WHERE cs.assignee_user_id = ? AND cs.status = "pending"
     ORDER BY cs.id DESC'
  );
  $stmt->execute([$uid]);
}
$steps = $stmt->fetchAll();
?>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Pending Steps — CGS</title>
<link rel="stylesheet" href="../assets/css/cgs.css">
</head>
<body class="container">
<h2>My Pending Steps</h2>

<?php if(!$steps): ?>
  <p>No pending steps assigned to you.</p>
<?php else: ?>
  <table class="table">
    <thead>
      <tr><th>Student</th><th>Reg No</th><th>Level</th><th>Programme</th><th>Year</th><th>Department</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach($steps as $s): ?>
      <tr>
        <td><?=e($s['student_name'])?></td>
        <td><?=e($s['reg_no'])?></td>
        <td><?=e($s['level'])?></td>
        <td><?=e($s['program'])?></td>
        <td><?=e($s['completion_year'])?></td>
        <td><?=e($s['dept'])?></td>
        <td>
          <form method="post" action="../../api/sign_step.php">
            <input type="hidden" name="step_id" value="<?=e($s['id'])?>">
            <input type="text" name="comment" placeholder="Optional comment">
            <button type="submit">Sign</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

</body>
</html>
