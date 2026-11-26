<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Get total approved cases count
    $totalStmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM approved_cases
    ");
    $totalStmt->execute();
    $total = $totalStmt->fetchColumn();

    // Get monthly approved cases count
    $monthlyStmt = $pdo->prepare("
        SELECT COUNT(*) as monthly 
        FROM approved_cases
        WHERE approval_date >= DATE_TRUNC('month', CURRENT_DATE)
    ");
    $monthlyStmt->execute();
    $monthly = $monthlyStmt->fetchColumn();

    // Get today's approved cases count
    $todayStmt = $pdo->prepare("
        SELECT COUNT(*) as today 
        FROM approved_cases
        WHERE approval_date >= CURRENT_DATE
    ");
    $todayStmt->execute();
    $today = $todayStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'total' => (int)$total,
        'monthly' => (int)$monthly,
        'today' => (int)$today,
        'last_updated' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>