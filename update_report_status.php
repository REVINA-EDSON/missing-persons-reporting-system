<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['report_id']) || empty($input['status'])) {
        throw new Exception('Missing required fields');
    }
    
    $allowedStatuses = ['approved', 'rejected'];
    if (!in_array($input['status'], $allowedStatuses)) {
        throw new Exception('Invalid status value');
    }

    $stmt = $pdo->prepare("UPDATE missing_reports SET status = $1 WHERE report_id = $2");
    $stmt->execute([$input['status'], $input['report_id']]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No records updated - report may not exist');
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>