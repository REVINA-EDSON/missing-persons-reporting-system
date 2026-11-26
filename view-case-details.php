<?php
require 'db_connection.php';

if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];

    $stmt = $pdo->prepare("SELECT * FROM approved_cases WHERE report_id = :report_id");
    $stmt->execute(['report_id' => $report_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode(['error' => 'Case not found']);
    }
}
?>
