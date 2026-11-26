<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

session_start();
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['report_id']) || empty($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Report ID and status are required']);
    exit;
}

// Validate status
$allowedStatuses = ['approved', 'rejected'];
if (!in_array($input['status'], $allowedStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    
    // Get current status and full report details
    $currentStmt = $pdo->prepare("
        SELECT * FROM found_reports 
        WHERE report_id = $1 
        FOR UPDATE
    ");
    $currentStmt->execute([$input['report_id']]);
    $currentReport = $currentStmt->fetch();
    
    if (!$currentReport) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Report not found']);
        exit;
    }
    
    // Update status in found_reports
    $updateStmt = $pdo->prepare("
        UPDATE found_reports 
        SET status = $1, 
            status_updated_at = NOW(),
            status_updated_by = $2
        WHERE report_id = $3
        RETURNING *
    ");
    $changedBy = $_SESSION['username'] ?? 'system';
    $updateStmt->execute([
        $input['status'],
        $changedBy,
        $input['report_id']
    ]);
    $updatedReport = $updateStmt->fetch();
    
    // If approving, copy to approved_cases
    if ($input['status'] === 'approved') {
        $approveStmt = $pdo->prepare("
            INSERT INTO approved_cases (
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
            )
            SELECT 
                report_id, 'found',
                reporter_first_name, reporter_middle_name, reporter_last_name,
                reporter_age, reporter_gender,
                permanent_residence, current_residence,
                phone_number, relationship,
                found_first_name, found_middle_name, found_last_name,
                found_age, found_gender,
                found_location, physical_appearance,
                found_time, found_day,
                picture, additional_description,
                report_date
            FROM found_reports
            WHERE report_id = $1
            ON CONFLICT (report_id) DO NOTHING
        ");
        $approveStmt->execute([$input['report_id']]);
    }
    
    // Log to audit table
    $auditStmt = $pdo->prepare("
        INSERT INTO report_status_audit 
        (report_id, old_status, new_status, changed_by)
        VALUES ($1, $2, $3, $4)
    ");
    $auditStmt->execute([
        $input['report_id'],
        $currentReport['status'],
        $input['status'],
        $changedBy
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'report' => $updatedReport,
        'approved_case_created' => ($input['status'] === 'approved')
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $e->getTraceAsString()
    ]);
}
?>