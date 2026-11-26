<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Get input from GET or POST
    $input = $_GET; // Using GET for easier testing
    
    // Initialize query
    $query = "SELECT 
                report_id,
                missing_first_name,
                missing_middle_name,
                missing_last_name,
                missing_age,
                missing_gender,
                last_seen_location,
                lost_day AS report_date,
                picture AS photo_filename,
                status,
                physical_appearance AS description
              FROM missing_reports 
              WHERE 1=1";
    
    $params = [];
    
    // Add filters if provided
    if (!empty($input['name'])) {
        $query .= " AND (missing_first_name ILIKE ? OR missing_last_name ILIKE ?)";
        $searchTerm = "%" . $input['name'] . "%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($input['status']) && $input['status'] !== 'all') {
        $query .= " AND status = ?";
        $params[] = $input['status'];
    }

    if (!empty($input['date'])) {
        $query .= " AND lost_day = ?";
        $params[] = $input['date'];
    }

    $query .= " ORDER BY lost_day DESC, report_date DESC LIMIT 100";

    // Prepare and execute statement
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Base paths - IMPORTANT: Verify these match your setup
    $baseUrl = 'http://localhost/missing-persons-reporting-system/';
    $uploadPath = 'http://localhost/missing-persons-reporting-system/';
    
    // Debug information
    $debugInfo = [
        'base_url' => $baseUrl,
        'upload_path' => $uploadPath,
        'file_checks' => []
    ];
    
    // Format response
    foreach ($reports as &$report) {
        $filename = $report['photo_filename'] ?? null;
        $debugInfo['file_checks'][$filename] = false;
        
        if (!empty($filename)) {
            $safeFilename = basename($filename);
            $filePath = $uploadPath . $safeFilename;
            
            // Debug file check
            $debugInfo['file_checks'][$filename] = [
                'safe_filename' => $safeFilename,
                'full_path' => $filePath,
                'exists' => file_exists($filePath),
                'readable' => is_readable($filePath)
            ];
            
            if (file_exists($filePath) && is_readable($filePath)) {
                $report['photo_url'] = $baseUrl . 'uploads/' . rawurlencode($safeFilename);
                continue;
            }
        }
        
        // Fallback to default image
        $report['photo_url'] = $baseUrl . 'assets/default-profile.jpg';
        unset($report['photo_filename']);
    }

    // Successful response with debug info
    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'count' => count($reports),
        '_debug' => $debugInfo // Debug information
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Application error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>