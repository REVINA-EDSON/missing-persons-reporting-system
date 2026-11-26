<?php
$pdo = new PDO("pgsql:host=localhost;dbname=missing_persons_db", "postgres", "postgres777");
$report_id = $_GET['report_id'];

$stmt = $pdo->prepare("SELECT reporter_first_name, reporter_last_name, phone_number, permanent_residence FROM missing_reports WHERE report_id = ?");
$stmt->execute([$report_id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>
