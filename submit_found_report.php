<?php
// submit_found_report.php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Debug: Log the start of the script
error_log("Starting submit_found_report.php");

// --- Database Configuration (PostgreSQL) ---
$host = 'localhost';
$port = 5432;
$dbname = 'missing_persons_db';
$user = 'postgres';
$password = 'postgres777';

// --- Helper Functions ---
function send_to_websocket(array $data): bool {
    // Check if sockets extension is loaded
    if (!extension_loaded('sockets')) {
        error_log("Sockets extension not loaded, skipping WebSocket message");
        return false;
    }

    $ws_host = 'localhost';
    $ws_port = 8080;
    $message = json_encode($data);

    try {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            error_log("socket_create() failed: " . socket_strerror(socket_last_error()));
            return false;
        }

        $connection = @socket_connect($socket, $ws_host, $ws_port);
        if ($connection === false) {
            error_log("socket_connect() failed: " . socket_strerror(socket_last_error()));
            socket_close($socket);
            return false;
        }

        $result = @socket_write($socket, $message, strlen($message));
        if ($result === false) {
            error_log("socket_write() failed: " . socket_strerror(socket_last_error()));
            socket_close($socket);
            return false;
        }

        socket_close($socket);
        error_log("WebSocket message sent successfully");
        return true;
    } catch (Exception $e) {
        error_log("WebSocket error: " . $e->getMessage());
        return false;
    }
}

function handleFileUpload(array $file, string $reportId): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . $file['error']);
        return false;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid file type: " . $file['type']);
        return false;
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        error_log("File too large: " . $file['size']);
        return false;
    }

    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
        error_log("Failed to create upload directory: " . $uploadDir);
        return false;
    }
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = $reportId . '.' . $fileExtension;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        error_log("Failed to move uploaded file: " . $destination);
        return false;
    }

    error_log("File uploaded successfully: " . $destination);
    return $destination;
}

// --- Main Logic ---
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

error_log("Processing POST request");

$formType = filter_input(INPUT_POST, 'form_type', FILTER_SANITIZE_STRING);
if ($formType !== 'found') {
    error_log("Invalid form type: " . $formType);
    echo json_encode(['status' => 'error', 'message' => 'Invalid form type']);
    exit;
}

// Reporter fields
$reporterFirstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
$reporterMiddleName = filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_STRING) ?? '';
$reporterLastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
$reporterAge = filter_input(INPUT_POST, 'age', FILTER_SANITIZE_NUMBER_INT);
$reporterGender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING) ?? 'Unknown';
$permanentResidence = filter_input(INPUT_POST, 'permanent_residence', FILTER_SANITIZE_STRING);
$currentResidence = filter_input(INPUT_POST, 'current_residence', FILTER_SANITIZE_STRING);
$phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
$placeOfFound = filter_input(INPUT_POST, 'relationship', FILTER_SANITIZE_STRING);

// Found person fields
$foundFirstName = filter_input(INPUT_POST, 'found_first_name', FILTER_SANITIZE_STRING) ?? null;
$foundMiddleName = filter_input(INPUT_POST, 'found_middle_name', FILTER_SANITIZE_STRING) ?? null;
$foundLastName = filter_input(INPUT_POST, 'found_last_name', FILTER_SANITIZE_STRING) ?? null;
$foundAge = filter_input(INPUT_POST, 'found_age', FILTER_SANITIZE_NUMBER_INT);
$foundGender = filter_input(INPUT_POST, 'found_gender', FILTER_SANITIZE_STRING);
$foundLocation = filter_input(INPUT_POST, 'found_location', FILTER_SANITIZE_STRING);
$physicalAppearance = filter_input(INPUT_POST, 'physical_appearance', FILTER_SANITIZE_STRING);
$foundTime = filter_input(INPUT_POST, 'found_time', FILTER_SANITIZE_STRING);
$foundDay = filter_input(INPUT_POST, 'found_day', FILTER_SANITIZE_STRING);

// Validation
$requiredReporterFields = [$reporterFirstName, $reporterLastName, $reporterAge, $permanentResidence, $currentResidence, $phoneNumber, $placeOfFound];
$requiredFoundFields = [$foundAge, $foundGender, $foundLocation, $physicalAppearance, $foundTime, $foundDay];

if (in_array('', $requiredReporterFields, true) || in_array(null, $requiredReporterFields, true)) {
    error_log("Missing required reporter fields");
    echo json_encode(['status' => 'error', 'message' => 'Missing required reporter fields']);
    exit;
}
if (in_array('', $requiredFoundFields, true) || in_array(null, $requiredFoundFields, true)) {
    error_log("Missing required found person fields");
    echo json_encode(['status' => 'error', 'message' => 'Missing required found person fields']);
    exit;
}

error_log("Validation passed");

// Database connection
$conn_string = "host={$host} port={$port} dbname={$dbname} user={$user} password={$password}";
$dbconn = pg_connect($conn_string);
if (!$dbconn) {
    error_log("Database connection failed: " . pg_last_error());
    echo json_encode(['status' => 'error', 'message' => "Could not connect to PostgreSQL: " . pg_last_error()]);
    exit;
}

error_log("Database connected");

pg_query($dbconn, "BEGIN");
$reportId = "F-" . date("YmdHis");
$reportDate = date("Y-m-d H:i:s");
$picturePath = '';

if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $picturePath = handleFileUpload($_FILES['picture'], $reportId);
    if ($picturePath === false) {
        pg_query($dbconn, "ROLLBACK");
        echo json_encode(['status' => 'error', 'message' => 'Failed to upload image']);
        exit;
    }
}

error_log("File upload processed");

// Insert into found_reports
$query = "INSERT INTO found_reports (
    report_id, reporter_first_name, reporter_middle_name, reporter_last_name, reporter_age, reporter_gender,
    permanent_residence, current_residence, phone_number, place_of_found, found_first_name, found_middle_name,
    found_last_name, found_age, found_gender, found_location, physical_appearance, found_time, found_day,
    picture, additional_description, report_date, status
) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, $19, $20, $21, $22, $23)";
$params = [
    $reportId, $reporterFirstName, $reporterMiddleName, $reporterLastName, $reporterAge, $reporterGender,
    $permanentResidence, $currentResidence, $phoneNumber, $placeOfFound, $foundFirstName, $foundMiddleName,
    $foundLastName, $foundAge, $foundGender, $foundLocation, $physicalAppearance, $foundTime, $foundDay,
    $picturePath, $_POST['additional_description'] ?? null, $reportDate, 'pending'
];

$result = pg_query_params($dbconn, $query, $params);
if ($result) {
    pg_query($dbconn, "COMMIT");
    error_log("Database insert successful");

    // Prepare WebSocket data
    $report_data = [
        'reportId' => $reportId,
        'type' => 'found',
        'reporterFirstName' => $reporterFirstName,
        'reporterMiddleName' => $reporterMiddleName,
        'reporterLastName' => $reporterLastName,
        'reporterAge' => $reporterAge,
        'reporterGender' => $reporterGender,
        'permanentResidence' => $permanentResidence,
        'currentResidence' => $currentResidence,
        'phoneNumber' => $phoneNumber,
        'placeOfFound' => $placeOfFound,
        'foundFirstName' => $foundFirstName,
        'foundMiddleName' => $foundMiddleName,
        'foundLastName' => $foundLastName,
        'foundAge' => $foundAge,
        'foundGender' => $foundGender,
        'foundLocation' => $foundLocation,
        'physicalAppearance' => $physicalAppearance,
        'foundTime' => $foundTime,
        'foundDay' => $foundDay,
        'picture' => $picturePath,
        'additionalDescription' => $_POST['additional_description'] ?? null,
        'reportDate' => $reportDate,
        'status' => 'pending'
    ];

    // Attempt to send WebSocket message
    if (!send_to_websocket($report_data)) {
        error_log("Failed to send WebSocket message, but continuing execution");
    }

    // Send success response
    $response = ['status' => 'success', 'message' => 'Report submitted successfully'];
    ob_end_clean();
    echo json_encode($response);
    error_log("Response sent: " . json_encode($response));
} else {
    pg_query($dbconn, "ROLLBACK");
    error_log("Database insert failed: " . pg_last_error($dbconn));
    ob_end_clean();
    echo json_encode(['status' => 'error', 'message' => "Error inserting report: " . pg_last_error($dbconn)]);
}

pg_close($dbconn);
exit;
?>