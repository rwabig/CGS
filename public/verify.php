<?php
// public/verify.php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/includes/helpers.php';

$db = Database::getConnection();

$cid = (int)($_GET['cid'] ?? 0);
$certificate = null;
$signatories = [];
$error = '';

if ($cid > 0) {
    try {
        $stmt = $db->prepare("
            SELECT cc.*, sp.full_name, sp.registration_number, sp.photo_path
            FROM clearance_certificates cc
            JOIN student_profiles sp ON cc.student_id = sp.user_id
            WHERE cc.id = :cid AND cc.issued_at IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([':cid' => $cid]);
        $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($certificate) {
            $sigStmt = $db->prepare("
                SELECT signatory_name, signatory_title, status, signed_at
                FROM clearance_signatories
                WHERE certificate_id = :cid
                ORDER BY step_order ASC
            ");
            $sigStmt->execute([':cid' => $cid]);
            $signatories = $sigStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Certificate not found or not yet issued.";
        }
    } catch (Throwable $e) {
        $error = "Error fetching certificate: " . htmlspecialchars($e->getMessage());
    }
} else {
    $error = "Invalid or missing certificate ID.";
}

$pageTitle = "Certificate Verification";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Certificate Verification - CGS</title>
  <link rel="stylesheet" href="assets/css/cgs.css">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:900px; margin:auto; padding:20px; }
    .card { background:white; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.08); padding:30px; margin-bottom:20px; }
    h1 { text-align:center; font-size:22px; margin-bottom:20px; }
    .student-info { display:flex; align-items:center; gap:20px; margin-bottom:20px; }
    .student-info img { width:100px; height:100px; border-radius:8px; border:2px solid #2563eb; }
    table { width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; }
    th, td { border:1px solid #e5e7eb; padding:8px; text-align:left; }
    th { background:#f9fafb; }
    .status-valid { color:green; font-weight:bold; }
    .status-invalid { color:red; font-weight:bold; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
  </style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Certificate Verification</h1>

    <?php if ($error): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php elseif ($certificate): ?>
      <p class="status-valid">✔ This certificate is valid and issued by <?= htmlspecialchars($certificate['university_name']) ?>.</p>

      <div class="student-info">
        <img src="student/<?= htmlspecialchars($certificate['photo_path'] ?? 'assets/images/default-student.png') ?>" alt="Student Photo">
        <div>
          <strong>Name:</strong> <?= htmlspecialchars($certificate['full_name']) ?><br>
          <strong>Reg No:</strong> <?= htmlspecialchars($certificate['registration_number']) ?><br>
          <strong>Issued At:</strong> <?= htmlspecialchars($certificate['issued_at']) ?><br>
          <strong>Completion Year:</strong> <?= htmlspecialchars($certificate['completion_year'] ?? '-') ?><br>
          <strong>Graduation Date:</strong> <?= htmlspecialchars($certificate['graduation_date'] ?? '-') ?>
        </div>
      </div>

      <h3>Signed By</h3>
      <table>
        <thead>
          <tr>
            <th>Signatory</th>
            <th>Title</th>
            <th>Status</th>
            <th>Signed At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($signatories as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['signatory_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($s['signatory_title'] ?? '-') ?></td>
              <td><?= htmlspecialchars(ucfirst($s['status'] ?? '-')) ?></td>
              <td><?= htmlspecialchars($s['signed_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
