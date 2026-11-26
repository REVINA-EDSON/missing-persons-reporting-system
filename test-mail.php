<?php
$to = "eliawalles56@gmail.com";
$subject = "Password Reset";
$message = "Click here to reset: http://localhost/reset.php?token=abc123";
$headers = "From: your-email@gmail.com";

if(mail($to, $subject, $message, $headers)){
    echo "Mail sent successfully!";
} else {
    echo "Mail sending failed!";
}
?>
