<?php
require_once __DIR__.'/../src/bootstrap.php';
Auth::requireRole(['signatory','admin','student']); // any authenticated user can call

header('Content-Type: application/json');

$uid = $_SESSION['uid'];

// Fetch roles for this user
$stmt = Database::$pdo->prepare(
  'SELECT r.slug FROM user_roles ur
   JOIN roles r ON r.id=ur.role_id
   WHERE ur.user_id=?'
);
$stmt->execute([$uid]);
$roles = array_column($stmt->fetchAll(), 'slug');

$response = [];

// Signatory count: steps assigned to this user
if (in_array('signatory',$roles,true)) {
  $stmt = Database::$pdo->prepare(
    'SELECT COUNT(*) FROM clearance_steps
     WHERE assignee_user_id=? AND status="pending"'
  );
  $stmt->execute([$uid]);
  $response['signatory'] = (int)$stmt->fetchColumn();
}

// Admin count: all pending steps
if (in_array('admin',$roles,true)) {
  $stmt = Database::$pdo->query(
    'SELECT COUNT(*) FROM clearance_steps WHERE status="pending"'
  );
  $response['admin'] = (int)$stmt->fetchColumn();
}

// Student count: optional — could show own clearance pending steps
if (in_array('student',$roles,true)) {
  $stmt = Database::$pdo->prepare(
    'SELECT COUNT(*) FROM clearance_steps cs
     JOIN clearances c ON c.id=cs.clearance_id
     WHERE c.user_id=? AND cs.status="pending"'
  );
  $stmt->execute([$uid]);
  $response['student'] = (int)$stmt->fetchColumn();
}

echo json_encode($response);
?>
