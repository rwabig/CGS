<?php
// public/student/profile.php
require_once __DIR__ . '/../../src/bootstrap.php';

$db   = Database::getConnection();
$authUser = Auth::user();
$currentUserId = $authUser['id'] ?? ($_SESSION['user_id'] ?? 0);

$userId = isset($_GET['user']) ? (int)$_GET['user'] : $currentUserId;

// Access control: students can see their own, admins/super_admins can see any
if ($userId !== $currentUserId && !Auth::hasRole('admin') && !Auth::hasRole('super_admin')) {
    http_response_code(403);
    exit("Forbidden");
}

$error = '';
$success = '';

// Load profile if exists
$stmt = $db->prepare("SELECT * FROM student_profiles WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($userId === $currentUserId || Auth::hasRole('admin') || Auth::hasRole('super_admin'))) {
    $name        = trim($_POST['name'] ?? '');
    $regNo       = trim($_POST['registration_number'] ?? '');
    $orgId       = $_POST['organization_id'] ?: null;
    $deptId      = $_POST['department_id'] ?: null;
    $catId       = $_POST['category_id'] ?: null;
    $progId      = $_POST['program_id'] ?: null;
    $resStatus   = $_POST['residential_status'] ?? 'non_residential';
    $hallName    = $resStatus === 'residential' ? trim($_POST['hall_name'] ?? '') : null;
    $address     = trim($_POST['current_address'] ?? '');
    $completion  = $_POST['completion_year'] ?: null;
    $gradDate    = $_POST['graduation_date'] ?: null;

    // Handle passport photo upload
    $photoPath = $profile['photo_path'] ?? null;
    if (!empty($_FILES['photo']['name'])) {
        $uploadDir = __DIR__ . "/../../uploads/photos/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $fileName = "student_{$userId}_" . time() . "." . $ext;
        $target = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
            $photoPath = "uploads/photos/" . $fileName;
        }
    }

    if ($name && $regNo && $orgId && $deptId && $catId && $progId) {
        if ($profile) {
            $stmt = $db->prepare("UPDATE student_profiles
                SET name=:n, registration_number=:r, organization_id=:o, department_id=:d,
                    category_id=:c, program_id=:p, residential_status=:rs, hall_name=:h,
                    current_address=:a, completion_year=:cy, graduation_date=:gd, photo_path=:pp,
                    updated_at=NOW()
                WHERE user_id=:uid");
            $stmt->execute([
                ':n'=>$name, ':r'=>$regNo, ':o'=>$orgId, ':d'=>$deptId,
                ':c'=>$catId, ':p'=>$progId, ':rs'=>$resStatus, ':h'=>$hallName,
                ':a'=>$address, ':cy'=>$completion, ':gd'=>$gradDate, ':pp'=>$photoPath,
                ':uid'=>$userId
            ]);
            $success = "Profile updated successfully.";
        } else {
            $stmt = $db->prepare("INSERT INTO student_profiles
                (user_id, name, registration_number, organization_id, department_id, category_id, program_id,
                 residential_status, hall_name, current_address, completion_year, graduation_date, photo_path, created_at, updated_at)
                VALUES (:uid,:n,:r,:o,:d,:c,:p,:rs,:h,:a,:cy,:gd,:pp,NOW(),NOW())");
            $stmt->execute([
                ':uid'=>$userId, ':n'=>$name, ':r'=>$regNo, ':o'=>$orgId, ':d'=>$deptId,
                ':c'=>$catId, ':p'=>$progId, ':rs'=>$resStatus, ':h'=>$hallName,
                ':a'=>$address, ':cy'=>$completion, ':gd'=>$gradDate, ':pp'=>$photoPath
            ]);
            $success = "Profile created successfully.";
        }
    } else {
        $error = "Please fill all required fields.";
    }
}

// Load organizations
$organizations = $db->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Profile - CGS</title>
  <link href="../assets/css/cgs.css" rel="stylesheet">
  <style>
    body {background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;margin:0;}
    .container {max-width:600px;margin:20px auto;padding:20px;}
    .profile-card {background:white;padding:20px;border-radius:10px;box-shadow:0 6px 20px rgba(0,0,0,0.08);}
    h1 {font-size:20px;margin-bottom:10px;}
    label {font-size:13px;font-weight:600;display:block;margin:8px 0 3px;}
    input,select {width:100%;padding:8px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;}
    .btn {display:block;width:100%;background:#2563eb;color:white;padding:12px;text-align:center;border-radius:6px;margin-top:10px;font-size:15px;cursor:pointer;border:none;}
    .btn:hover {background:#1d4ed8;}
    .alert {padding:10px;border-radius:6px;margin-bottom:10px;font-size:14px;}
    .alert-error {background:#fee2e2;color:#991b1b;}
    .alert-success {background:#ecfccb;color:#166534;}
    .photo-preview {margin:10px 0;text-align:center;}
    .photo-preview img {max-width:120px;border-radius:6px;}
  </style>
</head>
<body>
<?php include __DIR__ . '/../includes/menu.php'; ?>
<div class="container">
  <div class="profile-card">
    <h1>Student Profile</h1>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <label>Name</label>
      <input type="text" name="name" value="<?= htmlspecialchars($profile['name'] ?? '') ?>" required>

      <label>Registration Number</label>
      <input type="text" name="registration_number" value="<?= htmlspecialchars($profile['registration_number'] ?? '') ?>" required>

      <label>School / Institute</label>
      <select name="organization_id" id="organization-select" required>
        <option value="">-- Select School/Institute --</option>
        <?php foreach ($organizations as $o): ?>
          <option value="<?= $o['id'] ?>" <?= ($profile['organization_id']??'')==$o['id']?'selected':'' ?>>
            <?= htmlspecialchars($o['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Other dropdowns dynamically loaded via API -->
      <label>Department</label>
      <select name="department_id" id="department-select" required><option value="">-- Select Department --</option></select>

      <label>Category</label>
      <select name="category_id" id="category-select" required><option value="">-- Select Category --</option></select>

      <label>Program</label>
      <select name="program_id" id="program-select" required><option value="">-- Select Program --</option></select>

      <label>Residential Status</label>
      <select name="residential_status" id="residential-status" required>
        <option value="residential" <?= ($profile['residential_status']??'')==='residential'?'selected':'' ?>>Residential</option>
        <option value="non_residential" <?= ($profile['residential_status']??'')==='non_residential'?'selected':'' ?>>Non-Residential</option>
      </select>

      <div id="hall-field" style="display:none;">
        <label>Hall / Residence Name</label>
        <input type="text" name="hall_name" value="<?= htmlspecialchars($profile['hall_name'] ?? '') ?>">
      </div>

      <label>Current Address</label>
      <input type="text" name="current_address" value="<?= htmlspecialchars($profile['current_address'] ?? '') ?>">

      <label>Completion Year</label>
      <input type="number" name="completion_year" value="<?= htmlspecialchars($profile['completion_year'] ?? '') ?>">

      <label>Graduation Date</label>
      <input type="date" name="graduation_date" value="<?= htmlspecialchars($profile['graduation_date'] ?? '') ?>">

      <label>Passport Photo</label>
      <input type="file" name="photo" accept="image/*">
      <?php if (!empty($profile['photo_path'])): ?>
        <div class="photo-preview"><img src="../<?= htmlspecialchars($profile['photo_path']) ?>" alt="Photo"></div>
      <?php endif; ?>

      <button type="submit" class="btn">Save Profile</button>
    </form>
  </div>
</div>
<footer style="text-align:center;padding:8px;background:#000;color:white;font-size:13px;">
  © <?= date('Y') ?> University Digital Clearance System | Case study: ARU by Rwabigimbo et al. | Powered by: UCC
</footer>
<script>
// JS chain same as before (loading depts/cats/programs + hall toggle)
const orgSel  = document.getElementById("organization-select");
const deptSel = document.getElementById("department-select");
const catSel  = document.getElementById("category-select");
const progSel = document.getElementById("program-select");

const selectedDept = "<?= $profile['department_id'] ?? '' ?>";
const selectedCat  = "<?= $profile['category_id'] ?? '' ?>";
const selectedProg = "<?= $profile['program_id'] ?? '' ?>";

function setLoading(sel, label){
  sel.innerHTML = `<option value="">⏳ Loading ${label}...</option>`;
}

function loadDepartments(orgId, preselect){
  if(!orgId){ deptSel.innerHTML='<option value="">-- Select Department --</option>'; return Promise.resolve(); }
  setLoading(deptSel,"Departments");
  return fetch(`../api/get_departments.php?organization_id=${orgId}`).then(r=>r.json()).then(j=>{
    deptSel.innerHTML='<option value="">-- Select Department --</option>';
    if(j.success){
      j.data.forEach(d=>{
        const opt=new Option(d.name,d.id,d.id==preselect,d.id==preselect);
        deptSel.add(opt);
      });
    }
  });
}
function loadCategories(deptId, preselect){
  if(!deptId){ catSel.innerHTML='<option value="">-- Select Category --</option>'; return Promise.resolve(); }
  setLoading(catSel,"Categories");
  return fetch(`../api/get_categories.php?department_id=${deptId}`).then(r=>r.json()).then(j=>{
    catSel.innerHTML='<option value="">-- Select Category --</option>';
    if(j.success){
      j.data.forEach(c=>{
        const opt=new Option(c.name,c.id,c.id==preselect,c.id==preselect);
        catSel.add(opt);
      });
    }
  });
}
function loadPrograms(catId, preselect){
  if(!catId){ progSel.innerHTML='<option value="">-- Select Program --</option>'; return Promise.resolve(); }
  setLoading(progSel,"Programs");
  return fetch(`../api/get_programs.php?category_id=${catId}`).then(r=>r.json()).then(j=>{
    progSel.innerHTML='<option value="">-- Select Program --</option>';
    if(j.success){
      j.data.forEach(p=>{
        const opt=new Option(p.name,p.id,p.id==preselect,p.id==preselect);
        progSel.add(opt);
      });
    }
  });
}

// chain events
orgSel.addEventListener("change", ()=> loadDepartments(orgSel.value));
deptSel.addEventListener("change", ()=> loadCategories(deptSel.value));
catSel.addEventListener("change", ()=> loadPrograms(catSel.value));

// prefill chain
window.addEventListener("DOMContentLoaded", ()=>{
  Promise.resolve()
    .then(()=> orgSel.value ? loadDepartments(orgSel.value, selectedDept) : null)
    .then(()=> selectedDept ? loadCategories(selectedDept, selectedCat) : null)
    .then(()=> selectedCat ? loadPrograms(selectedCat, selectedProg) : null);

  // hall toggle
  const resSel=document.getElementById("residential-status");
  const hall=document.getElementById("hall-field");
  function toggleHall(){ hall.style.display = resSel.value==="residential" ? "block":"none"; }
  resSel.addEventListener("change", toggleHall);
  toggleHall();
});
</script>
</body>
</html>
