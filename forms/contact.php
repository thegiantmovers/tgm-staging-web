<?php
/**
 * PHP Email Sending Script for The Giant Movers Contact Form
 *
 * This script processes the form submission from contact.html,
 * formats the data, and sends it as an email via SMTP using PHPMailer.
 * It is designed to be compatible with BootstrapMade's validate.js.
 */

// Adjust the path to PHPMailer's autoloader based on your installation method.
// Assuming your project root is one level up from 'forms'
require dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables from .env file
// Assuming .env is in the project root (one level up from 'forms')
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Define your recipient email address from .env (can be the same as for quotes or different)
$receiving_email_address = $_ENV['RECEIVING_EMAIL']; 
// You might want a separate contact email in .env, e.g., $_ENV['CONTACT_EMAIL']

// --- Process Form Data ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);

    // Basic validation
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($subject) || empty($message)) {
        echo "Please fill all required fields correctly.";
        exit;
    }

    // --- Prepare Email Content ---
    $email_subject = "Website Contact Form: " . $subject;

    $email_body_text = "Name: " . $name . "\n";
    $email_body_text .= "Email: " . $email . "\n";
    $email_body_text .= "Subject: " . $subject . "\n\n";
    $email_body_text .= "Message:\n" . $message . "\n";

    // HTML version of the email body for better formatting
    $email_body_html = "
        <p>Dear The Giant Movers Team,</p>
        <p>You have received a new message from your website contact form:</p>
        <table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse: collapse;'>
            <tr><td style='background-color:#f2f2f2;'><strong>Name:</strong></td><td>" . htmlspecialchars($name) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Email:</strong></td><td>" . htmlspecialchars($email) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Subject:</strong></td><td>" . htmlspecialchars($subject) . "</td></tr>
            <tr><td style='background-color:#e0e0e0;'><strong>Message:</strong></td><td>" . nl2br(htmlspecialchars($message)) . "</td></tr>
        </table>
        <p>Best regards,<br>Website Visitor</p>
    ";


    // --- PHPMailer Configuration ---
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST']; 
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME']; 
        $mail->Password   = $_ENV['SMTP_PASSWORD']; 
        
        // Determine SMTPSecure based on .env value
        if ($_ENV['SMTP_SECURE'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($_ENV['SMTP_SECURE'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false;
        }

        $mail->Port       = $_ENV['SMTP_PORT'];              

        // Recipients
        $mail->setFrom($_ENV['SENDER_EMAIL'], $_ENV['SENDER_NAME']); 
        $mail->addAddress($receiving_email_address); // Add recipient
        $mail->addReplyTo($email, $name); // Reply to the client's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = $email_subject;
        $mail->Body    = $email_body_html;
        $mail->AltBody = $email_body_text;

        $mail->send();
        
        // Success: Redirect to the thank you page
        header('Location: ../thank-you.html');
        exit;

    } catch (Exception $e) {
        // Failure: Send error message for validate.js
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        http_response_code(500); // Set HTTP status code to 500 for error
        echo "Message could not be sent. Please try again later. Mailer Error: {$mail->ErrorInfo}";
        exit;
    }
} else {
    // Not a POST request, redirect or show an error
    http_response_code(405);
    echo "Invalid request method.";
    exit;
}
?>
