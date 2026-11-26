<?php
require_once 'db_connection.php';

header('Content-Type: application/json');

try {
    $searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
    
    if (empty($searchTerm)) {
        echo json_encode([]);
        exit;
    }

    // Prepare the SQL query with multiple search conditions
    $query = "SELECT * FROM approved_cases 
              WHERE person_first_name ILIKE :search 
                 OR person_last_name ILIKE :search 
                 OR location ILIKE :search 
                 OR physical_appearance ILIKE :search 
                 OR additional_description ILIKE :search
              ORDER BY report_date DESC
              LIMIT 10";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => "%$searchTerm%"]);
    
    $results = $stmt->fetchAll();
    
    // Format the results for display
 $formattedResults = array_map(function($row) {
    return [
        'id' => $row['report_id'],
        'name' => trim($row['person_first_name'] . ' ' . $row['person_last_name']),
        'age' => $row['person_age'],
        'gender' => $row['person_gender'],
        'location' => $row['location'],
        'date' => $row['event_day'],
        'description' => $row['physical_appearance'],
       'image' => (file_exists('uploads/' . basename($row['picture']))) 
    ? 'uploads/' . basename($row['picture']) 
    : 'uploads/default-person.jpg'
    ];
}, $results);


    
    echo json_encode($formattedResults);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}