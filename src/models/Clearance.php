<?php
class Clearance {
  public static function findByUser($userId) {
    $stmt=Database::$pdo->prepare('SELECT * FROM clearances WHERE user_id=? ORDER BY created_at DESC');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
  }
}
?>
