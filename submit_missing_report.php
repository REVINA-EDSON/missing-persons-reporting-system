<?php
// submit_missing_report.php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    // Validate form type
    $formType = filter_input(INPUT_POST, 'form_type', FILTER_SANITIZE_STRING);
    if ($formType !== 'missing') {
        throw new Exception('Invalid form type');
    }

    // Get all form data
    $reporterData = [
        'first_name' => filter_input(INPUT_POST, 'reporter_first_name', FILTER_SANITIZE_STRING),
        'middle_name' => filter_input(INPUT_POST, 'reporter_middle_name', FILTER_SANITIZE_STRING),
        'last_name' => filter_input(INPUT_POST, 'reporter_last_name', FILTER_SANITIZE_STRING),
        'age' => filter_input(INPUT_POST, 'reporter_age', FILTER_SANITIZE_NUMBER_INT),
        'gender' => filter_input(INPUT_POST, 'reporter_gender', FILTER_SANITIZE_STRING),
        'permanent_residence' => filter_input(INPUT_POST, 'permanent_residence', FILTER_SANITIZE_STRING),
        'current_residence' => filter_input(INPUT_POST, 'current_residence', FILTER_SANITIZE_STRING),
        'phone_number' => filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING),
        'relationship' => filter_input(INPUT_POST, 'relationship', FILTER_SANITIZE_STRING)
    ];

    $missingPersonData = [
        'first_name' => filter_input(INPUT_POST, 'missing_first_name', FILTER_SANITIZE_STRING),
        'middle_name' => filter_input(INPUT_POST, 'missing_middle_name', FILTER_SANITIZE_STRING),
        'last_name' => filter_input(INPUT_POST, 'missing_last_name', FILTER_SANITIZE_STRING),
        'age' => filter_input(INPUT_POST, 'missing_age', FILTER_SANITIZE_NUMBER_INT),
        'gender' => filter_input(INPUT_POST, 'missing_gender', FILTER_SANITIZE_STRING),
        'last_seen_location' => filter_input(INPUT_POST, 'last_seen_location', FILTER_SANITIZE_STRING),
        'physical_appearance' => filter_input(INPUT_POST, 'physical_appearance', FILTER_SANITIZE_STRING),
        'lost_time' => filter_input(INPUT_POST, 'lost_time', FILTER_SANITIZE_STRING),
        'lost_day' => filter_input(INPUT_POST, 'lost_day', FILTER_SANITIZE_STRING),
        'additional_description' => filter_input(INPUT_POST, 'additional_description', FILTER_SANITIZE_STRING)
    ];

    // Handle file upload
    $picturePath = '';
    if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'D:/XAMMP/htdocs/missing-persons-reporting-system/uploads/';
        $fileExtension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
        $reportId = "MISS-" . date("YmdHis");
        $newFilename = $reportId . '.' . $fileExtension;
        $picturePath = $uploadDir . $newFilename;
        
        if (!move_uploaded_file($_FILES['picture']['tmp_name'], $picturePath)) {
            throw new Exception('Failed to upload image');
        }
    }

    // Insert into database
    $query = "INSERT INTO missing_reports (
        report_id, reporter_first_name, reporter_middle_name, reporter_last_name, 
        reporter_age, reporter_gender, permanent_residence, current_residence, 
        phone_number, relationship, missing_first_name, missing_middle_name, 
        missing_last_name, missing_age, missing_gender, last_seen_location, 
        physical_appearance, lost_time, lost_day, picture, additional_description
    ) VALUES (
        :report_id, :reporter_first_name, :reporter_middle_name, :reporter_last_name, 
        :reporter_age, :reporter_gender, :permanent_residence, :current_residence, 
        :phone_number, :relationship, :missing_first_name, :missing_middle_name, 
        :missing_last_name, :missing_age, :missing_gender, :last_seen_location, 
        :physical_appearance, :lost_time, :lost_day, :picture, :additional_description
    )";

    $stmt = $pdo->prepare($query);
    
    // Execute with all parameters
    $result = $stmt->execute([
        ':report_id' => $reportId,
        ':reporter_first_name' => $reporterData['first_name'],
        ':reporter_middle_name' => $reporterData['middle_name'],
        ':reporter_last_name' => $reporterData['last_name'],
        ':reporter_age' => $reporterData['age'],
        ':reporter_gender' => $reporterData['gender'],
        ':permanent_residence' => $reporterData['permanent_residence'],
        ':current_residence' => $reporterData['current_residence'],
        ':phone_number' => $reporterData['phone_number'],
        ':relationship' => $reporterData['relationship'],
        ':missing_first_name' => $missingPersonData['first_name'],
        ':missing_middle_name' => $missingPersonData['middle_name'],
        ':missing_last_name' => $missingPersonData['last_name'],
        ':missing_age' => $missingPersonData['age'],
        ':missing_gender' => $missingPersonData['gender'],
        ':last_seen_location' => $missingPersonData['last_seen_location'],
        ':physical_appearance' => $missingPersonData['physical_appearance'],
        ':lost_time' => $missingPersonData['lost_time'],
        ':lost_day' => $missingPersonData['lost_day'],
        ':picture' => $picturePath,
        ':additional_description' => $missingPersonData['additional_description']
    ]);

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Missing person report submitted successfully']);
    } else {
        throw new Exception('Failed to insert into database');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>