<?php
// submit_missing_report.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- Database Configuration (PostgreSQL) ---
$host = 'localhost';
$port = 5432;
$dbname = 'missing_persons_db';
$user = 'postgres';
$password = 'postgres777';

// --- Helper Functions ---

/**
 * Sends data to a WebSocket server.
 */
function send_to_websocket(array $data): void {
    $ws_host = 'localhost';
    $ws_port = 8080;
    $message = json_encode($data);

    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        error_log("socket_create() failed: " . socket_strerror(socket_last_error()));
        return;
    }

    if (socket_connect($socket, $ws_host, $ws_port) === false) {
        error_log("socket_connect() failed: " . socket_strerror(socket_last_error()));
        socket_close($socket);
        return;
    }

    socket_write($socket, $message, strlen($message));
    socket_close($socket);
}

/**
 * Handles file upload with improved validation.
 */
function handleFileUpload(array $file, string $reportId): string|false {
    // Validate upload error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("Upload error code: " . $file['error']);
        return false;
    }

    // Validate file type
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    if (!array_key_exists($mime, $allowedTypes)) {
        error_log("Invalid file type: " . $mime);
        return false;
    }

    // Validate file size (5MB max)
    if ($file['size'] > 5242880) {
        error_log("File too large: " . $file['size']);
        return false;
    }

    // Prepare upload directory
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        error_log("Cannot create upload directory");
        return false;
    }

    // Generate secure filename
    $extension = $allowedTypes[$mime];
    $filename = sprintf('%s.%s', preg_replace('/[^a-z0-9]/i', '_', $reportId), $extension);
    $destination = $uploadDir . $filename;

    // Move file securely
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log("Failed to move uploaded file");
        return false;
    }

    return $destination;
}

// --- Main Logic ---
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

try {
    // Validate required fields with detailed error reporting
    $requiredFields = [
        'first_name' => 'Reporter first name',
        'last_name' => 'Reporter last name',
        'age' => 'Reporter age',
        'gender' => 'Reporter gender',
        'permanent_residence' => 'Permanent residence',
        'current_residence' => 'Current residence',
        'phone_number' => 'Phone number',
        'relationship' => 'Relationship',
        'missing_first_name' => 'Missing person first name',
        'missing_last_name' => 'Missing person last name',
        'missing_age' => 'Missing person age',
        'missing_gender' => 'Missing person gender',
        'last_seen_location' => 'Last seen location',
        'physical_appearance' => 'Physical appearance',
        'lost_time' => 'Time last seen',
        'lost_day' => 'Date last seen'
    ];

    $missing = [];
    foreach ($requiredFields as $field => $description) {
        if (empty($_POST[$field])) {
            $missing[] = $description;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        die(json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: ' . implode(', ', $missing),
            'missing_fields' => array_keys(array_filter($requiredFields, fn($f) => empty($_POST[$f])))
        ]));
    }

    // Process file upload
    $picturePath = null;
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $picturePath = handleFileUpload($_FILES['picture'], uniqid('img_', true));
        if ($picturePath === false) {
            http_response_code(400);
            die(json_encode(['status' => 'error', 'message' => 'Invalid file upload']));
        }
    }

    // Connect to database
    $conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
    $dbconn = pg_connect($conn_string);

    if (!$dbconn) {
        http_response_code(500);
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
    }

    // Generate report ID and timestamp
    $reportId = 'M-' . date('Ymd') . '-' . bin2hex(random_bytes(4));
    $reportDate = date('Y-m-d H:i:s');

    // Prepare data for approved_cases table
    $query = "INSERT INTO approved_cases (
        report_id, type,
        reporter_first_name, reporter_middle_name, reporter_last_name,
        reporter_age, reporter_gender,
        permanent_residence, current_residence,
        phone_number, relationship_or_place,
        person_first_name, person_middle_name, person_last_name,
        person_age, person_gender,
        location, physical_appearance,
        event_time, event_day,
        picture, additional_description,
        report_date
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22)";

    $params = [
        $reportId,
        'missing',
        htmlspecialchars($_POST['first_name']),
        htmlspecialchars($_POST['middle_name'] ?? ''),
        htmlspecialchars($_POST['last_name']),
        (int)$_POST['age'],
        htmlspecialchars($_POST['gender']),
        htmlspecialchars($_POST['permanent_residence']),
        htmlspecialchars($_POST['current_residence']),
        preg_replace('/[^0-9]/', '', $_POST['phone_number']),
        htmlspecialchars($_POST['relationship']),
        htmlspecialchars($_POST['missing_first_name']),
        htmlspecialchars($_POST['missing_middle_name'] ?? ''),
        htmlspecialchars($_POST['missing_last_name']),
        (int)$_POST['missing_age'],
        htmlspecialchars($_POST['missing_gender']),
        htmlspecialchars($_POST['last_seen_location']),
        htmlspecialchars($_POST['physical_appearance']),
        $_POST['lost_time'],
        $_POST['lost_day'],
        $picturePath,
        htmlspecialchars($_POST['additional_description'] ?? ''),
        $reportDate
    ];

    // Execute query
    pg_query($dbconn, "BEGIN");
    $result = pg_query_params($dbconn, $query, $params);

    if (!$result) {
        pg_query($dbconn, "ROLLBACK");
        http_response_code(500);
        die(json_encode([
            'status' => 'error',
            'message' => 'Database error',
            'db_error' => pg_last_error($dbconn)
        ]));
    }

    pg_query($dbconn, "COMMIT");

    // Prepare WebSocket data
    $report_data = [
        'reportId' => $reportId,
        'type' => 'missing',
        'timestamp' => $reportDate,
        'reporter' => [
            'name' => trim($_POST['first_name'] . ' ' . $_POST['last_name']),
            'phone' => $_POST['phone_number']
        ],
        'missingPerson' => [
            'name' => trim($_POST['missing_first_name'] . ' ' . $_POST['missing_last_name']),
            'lastSeen' => $_POST['last_seen_location']
        ]
    ];

    send_to_websocket($report_data);

    echo json_encode([
        'status' => 'success',
        'message' => 'Report submitted successfully',
        'report_id' => $reportId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]));
}