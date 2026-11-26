<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['report_id'])) {
        throw new Exception('Missing report ID');
    }

    $query = "SELECT * FROM missing_reports WHERE report_id = $1";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$input['report_id']]);
    $report = $stmt->fetch();

    if (!$report) {
        throw new Exception('Report not found');
    }

    echo json_encode([
        'success' => true,
        'report' => $report
    ]);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>