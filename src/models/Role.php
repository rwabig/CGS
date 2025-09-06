<?php
class Role {
  public static function all() {
    return Database::$pdo->query('SELECT * FROM roles ORDER BY name')->fetchAll();
  }
}
?>
