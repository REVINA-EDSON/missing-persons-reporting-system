<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['report_id'])) {
    echo json_encode(['success' => false, 'error' => 'Report ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM found_reports WHERE report_id = $1");
    $stmt->execute([$input['report_id']]);
    $report = $stmt->fetch();
    
    if ($report) {
        // Format picture path
        $report['picture'] = $report['picture'] ? 'uploads/' . $report['picture'] : null;
        
        echo json_encode([
            'success' => true,
            'report' => $report
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Report not found'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>