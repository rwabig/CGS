<?php
// public/student/certificate.php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!Auth::hasRole('student')) {
    redirect('../login.php');
}

$db     = Database::getConnection();
$user   = Auth::user();
$userId = (int)$user['id'];

$error = '';
$certificate = null;
$signatories = [];

try {
    // Fetch certificate if issued
    $stmt = $db->prepare("
        SELECT cc.*, sp.full_name, sp.registration_number, sp.photo_path
        FROM clearance_certificates cc
        JOIN student_profiles sp ON cc.student_id = sp.user_id
        WHERE cc.student_id = :sid AND cc.issued_at IS NOT NULL
        LIMIT 1
    ");
    $stmt->execute([':sid' => $userId]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($certificate) {
        $sigStmt = $db->prepare("
            SELECT signatory_name, signatory_title, status, signed_at, comments
            FROM clearance_signatories
            WHERE certificate_id = :cid
            ORDER BY step_order ASC
        ");
        $sigStmt->execute([':cid' => $certificate['id']]);
        $signatories = $sigStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $error = "Could not load certificate: " . htmlspecialchars($e->getMessage());
}

// Build QR code payload (if issued)
$qrPayload = $certificate
    ? "Certificate ID: {$certificate['id']}\nStudent: {$certificate['full_name']}\nReg No: {$certificate['registration_number']}\nIssued: {$certificate['issued_at']}"
    : null;

// Use Google Charts API for QR code (simpler than a PHP library)
$qrUrl = $qrPayload
    ? "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" . urlencode($qrPayload)
    : null;

$pageTitle = "My Clearance Certificate";
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student | Clearance Certificate</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background:#f3f4f6; font-family:Arial,sans-serif; margin:0; }
    .container { max-width:900px; margin:auto; padding:20px; }
    .card { background:white; border-radius:12px; box-shadow:0 6px 20px rgba(0,0,0,0.08); padding:30px; margin-bottom:20px; }
    h1 { text-align:center; font-size:22px; margin-bottom:20px; }
    .header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
    .header img { max-height:80px; }
    .student-info { display:flex; align-items:center; gap:20px; margin-bottom:20px; }
    .student-info img { width:100px; height:100px; border-radius:8px; border:2px solid #2563eb; }
    table { width:100%; border-collapse:collapse; margin-top:20px; font-size:14px; }
    th, td { border:1px solid #e5e7eb; padding:8px; text-align:left; vertical-align:top; }
    th { background:#f9fafb; }
    .qr { text-align:center; margin-top:20px; }
    .btn { background:#2563eb; color:white; padding:10px 16px; border-radius:6px; text-decoration:none; border:none; font-size:14px; cursor:pointer; }
    .btn:hover { background:#1d4ed8; }
    .alert-error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:6px; margin-bottom:10px; }
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>

<div class="container">
  <div class="card">
    <h1>Clearance Certificate</h1>

    <?php if ($error): ?>
      <div class="alert-error"><?= $error ?></div>
    <?php elseif (!$certificate): ?>
      <p style="text-align:center;">Your clearance certificate has not been issued yet.</p>
    <?php else: ?>
      <div class="header">
        <img src="../assets/images/<?= htmlspecialchars($certificate['university_logo'] ?? 'aru-logo.png') ?>" alt="Logo">
        <div style="text-align:right;">
          <strong><?= htmlspecialchars($certificate['university_name']) ?></strong><br>
          <?= htmlspecialchars($certificate['title']) ?>
        </div>
      </div>

      <p><?= htmlspecialchars($certificate['description']) ?></p>

      <div class="student-info">
        <img src="<?= htmlspecialchars($certificate['photo_path'] ?? 'default-student.png') ?>" alt="Student Photo">
        <div>
          <strong>Name:</strong> <?= htmlspecialchars($certificate['full_name']) ?><br>
          <strong>Reg No:</strong> <?= htmlspecialchars($certificate['registration_number']) ?><br>
          <strong>Completion Year:</strong> <?= htmlspecialchars($certificate['completion_year'] ?? '-') ?><br>
          <strong>Graduation Date:</strong> <?= htmlspecialchars($certificate['graduation_date'] ?? '-') ?><br>
          <strong>Issued At:</strong> <?= htmlspecialchars($certificate['issued_at'] ?? '-') ?>
        </div>
      </div>

      <h3>Signatories</h3>
      <table>
        <thead>
          <tr>
            <th>Signatory</th>
            <th>Title</th>
            <th>Status</th>
            <th>Comments</th>
            <th>Signed At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($signatories as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['signatory_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($s['signatory_title'] ?? '-') ?></td>
              <td><?= htmlspecialchars(ucfirst($s['status'] ?? '-')) ?></td>
              <td><?= htmlspecialchars($s['comments'] ?? '') ?></td>
              <td><?= htmlspecialchars($s['signed_at'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($qrUrl): ?>
      <div class="qr">
        <p><strong>Verification QR Code</strong></p>
        <img src="<?= $qrUrl ?>" alt="QR Code">
      </div>
      <?php endif; ?>

      <div style="text-align:center; margin-top:20px;">
        <button class="btn" onclick="window.print()">Print Certificate</button>
      </div>
    <?php endif; ?>
  </div>
</div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Student Panel</footer>
</body>
</html>
