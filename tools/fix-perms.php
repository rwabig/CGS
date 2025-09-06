<?php
// Run on Linux/Mac: php tools/fix-perms.php
$root = dirname(__DIR__);
$dirs = [$root.'/public/assets', $root.'/database'];
foreach ($dirs as $d) {
  if (is_dir($d)) {
    echo "Fixing perms for $d\n";
    chmod($d, 0775);
  }
}
?>
