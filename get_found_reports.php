<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("SELECT *, 
                          CASE WHEN picture IS NOT NULL 
                               THEN '/uploads/' || picture 
                               ELSE NULL 
                          END as picture_path 
                          FROM found_reports 
                          ORDER BY report_date DESC");
    $stmt->execute();
    $reports = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'reports' => $reports
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => 'Image path base: ' . $_SERVER['DOCUMENT_ROOT'] . '/uploads/'
    ]);
}
?>