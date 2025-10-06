<?php
// public/signatory/profile.php
require_once __DIR__ . '/../../src/bootstrap.php';

$db   = Database::getConnection();
$authUser = Auth::user();
$currentUserId = $authUser['id'] ?? ($_SESSION['user_id'] ?? 0);

$userId = isset($_GET['user']) ? (int)$_GET['user'] : $currentUserId;

// Access control: staff can see their own, admins/super_admins can see any
if ($userId !== $currentUserId && !Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    http_response_code(403);
    exit("Forbidden");
}

$error = '';
$success = '';

// Load staff profile
$stmt = $db->prepare("SELECT * FROM staff_profiles WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($userId === $currentUserId || Auth::hasRole('admin') || Auth::hasRole('super_admin'))) {
    $fullName  = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $position  = $_POST['position_title'] ?? '';
    $orgId     = $_POST['organization_id'] ?: null;
    $deptId    = $_POST['department_id'] ?: null;
    $dirId     = $_POST['directory_id'] ?: null;
    $secId     = $_POST['section_id'] ?: null;
    $chequeNo  = trim($_POST['cheque_number'] ?? '');

    if ($fullName && $position && $orgId && $deptId) {
        if ($profile) {
            $stmt = $db->prepare("UPDATE staff_profiles
                SET full_name=:fn, phone=:ph, position_title=:pt, organization_id=:org, department_id=:dept,
                    directory_id=:dir, section_id=:sec, cheque_number=:chq, updated_at=NOW()
                WHERE user_id=:uid");
            $stmt->execute([
                ':fn'=>$fullName, ':ph'=>$phone, ':pt'=>$position,
                ':org'=>$orgId, ':dept'=>$deptId, ':dir'=>$dirId, ':sec'=>$secId,
                ':chq'=>$chequeNo, ':uid'=>$userId
            ]);
            $success = "Profile updated successfully.";
        } else {
            $stmt = $db->prepare("INSERT INTO staff_profiles
                (user_id, full_name, phone, position_title, organization_id, department_id, directory_id, section_id, cheque_number, created_at, updated_at)
                VALUES (:uid,:fn,:ph,:pt,:org,:dept,:dir,:sec,:chq,NOW(),NOW())");
            $stmt->execute([
                ':uid'=>$userId, ':fn'=>$fullName, ':ph'=>$phone, ':pt'=>$position,
                ':org'=>$orgId, ':dept'=>$deptId, ':dir'=>$dirId, ':sec'=>$secId,
                ':chq'=>$chequeNo
            ]);
            $success = "Profile created successfully.";
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Fetch dropdown data
$organizations = $db->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$directories   = $db->query("SELECT id, name FROM directories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$signatoryTitles = $db->query("SELECT DISTINCT signatory_title FROM workflows ORDER BY signatory_title")->fetchAll(PDO::FETCH_COLUMN);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Signatory Profile - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body {background:#f3f4f6;font-family:Arial,sans-serif;margin:0;}
    .container {max-width:600px;margin:20px auto;padding:20px;}
    .card {background:white;padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.08);}
    h1 {font-size:20px;margin-bottom:10px;}
    label {font-size:13px;font-weight:600;display:block;margin:8px 0 3px;}
    input,select {width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;}
    .btn {width:100%;background:#2563eb;color:white;padding:12px;text-align:center;border-radius:6px;margin-top:12px;border:none;cursor:pointer;font-size:15px;}
    .btn:hover {background:#1d4ed8;}
    .alert-error {background:#fee2e2;color:#991b1b;padding:10px;border-radius:6px;margin-bottom:10px;}
    .alert-success {background:#ecfccb;color:#166534;padding:10px;border-radius:6px;margin-bottom:10px;}
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="card">
    <h1>Signatory Profile</h1>

    <?php if ($error): ?><div class="alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post">
      <label>Full Name</label>
      <input type="text" name="full_name" required value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>">

      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">

      <label>Position Title</label>
      <select name="position_title" required>
        <option value="">-- Select Position --</option>
        <?php foreach ($signatoryTitles as $title): ?>
          <option value="<?= htmlspecialchars($title) ?>" <?= ($profile['position_title'] ?? '')===$title?'selected':'' ?>>
            <?= htmlspecialchars($title) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Organization</label>
      <select name="organization_id" id="org-select" required>
        <option value="">-- Select Organization --</option>
        <?php foreach ($organizations as $o): ?>
          <option value="<?= $o['id'] ?>" <?= ($profile['organization_id']??'')==$o['id']?'selected':'' ?>>
            <?= htmlspecialchars($o['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Department</label>
      <select name="department_id" id="dept-select"><option value="">-- Select Department --</option></select>

      <label>Directory</label>
      <select name="directory_id" id="dir-select">
        <option value="">-- Select Directory --</option>
        <?php foreach ($directories as $d): ?>
          <option value="<?= $d['id'] ?>" <?= ($profile['directory_id']??'')==$d['id']?'selected':'' ?>>
            <?= htmlspecialchars($d['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label>Section</label>
      <select name="section_id" id="sec-select"><option value="">-- Select Section --</option></select>

      <label>Cheque Number</label>
      <input type="text" name="cheque_number" value="<?= htmlspecialchars($profile['cheque_number'] ?? '') ?>">

      <button type="submit" class="btn">Save Profile</button>
    </form>
  </div>
</div>
<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Powered by UCC
</footer>
<script>
// JS chain to load depts and sections (similar to create_user.php)
function loadDropdown(url, targetId, selected) {
  const sel = document.getElementById(targetId);
  sel.innerHTML = '<option>Loading...</option>';
  fetch(url).then(r => r.json()).then(j => {
    sel.innerHTML = '<option value="">-- Select --</option>';
    if (j.success) {
      j.data.forEach(row => {
        const opt = document.createElement("option");
        opt.value = row.id; opt.text = row.name;
        if (selected && selected == row.id) opt.selected = true;
        sel.add(opt);
      });
    }
  });
}

const orgSel = document.getElementById("org-select");
const deptSel = document.getElementById("dept-select");
const dirSel = document.getElementById("dir-select");
const secSel = document.getElementById("sec-select");

// preselected values
const selectedDept = "<?= $profile['department_id'] ?? '' ?>";
const selectedSec  = "<?= $profile['section_id'] ?? '' ?>";

orgSel.addEventListener("change", () => {
  if (orgSel.value) {
    loadDropdown(`../api/get_departments.php?organization_id=${orgSel.value}`, "dept-select", selectedDept);
  } else {
    deptSel.innerHTML = '<option value="">-- Select Department --</option>';
  }
});

dirSel.addEventListener("change", () => {
  if (dirSel.value) {
    loadDropdown(`../api/get_sections.php?directory_id=${dirSel.value}`, "sec-select", selectedSec);
  } else {
    secSel.innerHTML = '<option value="">-- Select Section --</option>';
  }
});

// trigger prefill if values exist
window.addEventListener("DOMContentLoaded", () => {
  if (orgSel.value) orgSel.dispatchEvent(new Event("change"));
  if (dirSel.value) dirSel.dispatchEvent(new Event("change"));
});
</script>
</body>
</html>
