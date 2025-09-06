<?php
require_once __DIR__.'/../src/bootstrap.php';
Auth::logout();
header('Location: login.php');
exit;
?>
