<?php
/**
 * PHP Email Sending Script for The Giant Movers Quote Form using PHPMailer
 *
 * This script processes the form submission from get-a-quote.html,
 * formats the data, and sends it as an email via SMTP using PHPMailer.
 * It is designed to be compatible with BootstrapMade's validate.js.
 */

// IMPORTANT: Adjust the path to PHPMailer's autoloader based on your installation method.
// If you installed via Composer:
require dirname(__DIR__) . '/vendor/autoload.php'; // Use dirname(__DIR__) to go up one level from 'forms'
// If you downloaded manually and placed src in forms/PHPMailer/src/:
// require 'PHPMailer/src/PHPMailer.php';
// require 'PHPMailer/src/SMTP.php';
// require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__)); // Go up one level to find .env
$dotenv->load();

// Define your recipient email address from .env
$receiving_email_address = $_ENV['RECEIVING_EMAIL']; 

// --- Process Form Data ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate input
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);

    $departure_location = filter_var($_POST['departure_location'] ?? '', FILTER_SANITIZE_STRING);
    $delivery_location = filter_var($_POST['delivery_location'] ?? '', FILTER_SANITIZE_STRING);
    $preferred_move_date = filter_var($_POST['preferred_move_date'] ?? '', FILTER_SANITIZE_STRING);
    $type_of_property = filter_var($_POST['type_of_property'] ?? '', FILTER_SANITIZE_STRING);
    $number_of_bedrooms = isset($_POST['number_of_bedrooms']) ? filter_var($_POST['number_of_bedrooms'], FILTER_SANITIZE_STRING) : 'Not specified';
    $service_needed = filter_var($_POST['service_needed'] ?? '', FILTER_SANITIZE_STRING);
    $additional_message = filter_var($_POST['additional_message'] ?? '', FILTER_SANITIZE_STRING);

    // Basic validation (you can add more robust validation here)
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($phone)) {
        echo "Please fill all required contact fields correctly.";
        exit;
    }

    // --- Prepare Email Content ---
    $subject = "New Quote Request from Website: " . $name;

    $email_body_text = "Name: " . $name . "\n";
    $email_body_text .= "Email: " . $email . "\n";
    $email_body_text .= "Phone: " . $phone . "\n\n";

    $email_body_text .= "--- Moving Details ---\n";
    $email_body_text .= "Current Location: " . $departure_location . "\n";
    $email_body_text .= "Destination: " . $delivery_location . "\n";
    $email_body_text .= "Preferred Move Date: " . $preferred_move_date . "\n";
    $email_body_text .= "Type of Property: " . $type_of_property . "\n";
    $email_body_text .= "Number of Bedrooms: " . $number_of_bedrooms . "\n";
    $email_body_text .= "Service Needed: " . $service_needed . "\n\n";
    $email_body_text .= "Additional Message:\n" . $additional_message . "\n";

    // HTML version of the email body for better formatting
    $email_body_html = "
        <p>Dear The Giant Movers Team,</p>
        <p>You have received a new moving quote request from your website with the following details:</p>
        <table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse: collapse;'>
            <tr><td style='background-color:#f2f2f2;'><strong>Name:</strong></td><td>" . htmlspecialchars($name) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Email:</strong></td><td>" . htmlspecialchars($email) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Phone:</strong></td><td>" . htmlspecialchars($phone) . "</td></tr>
            <tr><td colspan='2' style='background-color:#e0e0e0; text-align:center;'><strong>Moving Details</strong></td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Current Location:</strong></td><td>" . htmlspecialchars($departure_location) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Destination:</strong></td><td>" . htmlspecialchars($delivery_location) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Preferred Move Date:</strong></td><td>" . htmlspecialchars($preferred_move_date) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Type of Property:</strong></td><td>" . htmlspecialchars($type_of_property) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Number of Bedrooms:</strong></td><td>" . htmlspecialchars($number_of_bedrooms) . "</td></tr>
            <tr><td style='background-color:#f2f2f2;'><strong>Service Needed:</strong></td><td>" . htmlspecialchars($service_needed) . "</td></tr>
            <tr><td style='background-color:#e0e0e0;'><strong>Additional Message:</strong></td><td>" . nl2br(htmlspecialchars($additional_message)) . "</td></tr>
        </table>
        <p>Best regards,<br>Website Visitor</p>
    ";


    // --- PHPMailer Configuration ---
    $mail = new PHPMailer(true); // Passing `true` enables exceptions

    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF; // Set to SMTP::DEBUG_SERVER for detailed debug output
        $mail->isSMTP();                     // Send using SMTP
        $mail->Host       = $_ENV['SMTP_HOST']; 
        $mail->SMTPAuth   = true;             // Enable SMTP authentication
        $mail->Username   = $_ENV['SMTP_USERNAME']; 
        $mail->Password   = $_ENV['SMTP_PASSWORD']; 
        
        // Determine SMTPSecure based on .env value
        if ($_ENV['SMTP_SECURE'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($_ENV['SMTP_SECURE'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = false; // No encryption
        }

        $mail->Port       = $_ENV['SMTP_PORT'];              

        // Recipients
        $mail->setFrom($_ENV['SENDER_EMAIL'], $_ENV['SENDER_NAME']); 
        $mail->addAddress($receiving_email_address); // Add recipient
        $mail->addReplyTo($email, $name); // Reply to the client's email

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $email_body_html;
        $mail->AltBody = $email_body_text; // Plain text for non-HTML mail clients

        $mail->send();
        echo 'OK'; // IMPORTANT: Send 'OK' string on success for validate.js
        exit;

    } catch (Exception $e) {
        // Failure: Send error message for validate.js
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}"); // Log the error for debugging
        echo "Message could not be sent. Please try again later. Mailer Error: {$mail->ErrorInfo}"; // Send error to client
        exit;
    }
} else {
    // Not a POST request, redirect or show an error
    echo "Invalid request method.";
    exit;
}
?>
