<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$roles = Auth::roles();

$pdo = Database::getConnection();
$isStudent = in_array('student', $roles);
$isStaff   = in_array('signatory', $roles) || in_array('admin', $roles) || in_array('super_admin', $roles);

$profile = null;
if ($isStudent) {
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} elseif ($isStaff) {
    $stmt = $pdo->prepare("SELECT * FROM staff_profiles WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Check if student has completed clearance
$clearanceComplete = false;
if ($isStudent && $profile) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_steps WHERE user_id = ? AND status != 'approved'");
    $stmt->execute([$user['id']]);
    $remaining = $stmt->fetchColumn();
    $clearanceComplete = ($remaining == 0);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | My Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; justify-content: center; align-items: flex-start; padding: 20px; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 700px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    .photo-preview { display: block; width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; border: 2px solid #e5e7eb; }
    .profile-field { margin: 6px 0; font-size: 14px; }
    .profile-field strong { display: inline-block; width: 160px; color: #374151; }
    .btn { display: block; width: 100%; margin-top: 15px; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; text-align: center; }
    .btn:hover { background: #1d4ed8; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }

    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 250px;
        max-width: 300px;
        padding: 12px 16px;
        border-radius: 6px;
        color: white;
        font-size: 14px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        opacity: 0;
        transform: translateY(-10px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        z-index: 9999;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { background: #16a34a; }
    .toast.error { background: #dc2626; }
  </style>
</head>
<body>
<header>CGS | My Profile</header>
<div class="container">
  <div class="card">
    <h1>My Profile</h1>

    <!-- Passport Photo -->
    <img src="<?= !empty($profile['passport_photo']) ? 'uploads/'.htmlspecialchars($profile['passport_photo']) : 'assets/img/default-avatar.png' ?>"
         class="photo-preview">

    <?php if (!$profile): ?>
        <p style="text-align:center; color:red; font-size:14px;">You have not completed your profile.</p>
        <a href="profile_edit.php" class="btn">Complete My Profile</a>
    <?php else: ?>
        <?php if ($isStudent): ?>
            <div class="profile-field"><strong>Registration No:</strong> <?= htmlspecialchars($profile['registration_number'] ?? '-') ?></div>
            <div class="profile-field"><strong>Organization:</strong> <?= htmlspecialchars($profile['organization_id']) ?></div>
            <div class="profile-field"><strong>Department:</strong> <?= htmlspecialchars($profile['department_id']) ?></div>
            <div class="profile-field"><strong>Program:</strong> <?= htmlspecialchars($profile['program_id']) ?></div>
            <div class="profile-field"><strong>Completion Year:</strong> <?= htmlspecialchars($profile['completion_year']) ?></div>
            <div class="profile-field"><strong>Current Address:</strong> <?= htmlspecialchars($profile['address']) ?></div>
        <?php elseif ($isStaff): ?>
            <div class="profile-field"><strong>PF/Check Number:</strong> <?= htmlspecialchars($profile['pf_number']) ?></div>
            <div class="profile-field"><strong>Organization:</strong> <?= htmlspecialchars($profile['organization_id']) ?></div>
            <div class="profile-field"><strong>Department:</strong> <?= htmlspecialchars($profile['department_id']) ?></div>
            <div class="profile-field"><strong>Title:</strong> <?= htmlspecialchars($profile['title_id']) ?></div>
            <div class="profile-field"><strong>Current Address:</strong> <?= htmlspecialchars($profile['address']) ?></div>
        <?php endif; ?>

        <a href="profile_edit.php" class="btn">Edit My Profile</a>

        <?php if ($isStudent && $clearanceComplete): ?>
            <button type="button" class="btn" style="background:#16a34a;" onclick="downloadCertificate()">Download Clearance Certificate</button>
        <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div id="toast" class="toast"></div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU</footer>

<script>
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function downloadCertificate() {
    fetch('generate_certificate.php', { method: 'POST' })
        .then(response => {
            if (!response.ok) throw new Error("Failed to generate certificate");
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = "ClearanceCertificate.pdf";
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            showToast("✅ Certificate downloaded successfully!", "success");
        })
        .catch(err => {
            console.error(err);
            showToast("❌ Failed to generate certificate.", "error");
        });
}
</script>
</body>
</html>
