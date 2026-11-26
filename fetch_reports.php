<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

try {
    // Validate page input
    $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT, [
        'options' => [
            'default' => 1,
            'min_range' => 1
        ]
    ]) : 1;

    $limit = 5;
    $offset = ($page - 1) * $limit;

    // Modified query to sort by report_date only (newest first)
    $stmt = $pdo->prepare("
        SELECT 
            report_id,
            type,
            person_first_name,
            person_middle_name,
            person_last_name,
            person_age,
            person_gender,
            location,
            physical_appearance,
            event_day,
            picture,
            report_date
        FROM approved_cases 
        ORDER BY report_date DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total records
    $countStmt = $pdo->query("SELECT COUNT(*) FROM approved_cases");
    $totalReports = (int)$countStmt->fetchColumn();

    // Process each report
    foreach ($reports as &$report) {
        // Format date
        $reportDate = new DateTime($report['report_date']);
        $report['formatted_date'] = $reportDate->format('F j, Y');
        
        // Set image path
        $report['picture_path'] = !empty($report['picture']) 
            ? '/missing-persons-reporting-system/uploads/' . basename($report['picture'])
            : '/missing-persons-reporting-system/assets/default-profile.jpg';
        
        // Set status properties
        $report['status_class'] = ($report['type'] === 'missing') ? 'status-missing' : 'status-found';
        $report['status_text'] = ucfirst($report['type']);
    }

    // Prepare response
    echo json_encode([
        'success' => true,
        'data' => [
            'reports' => $reports,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalReports / $limit),
                'total_items' => $totalReports
            ]
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>