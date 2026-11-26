<?php
require_once 'db_connection.php';

// Get parameters
$format = $_GET['format'] ?? 'csv';
$searchName = $_GET['search_name'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

// Validate format
$allowedFormats = ['csv', 'excel', 'pdf'];
if (!in_array($format, $allowedFormats)) {
    http_response_code(400);
    die('Invalid export format');
}

try {
    $baseQuery = "
        SELECT 
            ac.report_id,
            ac.person_first_name || ' ' || ac.person_last_name as person_name,
            ac.person_age,
            ac.person_gender,
            ac.location,
            TO_CHAR(ac.event_day, 'YYYY-MM-DD') || ' ' || ac.event_time as event_datetime,
            TO_CHAR(ac.approval_date, 'YYYY-MM-DD HH24:MI:SS') as approval_date,
            fr.status_updated_by as approved_by
        FROM approved_cases ac
        JOIN found_reports fr ON ac.report_id = fr.report_id
    ";
    
    $whereClauses = [];
    $params = [];
    $paramCount = 1;
    
    if ($searchName) {
        $whereClauses[] = "(ac.person_first_name ILIKE $$paramCount OR ac.person_last_name ILIKE $$paramCount)";
        $params[] = '%' . $searchName . '%';
        $paramCount++;
    }
    
    if ($dateFrom) {
        $whereClauses[] = "ac.event_day >= $$paramCount";
        $params[] = $dateFrom;
        $paramCount++;
    }
    
    if ($dateTo) {
        $whereClauses[] = "ac.event_day <= $$paramCount";
        $params[] = $dateTo;
        $paramCount++;
    }
    
    if (!empty($whereClauses)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereClauses);
    }
    
    $baseQuery .= " ORDER BY ac.approval_date DESC";
    
    $stmt = $pdo->prepare($baseQuery);
    $stmt->execute($params);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate export
    switch ($format) {
        case 'csv':
            exportToCSV($cases);
            break;
        case 'excel':
            exportToExcel($cases);
            break;
        case 'pdf':
            default:
            exportToPDF($cases);
            break;
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}

function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="approved_cases_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, array_keys($data[0]));
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($data) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="approved_cases_' . date('Y-m-d') . '.xlsx"');
    
    // Simple tab-delimited format
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, array_keys($data[0]), "\t");
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row, "\t");
    }
    
    fclose($output);
    exit;
}

function exportToPDF($data) {
    // This is a placeholder - in production use a library like TCPDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="approved_cases_' . date('Y-m-d') . '.pdf"');
    
    $html = '<h1>Approved Cases Report</h1>';
    $html .= '<p>Generated on ' . date('Y-m-d H:i:s') . '</p>';
    $html .= '<table border="1"><tr>';
    
    // Headers
    foreach (array_keys($data[0]) as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    
    // Data
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . htmlspecialchars($value) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Simple PDF generation (in production use a proper PDF library)
    echo "%PDF-1.4\n";
    echo "1 0 obj\n";
    echo "<< /Type /Catalog /Pages 2 0 R >>\n";
    echo "endobj\n";
    // ... more PDF content ...
    
    exit;
}
?>