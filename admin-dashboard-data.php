<?php
$host = 'localhost';
$port = 5432;
$dbname = 'missing_persons_db';
$user = 'postgres';
$password = 'postgres777';

$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
$dbconn = pg_connect($conn_string);

if (!$dbconn) {
    die(json_encode(['error' => 'Database connection failed']));
}

header('Content-Type: application/json');

// Fetch missing reports
$missing_query = "SELECT * FROM missing_reports WHERE status = 'pending'";
$missing_result = pg_query($dbconn, $missing_query);
$missing = [];
while ($row = pg_fetch_assoc($missing_result)) {
    $missing[] = $row;
}

// Fetch found reports
$found_query = "SELECT * FROM found_reports WHERE status = 'pending'";
$found_result = pg_query($dbconn, $found_query);
$found = [];
while ($row = pg_fetch_assoc($found_result)) {
    $found[] = $row;
}

// Fetch totals
$total_missing = pg_fetch_result(pg_query($dbconn, "SELECT COUNT(*) FROM missing_reports WHERE status = 'pending'"), 0, 0);
$total_found = pg_fetch_result(pg_query($dbconn, "SELECT COUNT(*) FROM found_reports WHERE status = 'pending'"), 0, 0);
$pending_reports = $total_missing + $total_found;

pg_close($dbconn);

echo json_encode([
    'totalMissing' => (int)$total_missing,
    'totalFound' => (int)$total_found,
    'pendingReports' => (int)$pending_reports,
    'missing' => $missing,
    'found' => $found
]);
?>