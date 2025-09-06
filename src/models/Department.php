<?php
class Department {
  public static function all() {
    return Database::$pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
  }
}
?>
