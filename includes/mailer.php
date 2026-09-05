<?php
/**
 * Minimal dependency-free SMTP client (no Composer/PHPMailer available on
 * shared hosting). Built for Gmail SMTP (smtp.gmail.com:587, STARTTLS) but
 * works with any standard SMTP+AUTH LOGIN server.
 *
 * All send functions are best-effort: they log failures via error_log and
 * return false rather than throwing, so a broken mail config never breaks
 * an enquiry submission or a password reset.
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
        error_log("SMTP connect failed to {$host}:{$port} - {$errstr} ({$errno})");
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
 * (purple Hotel Pallav header, white content card, footer) - table markup and inline
 * styles throughout for email-client compatibility, no external image dependency.
 */
function email_shell(string $heading, string $bodyHtml, bool $guestFacing = true): string
{
    $s = get_settings();
    $hotel = e(APP_NAME);
    $year = date('Y');

    // Everything in the header/footer comes from Settings, so updating the hotel's
    // details in the admin panel updates every email - nothing here is hardcoded.
    $receptionRaw = trim((string) ($s['reception_phone'] ?? $s['gm_phone'] ?? ''));
    $phone = e(phone_display($receptionRaw));
    $phoneHref = e(preg_replace('/[^0-9+]/', '', $receptionRaw));
    $addressText = trim((string) ($s['address'] ?? ''));
    $hotelEmail = trim((string) ($s['email'] ?? ''));
    $since = (int) ($s['opened_year'] ?? 0);

    $tagline = $since > 0
        ? 'Rajkot &middot; Since ' . $since
        : 'Rajkot';

    // Logo if one has been uploaded in Settings, otherwise the wordmark on its own.
    $logo = '';
    if (!empty($s['logo_path'])) {
        $logo = '<img src="' . e(UPLOADS_URL . '/' . $s['logo_path']) . '" width="54" height="54" alt="' . $hotel . '"'
              . ' style="display:block;margin:0 auto 10px;width:54px;height:54px;border-radius:12px;object-fit:contain;background:#FFFFFF;padding:5px;">';
    }

    // Footer contact lines, each only rendered when the value actually exists.
    $footerBits = [];
    if ($addressText !== '') {
        $footerBits[] = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:19px;color:#7A7392;padding-bottom:5px;">' . e($addressText) . '</div>';
    }
    $inline = [];
    if ($receptionRaw !== '') {
        $inline[] = '<a href="tel:' . $phoneHref . '" style="color:#6D28D9;text-decoration:none;font-weight:bold;">' . $phone . '</a>';
    }
    if ($hotelEmail !== '') {
        $inline[] = '<a href="mailto:' . e($hotelEmail) . '" style="color:#6D28D9;text-decoration:none;font-weight:bold;">' . e($hotelEmail) . '</a>';
    }
    if ($inline) {
        $footerBits[] = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:19px;color:#7A7392;padding-bottom:8px;">' . implode(' &nbsp;&middot;&nbsp; ', $inline) . '</div>';
    }
    $footerContact = implode('', $footerBits);

    // "Call us if you need anything" only belongs on mail going to a guest - on a staff
    // notification it would be the hotel inviting itself to phone itself.
    $helpStrip = '';
    if ($guestFacing && $receptionRaw !== '') {
        $helpStrip = '<tr><td class="ep-pad" style="padding:8px 40px 32px;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F7F4FF;border-radius:12px;border-left:3px solid #8B5CF6;">'
            . '<tr><td style="padding:16px 18px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#7A7392;">'
            . 'Need anything at all? Call the front desk on <a href="tel:' . $phoneHref . '" style="color:#5B21B6;text-decoration:none;font-weight:bold;">' . $phone . '</a> - we are happy to help.'
            . '</td></tr></table></td></tr>';
    } else {
        // Keep the content block from sitting flush against the footer.
        $helpStrip = '<tr><td style="height:26px;line-height:26px;font-size:0;">&nbsp;</td></tr>';
    }

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
    /* Label above value instead of beside it - a 42% label column leaves too little
       room for a room name or an email address on a narrow phone. */
    .ep-cell { display: block !important; width: 100% !important; }
    .ep-cell-l { padding-bottom: 2px !important; border-bottom: 0 !important; }
    .ep-cell-v { padding-top: 0 !important; }
    /* Check-in and check-out stack, so neither date has to wrap mid-word. */
    .ep-half { display: block !important; width: 100% !important; padding: 14px 10px !important; }
    .ep-mid  { display: block !important; width: 100% !important; padding: 0 0 10px !important; }
    .ep-btn  { display: block !important; width: 100% !important; padding: 0 0 10px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background-color:#F5F2FC;-webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#F5F2FC;">
<tr><td align="center" style="padding:28px 12px;">
<table role="presentation" class="ep-wrap" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:600px;background-color:#FFFFFF;border-radius:18px;overflow:hidden;border:1px solid #E9E2FA;">

<tr><td style="background:#5B21B6;padding:26px 24px;text-align:center;">
  {$logo}
  <div style="font-family:Georgia,'Times New Roman',serif;font-size:22px;font-weight:bold;color:#FFFFFF;letter-spacing:.5px;">{$hotel}</div>
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:bold;letter-spacing:2px;color:#DDD3FA;text-transform:uppercase;margin-top:4px;">{$tagline}</div>
</td></tr>

<tr><td class="ep-pad" style="padding:32px 40px 8px;">
  <div class="ep-heading" style="font-family:Georgia,'Times New Roman',serif;font-size:26px;line-height:32px;font-weight:bold;color:#4A1A8F;text-align:center;padding-bottom:20px;">{$heading}</div>
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#4A4262;">
{$bodyHtml}
  </div>
</td></tr>

{$helpStrip}

<tr><td style="background-color:#FBF9FF;padding:22px 24px;text-align:center;border-top:1px solid #EFE9FE;">
  <div style="font-family:Georgia,'Times New Roman',serif;font-size:15px;font-weight:bold;color:#5B21B6;padding-bottom:6px;">{$hotel}</div>
  {$footerContact}
  <div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:18px;color:#A79FC7;">&copy; {$year} {$hotel}. All rights reserved.</div>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
HTML;
}

/**
 * A label/value details table - the standard way booking information is laid out in
 * every guest email. Table markup with inline styles so it survives Outlook, and it
 * reflows to full width on a phone via the .ep-wrap rule in the shell.
 *
 * $pairs is ['Label' => 'value', ...]; rows with an empty value are skipped, so a
 * template never shows "Room: -" for an enquiry that didn't name one.
 */
function email_details_table(array $pairs): string
{
    $rows = '';
    $i = 0;
    foreach ($pairs as $label => $value) {
        if (trim((string) $value) === '') continue;
        $bg = $i % 2 === 0 ? '#FFFFFF' : '#FBF9FF';
        $rows .= '<tr>'
            . '<td class="ep-cell ep-cell-l" width="42%" style="width:42%;padding:12px 16px;background:' . $bg . ';border-bottom:1px solid #EFE9FE;'
            . 'font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#7A7392;vertical-align:top;">'
            . e($label) . '</td>'
            . '<td class="ep-cell ep-cell-v" style="padding:12px 16px;background:' . $bg . ';border-bottom:1px solid #EFE9FE;'
            . 'font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;color:#1B1235;vertical-align:top;">'
            . $value . '</td>'
            . '</tr>';
        $i++;
    }
    if ($rows === '') return '';

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"'
        . ' style="width:100%;margin:20px 0;border:1px solid #E9E2FA;border-radius:12px;overflow:hidden;border-collapse:separate;">'
        . $rows . '</table>';
}

/** The check-in / check-out pair, shown side by side as the centrepiece of a stay email. */
function email_stay_band(string $checkIn, string $checkOut, string $nights, string $checkInTime, string $checkOutTime): string
{
    if ($checkIn === '' || $checkOut === '') return '';
    $nightLabel = $nights !== '' ? $nights . ' night' . ($nights === '1' ? '' : 's') : '&nbsp;';
    $inTime = $checkInTime !== '' ? 'from ' . e($checkInTime) : '';
    $outTime = $checkOutTime !== '' ? 'by ' . e($checkOutTime) : '';

    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:22px 0;background:#F7F4FF;border-radius:14px;">'
        . '<tr>'
        . '<td class="ep-half" width="44%" align="center" style="width:44%;padding:18px 10px;">'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#8B5CF6;padding-bottom:5px;">Check-in</div>'
        . '<div style="font-family:Georgia,\'Times New Roman\',serif;font-size:19px;font-weight:bold;color:#4A1A8F;">' . e($checkIn) . '</div>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#7A7392;padding-top:3px;">' . $inTime . '</div>'
        . '</td>'
        . '<td class="ep-mid" width="12%" align="center" style="width:12%;padding:18px 0;">'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;color:#A886F7;letter-spacing:.5px;">' . $nightLabel . '</div>'
        . '</td>'
        . '<td class="ep-half" width="44%" align="center" style="width:44%;padding:18px 10px;">'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:#8B5CF6;padding-bottom:5px;">Check-out</div>'
        . '<div style="font-family:Georgia,\'Times New Roman\',serif;font-size:19px;font-weight:bold;color:#4A1A8F;">' . e($checkOut) . '</div>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#7A7392;padding-top:3px;">' . $outTime . '</div>'
        . '</td>'
        . '</tr></table>';
}

/** Status pill (Confirmed / Declined / Received) shown under the heading. */
function email_status_pill(string $text, string $bg, string $fg): string
{
    return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto 4px;">'
        . '<tr><td style="background:' . $bg . ';border-radius:999px;padding:7px 18px;'
        . 'font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:bold;letter-spacing:1.5px;text-transform:uppercase;color:' . $fg . ';">'
        . e($text) . '</td></tr></table>';
}

/** Call / WhatsApp buttons so a guest can reach the hotel straight from the email. */
function email_contact_buttons(): string
{
    $s = get_settings();
    $tel = preg_replace('/[^0-9+]/', '', (string) ($s['reception_phone'] ?? $s['gm_phone'] ?? ''));
    $wa = preg_replace('/[^0-9]/', '', (string) ($s['reception_whatsapp'] ?? $s['whatsapp'] ?? ''));
    if ($tel === '' && $wa === '') return '';

    $cells = '';
    if ($tel !== '') {
        $cells .= '<td class="ep-btn" align="center" style="padding:0 5px;">'
            . '<a href="tel:' . e($tel) . '" style="display:inline-block;background:#5B21B6;color:#FFFFFF;font-family:Arial,Helvetica,sans-serif;'
            . 'font-size:14px;font-weight:bold;text-decoration:none;padding:13px 26px;border-radius:10px;">Call the hotel</a></td>';
    }
    if ($wa !== '') {
        $cells .= '<td class="ep-btn" align="center" style="padding:0 5px;">'
            . '<a href="https://wa.me/' . e($wa) . '" style="display:inline-block;background:#FFFFFF;color:#5B21B6;border:2px solid #DFD3FD;font-family:Arial,Helvetica,sans-serif;'
            . 'font-size:14px;font-weight:bold;text-decoration:none;padding:11px 26px;border-radius:10px;">WhatsApp us</a></td>';
    }
    return '<table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:24px auto 6px;"><tr>' . $cells . '</tr></table>';
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

/** Password reset link - sent to the admin whose account it is. */
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

/** Built-in fallback content for each editable email template - used whenever a row hasn't been customized yet. */
const EMAIL_TEMPLATE_DEFAULTS = [
    // Guest-facing. Warm, specific, and honest that nothing is reserved yet.
    'enquiry_received' => [
        'subject' => 'We have your enquiry - {{reference}}',
        'body' => "<p style=\"margin:0 0 14px;\">Dear {{guest_first_name}},</p>\n"
            . "<p style=\"margin:0 0 4px;\">Thank you for thinking of {{hotel_name}} for your stay in Rajkot. We have your enquiry and our front desk is checking availability for your dates now.</p>\n"
            . "{{stay_band}}\n"
            . "{{details_table}}\n"
            . "<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;margin:4px 0 6px;background:#FFFBEB;border-left:3px solid #C9A227;border-radius:10px;\">"
            . "<tr><td style=\"padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:21px;color:#7A5B12;\">"
            . "<b>Please note:</b> this is an enquiry, not a confirmed booking. Your room is held only once we have spoken with you and confirmed it."
            . "</td></tr></table>\n"
            . "<p style=\"margin:16px 0 0;\">We will call you on <b>{{guest_phone}}</b> shortly to confirm availability and settle the details. If you would rather reach us first, we are always glad to hear from you.</p>\n"
            . "{{contact_buttons}}\n"
            . "<p style=\"margin:18px 0 0;\">We look forward to welcoming you.</p>\n"
            . "<p style=\"margin:6px 0 0;color:#7A7392;\">Warm regards,<br><b style=\"color:#5B21B6;\">The team at {{hotel_name}}</b></p>",
    ],

    // Staff-facing: sent to the notification addresses when an enquiry is confirmed.
    'enquiry_confirmed' => [
        'subject' => 'Booking confirmed - {{reference}}',
        'body' => "{{pill_confirmed}}\n"
            . "<p style=\"margin:0 0 4px;\"><b>{{guest_name}}</b> is now confirmed as a booking. The room has been taken out of availability for these dates.</p>\n"
            . "{{stay_band}}\n"
            . "{{staff_table}}",
    ],

    // Staff-facing: sent to the notification addresses when an enquiry is declined.
    'enquiry_declined' => [
        'subject' => 'Enquiry declined - {{reference}}',
        'body' => "{{pill_declined}}\n"
            . "<p style=\"margin:0 0 4px;\">The enquiry from <b>{{guest_name}}</b> has been declined. No room is held for these dates.</p>\n"
            . "{{stay_band}}\n"
            . "<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\" style=\"width:100%;margin:18px 0 4px;background:#FEF2F2;border-left:3px solid #EF4444;border-radius:10px;\">"
            . "<tr><td style=\"padding:14px 16px;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:21px;color:#991B1B;\">"
            . "<b>Reason given:</b> {{decision_note}}"
            . "</td></tr></table>\n"
            . "{{staff_table}}",
    ],
];

/** Human-readable labels for each fixed template key. */
const EMAIL_TEMPLATE_LABELS = [
    'enquiry_received' => 'Enquiry Received',
    'enquiry_confirmed' => 'Booking Confirmed',
    'enquiry_declined' => 'Enquiry Declined',
];

/** Fixed subject/body for one template key - not admin-editable. */
function email_template(string $key): array
{
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

/** Splits Settings' notify_email (one address per line, or comma-separated) into a clean list. */
function notify_email_list(): array
{
    $s = get_settings();
    $raw = $s['notify_email'] ?? $s['email'] ?? '';
    $emails = preg_split('/[\r\n,]+/', (string) $raw);
    $emails = array_filter(array_map('trim', $emails), fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL));
    return array_values(array_unique($emails));
}

/**
 * Sends a rendered template to the guest (if an email was given) and always sends a copy
 * to every notification address configured in Settings, so every guest-facing email is
 * also seen by the team. $ownerExtraHtml (e.g. an admin-panel link) is appended only to
 * those copies.
 */
function send_templated_mail(string $key, string $toEmail, string $toName, array $vars, ?string $ownerExtraHtml = null): bool
{
    $rendered = render_email_template($key, $vars);
    $heading = EMAIL_TEMPLATE_LABELS[$key] ?? APP_NAME;
    $subject = APP_NAME . ' - ' . $rendered['subject'];
    $html = email_shell($heading, $rendered['body']);
    $sent = $toEmail !== '' ? smtp_send($toEmail, $toName ?: 'Guest', $subject, $html) : true;

    $ownerBody = email_shell($heading, $rendered['body'] . ($ownerExtraHtml ?? ''), false);
    foreach (notify_email_list() as $notifyTo) {
        smtp_send($notifyTo, APP_NAME . ' Admin', $subject, $ownerBody, $toEmail !== '' ? $toEmail : null);
    }
    return $sent;
}

/**
 * Sends a rendered template only to the Settings notification list - never to the guest.
 * Used for status-change events (confirmed/declined) after the guest has already gotten
 * their one "we've received this" email, so they aren't emailed again on every update.
 */
function send_admin_notification(string $key, array $vars, ?string $ownerExtraHtml = null): void
{
    $rendered = render_email_template($key, $vars);
    $heading = EMAIL_TEMPLATE_LABELS[$key] ?? APP_NAME;
    $subject = APP_NAME . ' - ' . $rendered['subject'];
    $html = email_shell($heading, $rendered['body'] . ($ownerExtraHtml ?? ''), false);
    foreach (notify_email_list() as $notifyTo) {
        smtp_send($notifyTo, APP_NAME . ' Admin', $subject, $html);
    }
}

/**
 * Placeholder values available to the enquiry_* templates.
 *
 * Everything the hotel side of these emails says - name, address, phone numbers,
 * check-in/check-out times - is read from Settings, so changing a detail in the admin
 * panel changes every email the system sends. The pre-built {{details_table}},
 * {{stay_band}} and {{contact_buttons}} blocks keep the templates readable while still
 * producing the full table-based layout.
 */
function enquiry_email_vars(array $enquiry, ?array $room = null): array
{
    $s = get_settings();

    // d/m/Y throughout, matching every date shown in the admin panel and on the site.
    $checkIn = $enquiry['check_in'] ? date('d/m/Y', strtotime($enquiry['check_in'])) : '';
    $checkOut = $enquiry['check_out'] ? date('d/m/Y', strtotime($enquiry['check_out'])) : '';
    $nights = '';
    if ($enquiry['check_in'] && $enquiry['check_out']) {
        $count = count(stay_nights($enquiry['check_in'], $enquiry['check_out']));
        if ($count > 0) $nights = (string) $count;
    }

    $firstName = trim(explode(' ', trim((string) $enquiry['name']))[0] ?? '');
    $guestPhoneRaw = (string) ($enquiry['phone'] ?? '');
    $guestPhone = $guestPhoneRaw !== '' ? phone_display($guestPhoneRaw) : '';
    $guestEmail = trim((string) ($enquiry['email'] ?? ''));
    $roomName = $room['name'] ?? '';
    $guests = (string) ($enquiry['guests'] ?? '');
    $checkInTime = trim((string) ($s['checkin_time'] ?? ''));
    $checkOutTime = trim((string) ($s['checkout_time'] ?? ''));
    $message = trim((string) ($enquiry['message'] ?? ''));

    // Guest-facing summary: the stay itself, without repeating their own contact details.
    $detailsTable = email_details_table([
        'Reference' => e($enquiry['reference'] ?? ''),
        'Room' => $roomName !== '' ? e($roomName) : '',
        'Guests' => $guests !== '' ? e($guests) : '',
        'Nights' => $nights,
    ]);

    // Staff-facing summary: who to contact and what they asked for. The dates aren't
    // repeated here - the stay band above already shows them.
    $staffTable = email_details_table([
        'Reference' => e($enquiry['reference'] ?? ''),
        'Guest' => e((string) $enquiry['name']),
        'Phone' => $guestPhone !== '' ? '<a href="tel:' . e(preg_replace('/[^0-9+]/', '', $guestPhoneRaw)) . '" style="color:#6D28D9;text-decoration:none;">' . e($guestPhone) . '</a>' : '',
        'Email' => $guestEmail !== '' ? '<a href="mailto:' . e($guestEmail) . '" style="color:#6D28D9;text-decoration:none;">' . e($guestEmail) . '</a>' : '',
        'Room' => $roomName !== '' ? e($roomName) : '',
        'Guests' => $guests !== '' ? e($guests) : '',
        'Message' => $message !== '' ? nl2br(e($message)) : '',
    ]);

    return [
        'guest_name' => e((string) $enquiry['name']),
        'guest_first_name' => e($firstName !== '' ? $firstName : 'there'),
        'reference' => e($enquiry['reference'] ?? ''),
        'room_name' => e($roomName !== '' ? $roomName : 'a room'),
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'nights' => $nights,
        'guests' => e($guests),
        'guest_phone' => e($guestPhone),
        'guest_email' => e($guestEmail),
        'message' => nl2br(e($message)),
        'decision_note' => $enquiry['decision_note'] ?? null ? e($enquiry['decision_note']) : '',

        // From the admin panel
        'hotel_name' => e(APP_NAME),
        'hotel_address' => e((string) ($s['address'] ?? '')),
        'hotel_email' => e((string) ($s['email'] ?? '')),
        'reception_phone' => e(phone_display($s['reception_phone'] ?? '')),
        'checkin_time' => e($checkInTime),
        'checkout_time' => e($checkOutTime),

        // Pre-rendered layout blocks
        'details_table' => $detailsTable,
        'staff_table' => $staffTable,
        'stay_band' => email_stay_band($checkIn, $checkOut, $nights, $checkInTime, $checkOutTime),
        'contact_buttons' => email_contact_buttons(),
        'pill_received' => email_status_pill('Enquiry received', '#EFE9FE', '#5B21B6'),
        'pill_confirmed' => email_status_pill('Confirmed', '#DCFCE7', '#15803D'),
        'pill_declined' => email_status_pill('Not available', '#FEE2E2', '#B91C1C'),
    ];
}
