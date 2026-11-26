
<?php
require_once 'db_connection.php';

$type = $_GET['type'] ?? ''; // 'approved' or 'rejected'

if ($type === 'approved') {
    $query = "SELECT * FROM approved_cases ORDER BY approval_date DESC";
    $stmt = $pdo->prepare($query);
} elseif ($type === 'rejected') {
    $query = "SELECT * FROM rejected_cases ORDER BY rejection_date DESC";
    $stmt = $pdo->prepare($query);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid case type']);
    exit;
}

try {
    $stmt->execute();
    $cases = $stmt->fetchAll();
    
    // Format dates for better display
    foreach ($cases as &$case) {
        $case['event_day'] = date('M d, Y', strtotime($case['event_day']));
        if ($type === 'approved') {
            $case['approval_date'] = date('M d, Y H:i', strtotime($case['approval_date']));
        } else {
            $case['rejection_date'] = date('M d, Y H:i', strtotime($case['rejection_date']));
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($cases);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
