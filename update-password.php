<?php
// update-password.php
header('Content-Type: application/json');

// Database connection parameters
$host = 'localhost';
$db = 'missing_persons_db';
$user = 'postgres';
$pass = 'postgres777';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);

    // Get the JSON data from the request
    $data = json_decode(file_get_contents("php://input"), true);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $token = $data['token'];

    // Validate token and get the associated email
    $stmt = $pdo->prepare("SELECT email FROM password_resets WHERE token = :token LIMIT 1");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
        exit;
    }

    $email = $resetRequest['email'];

    // Update the user's password
    $updateStmt = $pdo->prepare("UPDATE admins SET password = :password WHERE email = :email");
    $updateStmt->bindParam(':password', $password);
    $updateStmt->bindParam(':email', $email);

    if ($updateStmt->execute()) {
        // Optionally, delete the reset request from the table
        $deleteStmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $deleteStmt->bindParam(':token', $token);
        $deleteStmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Password has been reset successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to reset the password.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>