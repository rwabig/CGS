<?php
class User {
  public static function findById($id) {
    $stmt = Database::$pdo->prepare('SELECT * FROM users WHERE id=?');
    $stmt->execute([$id]);
    return $stmt->fetch();
  }
  public static function all() {
    return Database::$pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();
  }
}
?>
