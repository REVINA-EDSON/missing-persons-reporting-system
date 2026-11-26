<?php
$host = 'localhost';
$port = 5432;
$dbname = 'missing_persons_db';
$user = 'postgres';
$password = 'postgres777';

$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
$dbconn = pg_connect($conn_string);

if (!$dbconn) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$report_id = $input['report_id'] ?? '';
$type = $input['type'] ?? '';
$action = $input['action'] ?? '';

if (empty($report_id) || !in_array($type, ['missing', 'found']) || !in_array($action, ['approve', 'reject'])) {
    die(json_encode(['status' => 'error', 'message' => 'Invalid input']));
}

$source_table = $type === 'missing' ? 'missing_reports' : 'found_reports';
$target_table = $action === 'approve' ? 'approved_cases' : 'rejected_cases';

// Fetch the report
$query = "SELECT * FROM $source_table WHERE report_id = $1 AND status = 'pending'";
$result = pg_query_params($dbconn, $query, [$report_id]);
$report = pg_fetch_assoc($result);

if (!$report) {
    die(json_encode(['status' => 'error', 'message' => 'Report not found or already processed']));
}

pg_query($dbconn, "BEGIN");

// Insert into target table
$insert_query = "
    INSERT INTO $target_table (
        report_id, type, reporter_first_name, reporter_middle_name, reporter_last_name, reporter_age, reporter_gender,
        permanent_residence, current_residence, phone_number, relationship_or_place, person_first_name, person_middle_name,
        person_last_name, person_age, person_gender, location, physical_appearance, event_time, event_day, picture,
        additional_description, report_date
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23)
";
$params = [
    $report['report_id'],
    $type,
    $report['reporter_first_name'],
    $report['reporter_middle_name'],
    $report['reporter_last_name'],
    $report['reporter_age'],
    $report['reporter_gender'],
    $report['permanent_residence'],
    $report['current_residence'],
    $report['phone_number'],
    $type === 'missing' ? $report['relationship'] : $report['place_of_found'],
    $type === 'missing' ? $report['missing_first_name'] : $report['found_first_name'],
    $type === 'missing' ? $report['missing_middle_name'] : $report['found_middle_name'],
    $type === 'missing' ? $report['missing_last_name'] : $report['found_last_name'],
    $type === 'missing' ? $report['missing_age'] : $report['found_age'],
    $type === 'missing' ? $report['missing_gender'] : $report['found_gender'],
    $type === 'missing' ? $report['last_seen_location'] : $report['found_location'],
    $report['physical_appearance'],
    $type === 'missing' ? $report['lost_time'] : $report['found_time'],
    $type === 'missing' ? $report['lost_day'] : $report['found_day'],
    $report['picture'],
    $report['additional_description'],
    $report['report_date']
];
$insert_result = pg_query_params($dbconn, $insert_query, $params);

if ($insert_result) {
    // Delete from source table
    $delete_query = "DELETE FROM $source_table WHERE report_id = $1";
    $delete_result = pg_query_params($dbconn, $delete_query, [$report_id]);

    if ($delete_result) {
        pg_query($dbconn, "COMMIT");
        echo json_encode(['status' => 'success', 'message' => 'Report processed successfully']);
    } else {
        pg_query($dbconn, "ROLLBACK");
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete from source table']);
    }
} else {
    pg_query($dbconn, "ROLLBACK");
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert into target table']);
}

pg_close($dbconn);
?>