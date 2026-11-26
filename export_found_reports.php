<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

// Validate export format
$allowedFormats = ['csv', 'excel', 'pdf'];
if (empty($input['format']) || !in_array($input['format'], $allowedFormats)) {
    echo json_encode(['success' => false, 'error' => 'Invalid export format']);
    exit;
}

// Build query with filters
$query = "SELECT * FROM found_reports WHERE 1=1";
$params = [];
$paramCount = 1;

if (!empty($input['name'])) {
    $query .= " AND (found_first_name ILIKE $$paramCount OR found_last_name ILIKE $$paramCount)";
    $searchTerm = '%' . $input['name'] . '%';
    $params[] = $searchTerm;
    $paramCount++;
}

if (!empty($input['status']) && $input['status'] !== 'all') {
    $query .= " AND status = $$paramCount";
    $params[] = $input['status'];
    $paramCount++;
}

if (!empty($input['dateFrom'])) {
    $query .= " AND found_day >= $$paramCount";
    $params[] = $input['dateFrom'];
    $paramCount++;
}

if (!empty($input['dateTo'])) {
    $query .= " AND found_day <= $$paramCount";
    $params[] = $input['dateTo'];
    $paramCount++;
}

if (!empty($input['location'])) {
    $query .= " AND found_location ILIKE $$paramCount";
    $params[] = '%' . $input['location'] . '%';
    $paramCount++;
}

// Add sorting
$query .= " ORDER BY report_date DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
    
    // Generate export file based on format
    $filename = 'found_reports_export_' . date('Y-m-d');
    $filepath = 'exports/' . $filename;
    
    switch ($input['format']) {
        case 'csv':
            $filepath .= '.csv';
            exportToCSV($reports, $filepath);
            break;
            
        case 'excel':
            $filepath .= '.xlsx';
            exportToExcel($reports, $filepath);
            break;
            
        case 'pdf':
            $filepath .= '.pdf';
            exportToPDF($reports, $filepath);
            break;
    }
    
    echo json_encode([
        'success' => true,
        'file' => $filepath
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    
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

function exportToExcel($data, $filename) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    
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

function exportToPDF($data, $filename) {
    // Note: Requires TCPDF or similar library
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    
    // Simple PDF content for demonstration
    $pdf_content = "%PDF-1.4\n";
    $pdf_content .= "1 0 obj\n";
    $pdf_content .= "<< /Type /Catalog /Pages 2 0 R >>\n";
    $pdf_content .= "endobj\n";
    // ... more PDF content ...
    
    echo $pdf_content;
    exit;
}
?>