<?php
// =====================
// RESEND EMAIL WRAPPER 
// =====================

/**
 * Send an HTML email via the Resend API.
 *
 * @param string $to      Recipient email address
 * @param string $toName  Recipient display name
 * @param string $subject Email subject line
 * @param string $html    HTML body
 * @return bool           true on success, false on failure
 */
function sendEmail(string $to, string $toName, string $subject, string $html): bool {
    $apiKey = getenv('RESEND_API_KEY') ?: '';
    if (!$apiKey) return false;

    // Use verified domain in production, sandbox in dev
    $fromEmail = getenv('MYSQLHOST')
        ? 'ANU Meal System <noreply@anu.ac.ke>'
        : 'ANU Meal System <onboarding@resend.dev>';

    $payload = json_encode([
        'from'    => $fromEmail,
        'to'      => ["{$toName} <{$to}>"],
        'subject' => $subject,
        'html'    => $html,
    ]);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Build a, HTML email body.
 */
function buildEmailHtml(string $title, string $bodyHtml): string {
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8">
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
  .wrap { max-width: 560px; margin: 30px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
  .header { background: linear-gradient(135deg, #cc0000, #fac823); padding: 24px 28px; color: #fff; }
  .header h2 { margin: 0; font-size: 20px; }
  .header p { margin: 4px 0 0; opacity: 0.85; font-size: 13px; }
  .body { padding: 24px 28px; color: #333; font-size: 15px; line-height: 1.6; }
  .footer { padding: 14px 28px; background: #f9f9f9; color: #999; font-size: 12px; border-top: 1px solid #eee; }
</style></head>
<body>
<div class="wrap">
  <div class="header">
    <h2>ANU Meal Booking System</h2>
    <p>Africa Nazarene University</p>
  </div>
  <div class="body">
    <strong style="font-size:17px;">$title</strong><br><br>
    $bodyHtml
  </div>
  <div class="footer">This is an automated message from the ANU Meal Booking System. Do not reply.</div>
</div>
</body></html>
HTML;
}
