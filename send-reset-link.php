<?php
// send-reset-link.php
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
    $email = $data['email'];

    // Validate email
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with that email address.']);
        exit;
    }

    // Generate a password reset token (here, just a random string)
    $token = bin2hex(random_bytes(16)); // You can store this token in your database for verification later
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token) VALUES (:email, :token)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    // Create reset link
    $resetLink = "http://localhost/missing-persons-reporting-system/reset-password.php?token=$token";

    // Send the email (make sure to configure your mailer properly)
    $subject = "Password Reset Request";
    $message = "You requested a password reset. Click the link below to reset your password:\n$resetLink";
    $headers = "From: no-reply@yourwebsite.com\r\n";
    
    if (mail($email, $subject, $message, $headers)) {
        echo json_encode(['status' => 'success', 'message' => 'A reset link has been sent to your email address.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to send reset email.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>