<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole(['signatory','admin']); // admin allowed to sign all optionally
header('Content-Type: application/json');

$clearance_step_id = (int)($_POST['clearance_step_id'] ?? 0);
$comment = trim($_POST['comments'] ?? '');
$user = Auth::user();

if (!$clearance_step_id) {
  http_response_code(400); echo json_encode(['error'=>'missing step']); exit;
}

/* load the clearance_step and its workflow signatories */
$step = Database::$pdo->prepare("SELECT * FROM clearance_steps WHERE id = ?");
$step->execute([$clearance_step_id]);
$stepRow = $step->fetch(PDO::FETCH_ASSOC);
if (!$stepRow) { http_response_code(404); echo json_encode(['error'=>'step not found']); exit; }

/* find workflow signatories ordered */
$stmt = Database::$pdo->prepare("
  SELECT ws.id, ws.user_id, ws.sign_order, u.name, u.email
  FROM workflow_signatories ws
  JOIN users u ON u.id = ws.user_id
  WHERE ws.workflow_step_id = ?
  ORDER BY ws.sign_order ASC
");
$stmt->execute([$stepRow['workflow_step_id']]);
$signatories = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$signatories) { http_response_code(400); echo json_encode(['error'=>'no signatories']); exit; }

/* determine which sign_order is next pending for this clearance step */
$stmt2 = Database::$pdo->prepare("SELECT MAX(sign_order) FROM clearance_steps WHERE clearance_id = ? AND workflow_step_id = ? AND status='signed'");
$stmt2->execute([$stepRow['clearance_id'], $stepRow['workflow_step_id']]);
$maxSignedOrder = (int)$stmt2->fetchColumn(); // 0 if none signed

$nextRequiredOrder = $maxSignedOrder + 1;

/* ensure current user is assigned at the nextRequiredOrder */
$allowed = false;
foreach($signatories as $s) {
  if ((int)$s['sign_order'] === (int)$nextRequiredOrder && (int)$s['user_id'] === (int)$user['id']) {
    $allowed = true; break;
  }
}

if (!$allowed) {
  http_response_code(403); echo json_encode(['error'=>'You are not the next signatory.']); exit;
}

/* perform signing: mark a clearance_steps row as signed (insert/update) */
/* update the clearance_steps record for this clearance and workflow_step */
$update = Database::$pdo->prepare("
  UPDATE clearance_steps SET status='signed', signatory_user_id=?, sign_order=?, comments=?, signed_at=NOW()
  WHERE id = ?
");
$update->execute([$user['id'], $nextRequiredOrder, $comment, $clearance_step_id]);

/* optionally progress clearance overall: check if all sign_orders are finished for this workflow_step */
$stmt3 = Database::$pdo->prepare("SELECT COUNT(*) FROM workflow_signatories WHERE workflow_step_id = ?");
$stmt3->execute([$stepRow['workflow_step_id']]);
$totalSigners = (int)$stmt3->fetchColumn();

$stmt4 = Database::$pdo->prepare("
  SELECT COUNT(*) FROM clearance_steps
  WHERE clearance_id = ? AND workflow_step_id = ? AND status='signed'
");
$stmt4->execute([$stepRow['clearance_id'], $stepRow['workflow_step_id']]);
$signedCount = (int)$stmt4->fetchColumn();

if ($signedCount >= $totalSigners) {
  /* mark step as completed (already signed), progress to next step if any */
  // If you keep a separate step-level status, update it here.
}

/* log action, return success */
echo json_encode(['success'=>true, 'signed_by'=>$user['id'], 'sign_order'=>$nextRequiredOrder]);
?>
