<?php
// public/includes/menu.php
// Shared menu (CGS Topbar + Nav)

if (!isset($pageTitle)) {
    $pageTitle = '';
}

// Always point to /CGS/public as the base
$basePath   = parse_url(getenv('APP_URL') ?: '', PHP_URL_PATH) ?: '/CGS';
$basePublic = rtrim($basePath, '/') . '/public';

// Current user
$user = null;
$profile = [];
$avatar = null;
$initials = null;
$profileLink = $basePublic . '/profile.php';

if (class_exists('Auth') && Auth::check()) {
    $user = Auth::user();

    if ($user && isset($user['id'])) {
        try {
            $db = Database::getConnection();

            // Find role
            $stmt = $db->prepare("
                SELECT r.slug
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.id
                WHERE ur.user_id = :uid LIMIT 1
            ");
            $stmt->execute([':uid' => $user['id']]);
            $role = $stmt->fetchColumn();

            if ($role === 'super_admin') {
                $stmt = $db->prepare("SELECT full_name, avatar FROM super_admin_profiles WHERE user_id=:uid");
                $profileLink = $basePublic . '/super_admin/profile.php?user=' . (int)$user['id'];
            } elseif ($role === 'admin') {
                $stmt = $db->prepare("SELECT full_name, avatar FROM admin_profiles WHERE user_id=:uid");
                $profileLink = $basePublic . '/admin/profile.php?user=' . (int)$user['id'];
            } elseif ($role === 'signatory') {
                $stmt = $db->prepare("SELECT full_name, avatar FROM staff_profiles WHERE user_id=:uid");
                $profileLink = $basePublic . '/signatory/profile.php?user=' . (int)$user['id'];
            } elseif ($role === 'student') {
                $stmt = $db->prepare("SELECT full_name, photo AS avatar FROM student_profiles WHERE user_id=:uid");
                $profileLink = $basePublic . '/student/profile.php?user=' . (int)$user['id'];
            } else {
                $stmt = null;
            }

            if ($stmt) {
                $stmt->execute([':uid' => $user['id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $avatar = $profile['avatar'] ?? null;

                // Fallback: initials
                $name = $profile['full_name'] ?? ($user['email'] ?? '');
                if ($name) {
                    $parts = preg_split('/\s+/', trim($name));
                    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
                }
            }
        } catch (Throwable $e) {
            error_log("Menu profile load failed: " . $e->getMessage());
        }
    }
}
?>
<style>
.cgs-topbar {
  background:#1e3a8a; color:#fff; padding:10px 16px;
  display:flex; justify-content:space-between; align-items:center;
}
.cgs-brand { font-weight:700; }
.cgs-user { font-size:14px; display:flex; align-items:center; gap:8px; }
.cgs-avatar {
  width:32px; height:32px; border-radius:50%;
  overflow:hidden; display:flex; justify-content:center; align-items:center;
  font-weight:bold; font-size:14px; background:#2563eb; color:#fff;
}
.cgs-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.cgs-nav { background:#fff; padding:10px 16px; border-bottom:1px solid #e6eefb; }
.cgs-nav a { color:#1e3a8a; text-decoration:none; margin-right:14px; font-weight:600; }
.cgs-breadcrumb { background:#f8fafc; padding:12px 16px; border-bottom:1px solid #eef2ff; }
.cgs-breadcrumb h2 { margin:0; font-size:18px; color:#0f172a; }
</style>

<header class="cgs-topbar">
  <div class="cgs-brand">Clearance for Graduating Students (CGS)</div>
  <div class="cgs-user">
    <?php if ($user): ?>
      <div class="cgs-avatar">
        <?php if ($avatar): ?>
          <img src="<?= htmlspecialchars($basePublic . '/' . ltrim($avatar, '/')) ?>" alt="Avatar">
        <?php else: ?>
          <?= htmlspecialchars($initials ?? '?') ?>
        <?php endif; ?>
      </div>
      <?= htmlspecialchars($user['email'] ?? 'Unknown') ?>
      &nbsp;|&nbsp; <a href="<?= htmlspecialchars($profileLink) ?>" style="color:#ecf2ff;">Profile</a>
      &nbsp;|&nbsp; <a href="<?= htmlspecialchars($basePublic . '/logout.php') ?>" style="color:#ffdddd;">Logout</a>
    <?php else: ?>
      <a href="<?= htmlspecialchars($basePublic . '/login.php') ?>" style="color:#ecf2ff;">Login</a>
    <?php endif; ?>
  </div>
</header>

<nav class="cgs-nav">
  <a href="<?= htmlspecialchars($basePublic . '/index.php') ?>">Home</a>
  <a href="<?= htmlspecialchars($basePublic . '/dashboard.php') ?>">Dashboard</a>
  <?php if ($user && class_exists('Auth') && Auth::hasRole('super_admin')): ?>
    <a href="<?= htmlspecialchars($basePublic . '/super_admin/dashboard.php') ?>">Super Admin</a>
  <?php endif; ?>
  <?php if ($user && class_exists('Auth') && Auth::hasRole('admin')): ?>
    <a href="<?= htmlspecialchars($basePublic . '/admin/dashboard.php') ?>">Admin</a>
  <?php endif; ?>
  <?php if ($user && class_exists('Auth') && Auth::hasRole('signatory')): ?>
    <a href="<?= htmlspecialchars($basePublic . '/signatory/dashboard.php') ?>">Signatory</a>
  <?php endif; ?>
  <?php if ($user && class_exists('Auth') && Auth::hasRole('student')): ?>
    <a href="<?= htmlspecialchars($basePublic . '/student/dashboard.php') ?>">Student</a>
  <?php endif; ?>
</nav>

<section class="cgs-breadcrumb">
  <h2><?= htmlspecialchars($pageTitle ?: 'Dashboard') ?></h2>
</section>
