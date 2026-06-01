<?php
// =========================================================================
// FILE NAME: email_service.php
// PURPOSE: Centralized Email Sending Service for Murang'a Dairy System
// =========================================================================

// You would typically use a library like PHPMailer for robust email sending.
// For this example, we'll use PHP's built-in mail() function, which requires
// your server to be configured for sending mail (e.g., via sendmail, postfix, or an SMTP relay).
// For more advanced features (SMTP authentication, HTML emails, attachments),
// consider integrating PHPMailer: https://github.com/PHPMailer/PHPMailer

/**
 * Sends an email.
 *
 * @param string $to The recipient's email address.
 * @param string $subject The email subject.
 * @param string $message The email body (can be HTML).
 * @param string $from_name The sender's name.
 * @param string $from_email The sender's email address.
 * @return bool True on success, false on failure.
 */
function sendEmail($to, $subject, $message, $from_name = 'Muranga Dairy System', $from_email = 'no-reply@murangadairy.com') {
    // Basic headers for plain text email
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // For HTML emails, you would change Content-Type and format the message accordingly.
    // Example for HTML:
    /*
    $headers = "From: " . $from_name . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . $from_email . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message = "<html><body>" . $message . "</body></html>"; // Wrap message in HTML
    */

    // Attempt to send the email
    $mail_sent = mail($to, $subject, $message, $headers);

    if (!$mail_sent) {
        // Log the error for debugging purposes (e.g., to a file or error tracking service)
        error_log("Email sending failed to: $to, Subject: $subject. Check mail server configuration.");
    }

    return $mail_sent;
}

?>