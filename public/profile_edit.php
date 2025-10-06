<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$roles = Auth::roles();
$isStudent = in_array('student', $roles);
$isStaff   = in_array('signatory', $roles) || in_array('admin', $roles) || in_array('super_admin', $roles);

$pdo = Database::getConnection();
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>CGS | Edit Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="assets/css/cgs.css" rel="stylesheet">
  <style>
    body { background: #f3f4f6; font-family: Arial, sans-serif; display: flex; flex-direction: column; height: 100vh; margin: 0; }
    header { background: #1e3a8a; color: white; padding: 10px 20px; font-size: 1.2rem; font-weight: bold; }
    .container { flex: 1; display: flex; flex-direction: column; justify-content: flex-start; align-items: center; padding: 20px; overflow-y: auto; }
    .card { background: white; border-radius: 10px; box-shadow: 0 6px 20px rgba(0,0,0,0.08); max-width: 700px; width: 100%; padding: 20px; }
    h1 { margin-top: 0; text-align: center; }
    label { display: block; font-weight: 600; margin-top: 10px; font-size: 14px; }
    select, input { width: 100%; padding: 8px; margin-top: 4px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
    select:focus, input:focus { outline: 2px solid #2563eb; outline-offset: 1px; }
    .btn { display: block; width: 100%; margin-top: 15px; padding: 10px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1rem; }
    .btn:hover { background: #1d4ed8; }
    .photo-preview { display: block; width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid #e5e7eb; }
    footer { background: black; color: white; text-align: center; padding: 8px; font-size: 13px; }
    #photoLoader { display: none; font-size: 13px; color: #6b7280; margin-bottom: 8px; }

    /* Toast notification styles */
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
<header>CGS | Edit My Profile</header>
<div class="container">
  <div class="card">
    <h1>Profile Details</h1>
    <p style="text-align:center; color:#6b7280; font-size:14px;">Update your organizational details and personal information below.</p>

    <!-- Passport Photo Upload -->
    <form id="photoForm" enctype="multipart/form-data" method="post">
        <img src="<?= !empty($profile['passport_photo']) ? 'uploads/'.htmlspecialchars($profile['passport_photo']) : 'assets/img/default-avatar.png' ?>"
             class="photo-preview" id="photoPreview">
        <span id="photoLoader">Uploading photo...</span>
        <input type="file" name="passport_photo" accept="image/*">
    </form>

    <form id="profileForm">
      <!-- Org → Dept → Cat → Program -->
      <label for="organization">Organization (School/Institute)</label>
      <select name="organization_id" id="organization" required></select>

      <label for="department">Department</label>
      <select name="department_id" id="department" required></select>

      <label for="category">Category</label>
      <select name="category_id" id="category" required></select>

      <label for="program">Program</label>
      <select name="program_id" id="program" required></select>

      <?php if ($isStudent): ?>
        <label for="reg_number">Registration Number</label>
        <input type="text" name="registration_number" id="reg_number" value="<?= htmlspecialchars($profile['registration_number'] ?? '') ?>">

        <label for="completion_year">Completion Year</label>
        <input type="number" name="completion_year" id="completion_year" min="2000" max="<?= date('Y')+5 ?>" value="<?= htmlspecialchars($profile['completion_year'] ?? '') ?>">

        <label>Residence Status</label>
        <select name="residence_status">
          <option value="residential" <?= ($profile['residence_status'] ?? '') === 'residential' ? 'selected' : '' ?>>Residential</option>
          <option value="non_residential" <?= ($profile['residence_status'] ?? '') === 'non_residential' ? 'selected' : '' ?>>Non-Residential</option>
        </select>

        <label for="hall">Hall/Residence (if residential)</label>
        <input type="text" name="hall" id="hall" value="<?= htmlspecialchars($profile['hall'] ?? '') ?>">

        <label for="address">Current Address</label>
        <input type="text" name="address" id="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">

      <?php elseif ($isStaff): ?>
        <label for="pf_number">PF/Check Number</label>
        <input type="text" name="pf_number" id="pf_number" value="<?= htmlspecialchars($profile['pf_number'] ?? '') ?>">

        <label for="title_id">Title/Post</label>
        <select name="title_id" id="title_id"></select>

        <label for="address">Current Address</label>
        <input type="text" name="address" id="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
      <?php endif; ?>

      <button type="submit" class="btn">Save Profile</button>
    </form>

    <p style="text-align:center; margin-top:15px;">
      <a href="dashboard.php">&larr; Back to Dashboard</a>
    </p>
  </div>
</div>

<!-- Toast container -->
<div id="toast" class="toast"></div>

<footer>© <?= date('Y') ?> University Digital Clearance System | Case study: ARU</footer>

<script>
// --- Toast notification function ---
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// --- Photo Upload with Live Preview ---
document.querySelector('#photoForm input[type="file"]').addEventListener('change', async (e) => {
    const formData = new FormData(document.getElementById('photoForm'));
    document.getElementById('photoLoader').style.display = 'inline';

    try {
        const res = await fetch('api/update_profile_photo.php', { method: 'POST', body: formData });
        const json = await res.json();
        document.getElementById('photoLoader').style.display = 'none';

        if (json.status === 'success') {
            document.getElementById('photoPreview').src = json.photo;
            showToast('Photo updated successfully!', 'success');
        } else {
            showToast(json.message, 'error');
        }
    } catch (err) {
        document.getElementById('photoLoader').style.display = 'none';
        showToast('Upload failed. Please try again.', 'error');
    }
});

// --- Dynamic dropdown loaders (Org > Dept > Category > Program) ---
async function loadOptions(endpoint, target, selectedId = null) {
    const res = await fetch(endpoint);
    const data = await res.json();
    const select = document.getElementById(target);
    select.innerHTML = data.map(item =>
        `<option value="${item.id}" ${item.id == selectedId ? 'selected' : ''}>${item.name}</option>`
    ).join('');
}

loadOptions('api/get_organizations.php', 'organization', <?= json_encode($profile['organization_id'] ?? null) ?>)
  .then(() => loadOptions(`api/get_departments.php?organization_id=${document.getElementById('organization').value}`, 'department', <?= json_encode($profile['department_id'] ?? null) ?>))
  .then(() => loadOptions(`api/get_categories.php?organization_id=${document.getElementById('organization').value}`, 'category', <?= json_encode($profile['category_id'] ?? null) ?>))
  .then(() => loadOptions(`api/get_programs.php?department_id=${document.getElementById('department').value}&category_id=${document.getElementById('category').value}`, 'program', <?= json_encode($profile['program_id'] ?? null) ?>));

document.getElementById('organization').addEventListener('change', (e) => {
    loadOptions(`api/get_departments.php?organization_id=${e.target.value}`, 'department');
    loadOptions(`api/get_categories.php?organization_id=${e.target.value}`, 'category');
});

document.getElementById('department').addEventListener('change', (e) => {
    loadOptions(`api/get_programs.php?department_id=${e.target.value}&category_id=${document.getElementById('category').value}`, 'program');
});

document.getElementById('category').addEventListener('change', (e) => {
    loadOptions(`api/get_programs.php?department_id=${document.getElementById('department').value}&category_id=${e.target.value}`, 'program');
});

// --- Profile AJAX Save ---
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch('api/update_profile.php', { method: 'POST', body: formData });
    const json = await res.json();
    showToast(json.message, json.status === 'success' ? 'success' : 'error');
});
</script>
</body>
</html>
