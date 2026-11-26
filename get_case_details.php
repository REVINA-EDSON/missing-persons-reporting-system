<?php
header('Content-Type: application/json');

$caseId = $_GET['case_id'] ?? null;

if (!$caseId) {
    die(json_encode(['error' => 'Case ID is required']));
}

// Database connection
$conn = new mysqli("localhost", "root", "", "missing_persons_db");

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Fetch case details
$stmt = $conn->prepare("SELECT * FROM cases WHERE id = ?");
$stmt->bind_param("i", $caseId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $caseData = $result->fetch_assoc();
    echo json_encode($caseData);
} else {
    echo json_encode(['error' => 'Case not found']);
}

$stmt->close();
$conn->close();
?>