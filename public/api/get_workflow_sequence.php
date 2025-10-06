<?php
// public/api/get_workflow_sequence.php
declare(strict_types=1);

ini_set('display_errors', '0'); // Hide errors in production
error_reporting(E_ALL);

require_once __DIR__ . '/../../src/bootstrap.php';
header('Content-Type: application/json');

try {
    // Require authentication & correct role
    Auth::requireRole('super_admin');

    $db = Database::getConnection();

    // Validate inputs
    $org  = isset($_GET['organization_id']) ? (int)$_GET['organization_id'] : 0;
    $dept = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
    $cat  = (isset($_GET['category_id']) && $_GET['category_id'] !== '')
              ? (int)$_GET['category_id']
              : null;

    if ($org <= 0 || $dept <= 0) {
        echo json_encode([
            'success' => false,
            'error'   => 'Organization and Department are required.'
        ]);
        exit;
    }

    // Build query with full joins
    $sql = "
        SELECT w.id, w.step_order, w.signatory_title,
               COALESCE(c.name, '-')  AS category,
               COALESCE(d.name, '-')  AS directory,
               COALESCE(s.name, '-')  AS section,
               o.name AS organization,
               dep.name AS department
          FROM workflows w
          INNER JOIN organizations o ON o.id = w.organization_id
          INNER JOIN departments dep ON dep.id = w.department_id
          LEFT JOIN categories c ON c.id = w.category_id
          LEFT JOIN directories d ON d.id = w.directory_id
          LEFT JOIN sections s   ON s.id = w.section_id
         WHERE w.organization_id = :org
           AND w.department_id   = :dept
    ";

    $params = [':org' => $org, ':dept' => $dept];

    if ($cat !== null) {
        $sql .= " AND w.category_id = :cat";
        $params[':cat'] = $cat;
    }

    $sql .= " ORDER BY w.step_order ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log access to audit_log
    $user = Auth::user();
    $logStmt = $db->prepare("
        INSERT INTO audit_log (user_id, action, details, created_at)
        VALUES (:user_id, :action, :details, NOW())
    ");
    $logStmt->execute([
        ':user_id' => $user['id'],
        ':action'  => 'workflow_sequence_view',
        ':details' => sprintf(
            "Viewed workflow sequence for Org #%d, Dept #%d%s",
            $org,
            $dept,
            $cat !== null ? ", Cat #$cat" : ""
        )
    ]);

    echo json_encode([
        'success' => true,
        'data'    => $rows
    ]);

} catch (AuthException $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Access denied.'
    ]);
} catch (Throwable $e) {
    error_log("Workflow sequence API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'An unexpected error occurred. Please try again later.'
    ]);
}
?>
