<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

$input = json_decode(file_get_contents('php://input'), true);

try {
    $baseQuery = "
        SELECT 
            ac.*,
            TO_CHAR(ac.approval_date, 'YYYY-MM-DD HH24:MI:SS') as approval_date_formatted,
            CASE WHEN ac.picture IS NOT NULL 
                 THEN '/uploads/' || ac.picture 
                 ELSE NULL 
            END as picture_path,
            fr.status_updated_by as approved_by
        FROM approved_cases ac
        JOIN found_reports fr ON ac.report_id = fr.report_id
    ";
    
    $whereClauses = [];
    $params = [];
    $paramCount = 1;
    
    // Add filters if provided
    if (!empty($input['search_name'])) {
        $whereClauses[] = "(ac.person_first_name ILIKE $$paramCount OR ac.person_last_name ILIKE $$paramCount)";
        $params[] = '%' . $input['search_name'] . '%';
        $paramCount++;
    }
    
    if (!empty($input['date_from'])) {
        $whereClauses[] = "ac.event_day >= $$paramCount";
        $params[] = $input['date_from'];
        $paramCount++;
    }
    
    if (!empty($input['date_to'])) {
        $whereClauses[] = "ac.event_day <= $$paramCount";
        $params[] = $input['date_to'];
        $paramCount++;
    }
    
    if (!empty($input['location'])) {
        $whereClauses[] = "ac.location ILIKE $$paramCount";
        $params[] = '%' . $input['location'] . '%';
        $paramCount++;
    }
    
    // Combine query
    $query = $baseQuery;
    if (!empty($whereClauses)) {
        $query .= " WHERE " . implode(" AND ", $whereClauses);
    }
    $query .= " ORDER BY ac.approval_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $cases = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'cases' => $cases,
        'count' => count($cases),
        'query' => $query // For debugging, remove in production
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>