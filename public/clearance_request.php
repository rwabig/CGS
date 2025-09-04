<?php require_once __DIR__.'/../src/bootstrap.php'; Auth::requireRole(['student']);
if($_SERVER['REQUEST_METHOD']==='POST'){
  $level=$_POST['level'];
  $program=trim($_POST['program']);
  $year=(int)$_POST['completion_year'];
  $reg=trim($_POST['reg_no']);
  $stmt=Database::$pdo->prepare('INSERT INTO clearances(user_id,level,program,completion_year,reg_no,status) VALUES(?,?,?,?,?,"in_progress")');
  $stmt->execute([$_SESSION['uid'],$level,$program,$year,$reg]);
  $cid=(int)Database::$pdo->lastInsertId();
  // Create steps based on level (UG vs PG) using department slugs as per ARU forms
  $ug = ['hod','library','ls_store','games','halls','accounts','dss','academic_admin'];
  $pg = ['dean_students','director_library','supervisor','hod','dean_school','accounts','dpgs','cict','exams'];
  $flow = $level==='postgraduate' ? $pg : $ug;
  $order=1;
  foreach($flow as $slug){
    $deptId=(int)Database::$pdo->prepare('SELECT id FROM departments WHERE slug=? LIMIT 1')->execute([$slug])?:0;
    $stmt2=Database::$pdo->prepare('INSERT INTO clearance_steps(clearance_id,department_id,step_order) SELECT ?, id, ? FROM departments WHERE slug=?');
    $stmt2->execute([$cid,$order++,$slug]);
  }
  header('Location: clearance_status.php?cid='.$cid);
  exit;
}
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>New Clearance — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
</head><body class="container">
<h2>Request Clearance</h2>
<form method="post" class="card">
  <label>Level
    <select name="level" required>
      <option value="undergraduate">Undergraduate</option>
      <option value="postgraduate">Postgraduate</option>
    </select>
  </label>
  <label>Programme <input name="program" required></label>
  <label>Completion year <input name="completion_year" type="number" min="2000" max="2100" required></label>
  <label>Registration No. <input name="reg_no"></label>
  <button type="submit">Create</button>
</form>
</body></html>
