<?php

namespace App\Services;

use App\Core\Config;
use SendGrid\Mail\Mail;

class MailService
{
    /**
     * Send an email using SendGrid.
     *
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body content
     * @param string $type Content type (text/plain or text/html)
     * @return bool True on success, false on failure
     */
    public static function send(string $to, string $subject, string $body, string $type = 'text/plain'): bool
    {
        $apiKey = Config::get('SENDGRID_API_KEY');
        $fromEmail = Config::get('MAIL_FROM_ADDRESS');
        $fromName = Config::get('MAIL_FROM_NAME', 'Sinapp Social');

        if (empty($apiKey) || empty($fromEmail)) {
            error_log("WARNING: SendGrid API Key or From Address is missing. Email to {$to} not sent.");
            return false;
        }

        // WARNING: Logging email dispatch as requested using error_log for visibility
        error_log("Attempting to send email to {$to} with subject: {$subject}");

        $email = new Mail();
        $email->setFrom($fromEmail, $fromName);
        $email->setSubject($subject);
        $email->addTo($to);
        $email->addContent($type, $body);

        $sendgrid = new \SendGrid($apiKey);

        try {
            $response = $sendgrid->send($email);
            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                return true;
            } else {
                error_log("SendGrid Error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            error_log("SendGrid Exception: " . $e->getMessage());
            return false;
        }
    }
}
