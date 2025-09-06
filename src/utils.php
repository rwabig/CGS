<?php
// Misc helpers
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($url): void { header("Location: $url"); exit; }
?>
