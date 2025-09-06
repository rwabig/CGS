<?php
class Csrf {
  public static function token(): string {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
  }
  public static function check($token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
  }
}
?>
