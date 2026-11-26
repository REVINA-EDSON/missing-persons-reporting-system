<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($data['username']) || empty($data['password'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Both username and password are required.'
    ]);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = :username");
    $stmt->execute([':username' => $data['username']]);
    $admin = $stmt->fetch();

    if (!$admin) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ]);
        exit;
    }

    // Verify password (plain text comparison - INSECURE)
    if ($data['password'] === $admin['password']) {
        // Login successful
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful! Redirecting...'
        ]);
    } else {
        // Invalid password
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid username or password.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during login. Please try again.'
    ]);
}
?>