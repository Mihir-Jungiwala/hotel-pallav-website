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

/** Password reset link — sent to the admin whose account it is. */
function mail_password_reset(string $toEmail, string $resetLink): bool
{
    $html = '<p>Someone requested a password reset for the ' . e(APP_NAME) . ' admin panel.</p>'
        . '<p><a href="' . e($resetLink) . '">Click here to reset your password</a> (valid for 1 hour).</p>'
        . '<p>If you did not request this, you can ignore this email.</p>';
    return smtp_send($toEmail, 'Admin', 'Reset your ' . APP_NAME . ' admin password', $html);
}

/** Booking confirmation-of-receipt — sent to the guest, if they gave an email. */
function mail_booking_guest(array $booking, ?array $room = null): bool
{
    if (empty($booking['guest_email'])) return true;
    $s = get_settings();
    $roomName = $room['name'] ?? 'a room';
    $html = '<p>Hi ' . e($booking['guest_name']) . ',</p>'
        . '<p>Thank you for your booking request at ' . e(APP_NAME) . '. Here are the details:</p>'
        . '<ul>'
        . '<li><b>Reference:</b> ' . e($booking['reference']) . '</li>'
        . '<li><b>Room:</b> ' . e($roomName) . '</li>'
        . '<li><b>Check-in:</b> ' . e($booking['check_in']) . '</li>'
        . '<li><b>Check-out:</b> ' . e($booking['check_out']) . '</li>'
        . '<li><b>Guests:</b> ' . e((string) $booking['guests']) . '</li>'
        . '</ul>'
        . '<p>This is a <b>request</b>, not a confirmed booking yet — our team will call you shortly on '
        . e(phone_display($booking['guest_phone'])) . ' to confirm availability and finalize your stay.</p>'
        . '<p>Any questions, call us on ' . e(phone_display($s['reception_phone'] ?? '')) . '.</p>';
    return smtp_send($booking['guest_email'], $booking['guest_name'], 'Your booking request — ' . $booking['reference'], $html);
}

/** New-booking notification — sent to the hotel's own inbox. */
function mail_booking_owner(array $booking, ?array $room = null): bool
{
    $s = get_settings();
    $notifyTo = $s['notify_email'] ?? $s['email'] ?? null;
    if (!$notifyTo) return true;
    $roomName = $room['name'] ?? 'Unspecified';
    $html = '<p>New booking request received.</p>'
        . '<ul>'
        . '<li><b>Reference:</b> ' . e($booking['reference']) . '</li>'
        . '<li><b>Guest:</b> ' . e($booking['guest_name']) . '</li>'
        . '<li><b>Phone:</b> ' . e(phone_display($booking['guest_phone'])) . '</li>'
        . '<li><b>Email:</b> ' . e($booking['guest_email'] ?? '—') . '</li>'
        . '<li><b>Room:</b> ' . e($roomName) . '</li>'
        . '<li><b>Check-in:</b> ' . e($booking['check_in']) . '</li>'
        . '<li><b>Check-out:</b> ' . e($booking['check_out']) . '</li>'
        . '<li><b>Guests:</b> ' . e((string) $booking['guests']) . '</li>'
        . '<li><b>Message:</b> ' . e($booking['message'] ?? '—') . '</li>'
        . '</ul>'
        . '<p><a href="' . e(APP_URL) . '/admin/bookings.php">Open in admin panel</a></p>';
    return smtp_send($notifyTo, APP_NAME . ' Admin', 'New booking request — ' . $booking['reference'], $html, $booking['guest_email'] ?? null);
}

/** New-enquiry notification — sent to the hotel's own inbox. */
function mail_enquiry_owner(array $enquiry): bool
{
    $s = get_settings();
    $notifyTo = $s['notify_email'] ?? $s['email'] ?? null;
    if (!$notifyTo) return true;
    $html = '<p>New enquiry received via the website.</p>'
        . '<ul>'
        . '<li><b>Name:</b> ' . e($enquiry['name']) . '</li>'
        . '<li><b>Phone:</b> ' . e($enquiry['phone'] ?? '—') . '</li>'
        . '<li><b>Email:</b> ' . e($enquiry['email'] ?? '—') . '</li>'
        . '<li><b>Message:</b> ' . nl2br(e($enquiry['message'])) . '</li>'
        . '</ul>'
        . '<p><a href="' . e(APP_URL) . '/admin/enquiries.php">Open in admin panel</a></p>';
    return smtp_send($notifyTo, APP_NAME . ' Admin', 'New enquiry from ' . $enquiry['name'], $html, $enquiry['email'] ?? null);
}
