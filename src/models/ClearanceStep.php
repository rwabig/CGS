<?php
class ClearanceStep {
  public static function byClearance($cid) {
    $stmt=Database::$pdo->prepare('SELECT cs.*, d.name AS dept FROM clearance_steps cs JOIN departments d ON d.id=cs.department_id WHERE clearance_id=? ORDER BY step_order');
    $stmt->execute([$cid]);
    return $stmt->fetchAll();
  }
}
?>
