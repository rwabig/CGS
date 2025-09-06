<?php
require_once __DIR__.'/../Auth.php';
Auth::requireRole($roles ?? []);
?>
