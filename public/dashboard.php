<?php
require_once __DIR__.'/../src/bootstrap.php';
Auth::requireRole();

$uid=$_SESSION['uid'];

// Fetch user roles
$stmt=Database::$pdo->prepare(
  'SELECT r.slug FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=?'
);
$stmt->execute([$uid]);
$roles=array_column($stmt->fetchAll(),'slug');
?>
<html lang="en"><head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Dashboard — CGS</title>
<link rel="stylesheet" href="assets/css/cgs.css"/>
<style>
.badge {background:#c33;color:#fff;border-radius:12px;padding:2px 6px;font-size:0.8em;}
</style>
</head><body class="container">
<h2>Dashboard</h2>
<ul class="menu">
  <?php if(in_array('student',$roles)): ?>
    <li>
      <a href="clearance_request.php">Request Clearance</a>
    </li>
    <li>
      <a href="clearance_status.php">My Clearance Status <span id="badge-student" class="badge" style="display:none"></span></a>
    </li>
  <?php endif; ?>

  <?php if(in_array('signatory',$roles)): ?>
    <li>
      <a href="signatory/dashboard.php">My Pending Steps <span id="badge-signatory" class="badge" style="display:none"></span></a>
    </li>
  <?php endif; ?>

  <?php if(in_array('admin',$roles)): ?>
    <li><a href="admin/users.php">Manage Users</a></li>
    <li><a href="admin/departments.php">Manage Departments</a></li>
    <li><a href="admin/roles.php">Manage Roles</a></li>
    <li><a href="admin/steps.php">Manage Steps</a></li>
    <li><a href="admin/workflows.php">Manage Workflows</a></li>
    <li>
      <a href="signatory/dashboard.php">All Pending Steps <span id="badge-admin" class="badge" style="display:none"></span></a>
    </li>
  <?php endif; ?>

  <li><a href="logout.php">Logout</a></li>
</ul>

<script>
async function refreshBadges(){
  try{
    const res=await fetch('../api/pending_counts.php');
    const data=await res.json();

    if(data.student!==undefined){
      const badge=document.getElementById('badge-student');
      if(data.student>0){ badge.textContent=data.student; badge.style.display='inline-block'; }
      else{ badge.style.display='none'; }
    }

    if(data.signatory!==undefined){
      const badge=document.getElementById('badge-signatory');
      if(data.signatory>0){ badge.textContent=data.signatory; badge.style.display='inline-block'; }
      else{ badge.style.display='none'; }
    }

    if(data.admin!==undefined){
      const badge=document.getElementById('badge-admin');
      if(data.admin>0){ badge.textContent=data.admin; badge.style.display='inline-block'; }
      else{ badge.style.display='none'; }
    }

  }catch(e){ console.error('Failed to refresh badges',e); }
}
setInterval(refreshBadges,5000);
refreshBadges();
</script>
</body></html>

