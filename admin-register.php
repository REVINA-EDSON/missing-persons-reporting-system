<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$errors = [];

if (empty($data['username'])) {
    $errors[] = "Username is required.";
} elseif (!preg_match('/^[a-zA-Z0-9]{3,50}$/', $data['username'])) {
    $errors[] = "Username must be 3-50 characters and alphanumeric.";
}

if (empty($data['email'])) {
    $errors[] = "Email is required.";
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

if (empty($data['password'])) {
    $errors[] = "Password is required.";
} elseif (strlen($data['password']) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
}

if ($data['password'] !== $data['confirmPassword']) {
    $errors[] = "Passwords do not match.";
}

if (empty($data['terms'])) {
    $errors[] = "You must agree to the terms.";
}

if (!empty($errors)) {
    echo json_encode([
        'status' => 'error',
        'message' => implode("\n", $errors)
    ]);
    exit;
}

try {
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE username = :username OR email = :email");
    $stmt->execute([
        ':username' => $data['username'],
        ':email' => $data['email']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Username or email already exists.'
        ]);
        exit;
    }

    // Insert new admin with plain text password (INSECURE)
    $stmt = $pdo->prepare("
        INSERT INTO admins (username, email, password) 
        VALUES (:username, :email, :password)
    ");
    
    $stmt->execute([
        ':username' => $data['username'],
        ':email' => $data['email'],
        ':password' => $data['password']  // Storing plain text password
    ]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Admin registered successfully! You can now login.'
    ]);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'An error occurred during registration. Please try again.'
    ]);
}
?>