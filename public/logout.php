<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Ensure user is logged in before logging out
Auth::requireLogin();

// Perform logout (silent, no redirect here)
Auth::logout(true);

// Redirect to login with a success flag
header('Location: login.php?logged_out=1');
exit;
?>
