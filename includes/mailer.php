<?php
/**
 * Minimal dependency-free SMTP client (no Composer/PHPMailer available on
 * shared hosting). Built for Gmail SMTP (smtp.gmail.com:587, STARTTLS) but
 * works with any standard SMTP+AUTH LOGIN server.
 *
 * All send functions are best-effort: they log failures via error_log and
 * return false rather than throwing, so a broken mail config never breaks
 * a booking/enquiry submission or a password reset.
 */

function smtp_is_configured(): bool
{
    $s = get_settings();
    return !empty($s['smtp_host']) && !empty($s['smtp_username']) && !empty($s['smtp_password']) && !empty($s['smtp_from_email']);
}

/** Low-level: send one email over SMTP. Returns true on success. */
function smtp_send(string $toEmail, string $toName, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    $s = get_settings();
    if (!smtp_is_configured()) {
        error_log('smtp_send skipped: SMTP not configured in Settings.');
        return false;
    }

    $host = $s['smtp_host'];
    $port = (int) ($s['smtp_port'] ?: 587);
    $username = $s['smtp_username'];
    $password = $s['smtp_password'];
    $fromEmail = $s['smtp_from_email'];
    $fromName = $s['smtp_from_name'] ?: APP_NAME;

    $errno = 0;
    $errstr = '';
    $useImplicitTls = $port === 465;
    $address = ($useImplicitTls ? 'ssl://' : '') . $host;
    $sock = @stream_socket_client("{$address}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
    if (!$sock) {
        error_log("SMTP connect failed to {$host}:{$port} — {$errstr} ({$errno})");
        return false;
    }
    stream_set_timeout($sock, 15);

    try {
        smtp_expect($sock, '220');
        smtp_command($sock, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');

        if (!$useImplicitTls) {
            smtp_command($sock, 'STARTTLS', '220');
            if (!stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('STARTTLS negotiation failed');
            }
            smtp_command($sock, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), '250');
        }

        smtp_command($sock, 'AUTH LOGIN', '334');
        smtp_command($sock, base64_encode($username), '334');
        smtp_command($sock, base64_encode($password), '235');

        smtp_command($sock, 'MAIL FROM:<' . $fromEmail . '>', '250');
        smtp_command($sock, 'RCPT TO:<' . $toEmail . '>', ['250', '251']);
        smtp_command($sock, 'DATA', '354');

        $boundary = bin2hex(random_bytes(12));
        $headers = [
            'From: ' . smtp_encode_header($fromName) . ' <' . $fromEmail . '>',
            'To: ' . smtp_encode_header($toName) . ' <' . $toEmail . '>',
            'Subject: ' . smtp_encode_header($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . date('r'),
        ];
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $data = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $htmlBody) . "\r\n.";
        smtp_command($sock, $data, '250');
        smtp_command($sock, 'QUIT', '221');
        fclose($sock);
        return true;
    } catch (\Throwable $e) {
        error_log('SMTP send failed: ' . $e->getMessage());
        if (is_resource($sock)) fclose($sock);
        return false;
    }
}

function smtp_encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

/** @param string|string[] $expectedCode */
function smtp_command($sock, string $command, $expectedCode): string
{
    fwrite($sock, $command . "\r\n");
    return smtp_expect($sock, $expectedCode);
}

/** @param string|string[] $expectedCode */
function smtp_expect($sock, $expectedCode): string
{
    $expected = (array) $expectedCode;
    $response = '';
    while (($line = fgets($sock, 515)) !== false) {
        $response .= $line;
        // Multi-line replies use "250-text"; the final line uses "250 text".
        if (strlen($line) < 4 || $line[3] === ' ') break;
    }
    $code = substr($response, 0, 3);
    if (!in_array($code, $expected, true)) {
        throw new RuntimeException("Unexpected SMTP response (wanted " . implode('/', $expected) . "): {$response}");
    }
    return $response;
}

/**
 * Wraps inner message HTML in a branded, table-based, mobile-responsive email shell
 * (purple Hotel Pallav header, white content card, footer) — table markup and inline
 * styles throughout for email-client compatibility, no external image dependency.
 */
function email_shell(string $heading, string $bodyHtml): string
{
    $s = get_settings();
    $phone = e(phone_display($s['reception_phone'] ?? $s['gm_phone'] ?? ''));
    $address = trim((string) ($s['address'] ?? '')) !== '' ? ' &middot; ' . e($s['address']) : '';
    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$heading}</title>
<style>
  @media only screen and (max-width: 600px) {
    .ep-wrap { width: 100% !important; }
    .ep-pad { padding-left: 20px !important; padding-right: 20px !important; }
    .ep-heading { font-size: 22px !important; line-height: 28px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:#F5F2FC;-webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5F2FC;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" class="ep-wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#FFFFFF;border-radius:18px;overflow:hidden;border:1px solid #E9E2FA;">

<tr><td style="background:#5B21B6;padding:26px 24px;text-align:center;">
  <div style="font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#FFFFFF;letter-spacing:.5px;">Hotel Pallav</div>
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:bold;letter-spacing:2px;color:#DDD3FA;text-transform:uppercase;margin-top:4px;">Rajkot &middot; Since Generations</div>
</td></tr>

<tr><td class="ep-pad" style="padding:32px 40px 8px;">
  <div class="ep-heading" style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:32px;font-weight:bold;color:#4A1A8F;text-align:center;padding-bottom:20px;">{$heading}</div>
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#4A4262;">
{$bodyHtml}
  </div>
</td></tr>

<tr><td class="ep-pad" style="padding:8px 40px 32px;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F4FF;border-radius:12px;border-left:3px solid #8B5CF6;">
    <tr><td style="padding:16px 18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#7A7392;">
      Need help? Call us on <b style="color:#5B21B6;">{$phone}</b>{$address}
    </td></tr>
  </table>
</td></tr>

<tr><td style="background-color:#FBF9FF;padding:20px 24px;text-align:center;border-top:1px solid #EFE9FE;">
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;color:#A79FC7;">&copy; {$year} Hotel Pallav. All rights reserved.</div>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

/** One of the 2x2 "For Your Security" feature cards on the password-reset email. */
function password_reset_security_card(string $emoji, string $title, string $text): string
{
    return '<td width="50%" valign="top" style="width:50%;padding:0 6px 12px 0;box-sizing:border-box;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#FFFFFF;border:1px solid #E9E2FA;border-radius:12px;">'
        . '<tr><td align="center" style="padding:16px 12px;">'
        . '<div style="font-size:24px;line-height:28px;padding-bottom:4px;">' . $emoji . '</div>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;color:#5B21B6;padding-bottom:4px;">' . e($title) . '</div>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#7A7392;">' . e($text) . '</div>'
        . '</td></tr></table></td>';
}

/** Password reset link — sent to the admin whose account it is. */
function mail_password_reset(string $toEmail, string $toName, string $resetLink): bool
{
    $firstName = e(explode(' ', $toName)[0] ?: 'there');

    $body = '<p>Dear ' . $firstName . ',</p>'
        . '<p>We received a request to reset the password for your <b>' . e(APP_NAME) . '</b> admin account.</p>'
        . '<p>Click the button below to choose a new password. This link is valid for <b>10 minutes</b>.</p>'

        // Reset button card
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;background:#5B21B6;border-radius:14px;">'
        . '<tr><td align="center" style="padding:22px 20px;">'
        . '<a href="' . e($resetLink) . '" style="display:inline-block;background:#FFFFFF;color:#5B21B6;font-family:Arial,Helvetica,sans-serif;font-weight:bold;font-size:15px;text-decoration:none;padding:14px 34px;border-radius:10px;">Reset Your Password</a>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:bold;letter-spacing:1px;color:#E4DEFA;text-transform:uppercase;padding-top:14px;">Valid for 10 Minutes</div>'
        . '</td></tr></table>'

        // Security notice
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#F7F4FF;border-left:3px solid #8B5CF6;border-radius:10px;margin-bottom:22px;">'
        . '<tr><td style="padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:19px;color:#7A7392;">'
        . '<b style="color:#4A1A8F;">Security notice:</b> if you did not request this, you can safely ignore this email, your account will remain secure and no changes will be made.'
        . '</td></tr></table>'

        // "For Your Security" heading
        . '<div style="font-family:Georgia,\'Times New Roman\',serif;font-size:20px;font-weight:bold;color:#4A1A8F;text-align:center;padding-bottom:4px;">For Your Security</div>'
        . '<p style="text-align:center;font-size:13px;color:#7A7392;padding-bottom:14px;">A few reminders to keep your account safe.</p>'

        // 2x2 security cards
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
        . password_reset_security_card('🔒', 'Keep It Private', 'Never share this reset link with anyone, treat it like a password.')
        . password_reset_security_card('⏳', 'Limited Time', 'This link expires in 10 minutes. Request a new one if it lapses.')
        . '</tr><tr>'
        . password_reset_security_card('✔️', 'One-Time Use', 'The link stops working the moment your password is changed.')
        . password_reset_security_card('🛡️', 'Stay Protected', 'Choose a password you don\'t use anywhere else.')
        . '</tr></table>';

    $html = email_shell('Reset Your Password', $body);
    return smtp_send($toEmail, $toName ?: 'Admin', APP_NAME . ' - Forgot Password', $html);
}

/** Built-in fallback content for each editable email template — used whenever a row hasn't been customized yet. */
const EMAIL_TEMPLATE_DEFAULTS = [
    'booking_received' => [
        'subject' => 'Your Booking Request - {{reference}}',
        'body' => "<p>Hi {{guest_name}},</p>\n<p>Thank you for your booking request at {{hotel_name}}. Here are the details:</p>\n<ul>\n<li><b>Reference:</b> {{reference}}</li>\n<li><b>Room:</b> {{room_name}}</li>\n<li><b>Check-in:</b> {{check_in}}</li>\n<li><b>Check-out:</b> {{check_out}}</li>\n<li><b>Guests:</b> {{guests}}</li>\n</ul>\n<p>This is a <b>request</b>, not a confirmed booking yet, our team will call you shortly on {{guest_phone}} to confirm availability and finalize your stay.</p>\n<p>Any questions, call us on {{reception_phone}}.</p>",
    ],
    'booking_approved' => [
        'subject' => 'Your Booking is Confirmed - {{reference}}',
        'body' => "<p>Hi {{guest_name}},</p>\n<p>Great news, your booking request has been <b>confirmed</b>!</p>\n<ul>\n<li><b>Reference:</b> {{reference}}</li>\n<li><b>Room:</b> {{room_name}}</li>\n<li><b>Check-in:</b> {{check_in}}</li>\n<li><b>Check-out:</b> {{check_out}}</li>\n<li><b>Guests:</b> {{guests}}</li>\n</ul>\n<p>We look forward to welcoming you at {{hotel_name}}. Call us on {{reception_phone}} if you need anything before your stay.</p>",
    ],
    'booking_declined' => [
        'subject' => 'About Your Booking Request - {{reference}}',
        'body' => "<p>Hi {{guest_name}},</p>\n<p>We're sorry, we're unable to confirm your booking request ({{reference}}) for {{check_in}} to {{check_out}}.</p>\n<p>{{decision_note}}</p>\n<p>Please call us on {{reception_phone}} and our team will help you find alternative dates or options.</p>",
    ],
    'enquiry_received' => [
        'subject' => "We've received your enquiry",
        'body' => "<p>Hi {{guest_name}},</p>\n<p>Thank you for reaching out to {{hotel_name}}. We've received your message and our team will call you back shortly.</p>\n<p><b>Your message:</b> {{message}}</p>\n<p>Any urgent questions, call us on {{reception_phone}}.</p>",
    ],
    'enquiry_confirmed' => [
        'subject' => 'Following up on your enquiry',
        'body' => "<p>Hi {{guest_name}},</p>\n<p>Thanks for your patience, we're happy to confirm the details of your enquiry.</p>\n<p>{{message}}</p>\n<p>Call us on {{reception_phone}} for anything else.</p>",
    ],
    'enquiry_declined' => [
        'subject' => 'About your enquiry',
        'body' => "<p>Hi {{guest_name}},</p>\n<p>Thank you for your interest in {{hotel_name}}. Unfortunately we're unable to help with this particular request.</p>\n<p>Please call us on {{reception_phone}} if you'd like to discuss other options.</p>",
    ],
];

/** Human-readable labels, shown on the Email Templates admin page. */
const EMAIL_TEMPLATE_LABELS = [
    'booking_received' => 'Booking Received',
    'booking_approved' => 'Booking Approved',
    'booking_declined' => 'Booking Declined',
    'enquiry_received' => 'Enquiry Received',
    'enquiry_confirmed' => 'Enquiry Confirmed',
    'enquiry_declined' => 'Enquiry Declined',
];

/** The saved (or default, if never customized) subject/body for one template key. */
function email_template(string $key): array
{
    $row = db_one('SELECT subject, body FROM email_templates WHERE template_key = ?', [$key]);
    if ($row) return $row;
    return EMAIL_TEMPLATE_DEFAULTS[$key] ?? ['subject' => '', 'body' => ''];
}

/** Substitutes {{token}} placeholders in a template's subject/body with the given values. */
function render_email_template(string $key, array $vars): array
{
    $t = email_template($key);
    $search = array_map(fn ($k) => '{{' . $k . '}}', array_keys($vars));
    $replace = array_values($vars);
    return [
        'subject' => str_replace($search, $replace, $t['subject']),
        'body' => str_replace($search, $replace, $t['body']),
    ];
}

/**
 * Sends a rendered template to the guest (if an email was given) and always sends a copy
 * to the hotel's notify address from Settings, so every guest-facing email is also seen
 * by the team. $ownerExtraHtml (e.g. an admin-panel link) is appended only to that copy.
 */
function send_templated_mail(string $key, string $toEmail, string $toName, array $vars, ?string $ownerExtraHtml = null): bool
{
    $rendered = render_email_template($key, $vars);
    $heading = EMAIL_TEMPLATE_LABELS[$key] ?? APP_NAME;
    $subject = APP_NAME . ' - ' . $rendered['subject'];
    $html = email_shell($heading, $rendered['body']);
    $sent = $toEmail !== '' ? smtp_send($toEmail, $toName ?: 'Guest', $subject, $html) : true;

    $s = get_settings();
    $notifyTo = $s['notify_email'] ?? $s['email'] ?? null;
    if ($notifyTo) {
        $ownerBody = email_shell($heading, $rendered['body'] . ($ownerExtraHtml ?? ''));
        smtp_send($notifyTo, APP_NAME . ' Admin', $subject, $ownerBody, $toEmail !== '' ? $toEmail : null);
    }
    return $sent;
}

/** Placeholder values available to the booking_* templates. */
function booking_email_vars(array $booking, ?array $room = null): array
{
    $s = get_settings();
    return [
        'guest_name' => e($booking['guest_name']),
        'reference' => e($booking['reference']),
        'room_name' => e($room['name'] ?? 'a room'),
        'check_in' => date('d M Y', strtotime($booking['check_in'])),
        'check_out' => date('d M Y', strtotime($booking['check_out'])),
        'guests' => e((string) $booking['guests']),
        'guest_phone' => e(phone_display($booking['guest_phone'] ?? '')),
        'message' => nl2br(e($booking['message'] ?? '')),
        'decision_note' => $booking['decision_note'] ? e($booking['decision_note']) : '',
        'hotel_name' => e(APP_NAME),
        'reception_phone' => e(phone_display($s['reception_phone'] ?? '')),
    ];
}

/** Placeholder values available to the enquiry_* templates. */
function enquiry_email_vars(array $enquiry): array
{
    $s = get_settings();
    return [
        'guest_name' => e($enquiry['name']),
        'message' => nl2br(e($enquiry['message'])),
        'hotel_name' => e(APP_NAME),
        'reception_phone' => e(phone_display($s['reception_phone'] ?? '')),
    ];
}
