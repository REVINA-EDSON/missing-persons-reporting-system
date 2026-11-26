<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Total reports
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM found_reports");
    $total = $stmt->fetchColumn();
    
    // Approved reports
    $stmt = $pdo->query("SELECT COUNT(*) as approved FROM found_reports WHERE status = 'approved'");
    $approved = $stmt->fetchColumn();
    
    // Pending reports
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM found_reports WHERE status = 'pending'");
    $pending = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'total' => $total,
        'approved' => $approved,
        'pending' => $pending
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>