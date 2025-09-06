<?php
return [
  'app_url' => $_ENV['APP_URL'] ?? 'http://localhost/CGS/public',
  'debug' => ($_ENV['APP_DEBUG'] ?? 'false')==='true',
];
?>
