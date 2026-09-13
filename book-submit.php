<?php
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf('index.php#mainMsg');

// Honeypot - bots fill hidden fields, humans never see them.
if (trim($_POST['company'] ?? '') !== '') {
    redirect('index.php#mainMsg');
}

if (is_enquiry_rate_limited()) {
    flash('error', "You've submitted several enquiries recently, please wait a few minutes before trying again.");
    redirect('index.php#mainMsg');
}
record_enquiry_submission();

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$checkin = trim($_POST['checkin'] ?? '');
$checkout = trim($_POST['checkout'] ?? '');
$roomQuery = trim($_POST['room'] ?? '');
$adults = (int) ($_POST['adults'] ?? 1);
$children = (int) ($_POST['children'] ?? 0);
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
if ($name !== '' && mb_strlen($name) > 100) $errors[] = 'Name is too long.';

// International-friendly: a bare 10-digit number is assumed Indian (default +91), a
// country code with a redundant local trunk "0" kept alongside it gets that "0"
// dropped (see normalize_intl_phone) so tel:/wa.me links from this number always
// work, and any other country code + length is accepted too via a sane digit-count
// range. Always stored with a leading + so the country code is never lost.
$phone = normalize_intl_phone($phone);
$digits = ltrim($phone, '+');
if (strlen($digits) < 7 || strlen($digits) > 15) {
    $errors[] = 'Please enter a valid phone number with country code.';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'That email address does not look right.';
}

if ($checkin === '' || !strtotime($checkin)) $errors[] = 'Please pick a check-in date.';
if ($checkout === '' || !strtotime($checkout)) $errors[] = 'Please pick a check-out date.';
if ($roomQuery === '') $errors[] = 'Please pick a room.';
if ($message === '') $errors[] = 'Please tell us anything we should know (or write "none").';
if (empty($_POST['accept_terms'])) $errors[] = 'Please accept the Terms & Conditions to continue.';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('index.php#mainMsg');
}

$adults = max(1, min(20, $adults ?: 1));
$children = max(0, min(9, $children));

// "Not sure yet" is a real, deliberate answer - it must never silently turn into
// "whichever room happens to be first in the table" the way an actually-unmatched
// room name still should (so a typo or an old removed room name doesn't just vanish
// into "no room" either). $room stays null only for the "not sure" case; enquiries
// keep showing "-" for room rather than a room the guest never actually picked.
$room = null;
if ($roomQuery !== '' && strcasecmp($roomQuery, 'Not sure yet') !== 0) {
    $room = db_one("SELECT * FROM rooms WHERE name LIKE ? ORDER BY id LIMIT 1", ['%' . $roomQuery . '%']);
    if (!$room) {
        $room = db_one('SELECT * FROM rooms ORDER BY id LIMIT 1');
    }
}

$checkinDate = $checkin !== '' ? $checkin : date('Y-m-d', strtotime('+1 day'));
$checkoutDate = $checkout !== '' ? $checkout : date('Y-m-d', strtotime('+2 day'));
if (strtotime($checkoutDate) < strtotime($checkinDate)) {
    $checkoutDate = $checkinDate;
}

$reference = generate_reference();

$enquiryId = db_insert(
    'INSERT INTO enquiries (reference, room_id, name, phone, email, check_in, check_out, guests, children, message, status, ip_address, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", ?, NOW(), NOW())',
    [
        $reference,
        $room['id'] ?? null,
        $name,
        $phone,
        $email !== '' ? $email : null,
        $checkinDate,
        $checkoutDate,
        $adults + $children,
        $children,
        $message !== '' ? $message : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]
);

$content = get_page_content();
$successMsg = trim((string) ($content['fm_msg_success'] ?? '')) ?: 'Thank you! Your enquiry reference is {{reference}}. We will call you shortly to confirm.';
flash('success', str_replace('{{reference}}', $reference, $successMsg));

// The guest shouldn't have to wait through a real SMTP round-trip per notification
// recipient (each one a live network conversation with Gmail, easily 1-3 seconds)
// just to see their confirmation - send the redirect the moment we actually know
// where they're going, then keep the request alive just long enough to send mail.
// fastcgi_finish_request() (PHP-FPM/LiteSpeed, i.e. the real Hostinger environment)
// does this properly; the plain-flush fallback still returns the page before this
// script exits everywhere else PHP can run as a CGI/module.
header('Location: ' . rtrim(APP_URL, '/') . '/index.php#mainMsg');
session_write_close();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    ignore_user_abort(true);
    if (ob_get_level() > 0) ob_end_flush();
    flush();
}

if (smtp_is_configured()) {
    $enquiry = db_one('SELECT * FROM enquiries WHERE id = ?', [$enquiryId]);
    if ($enquiry) {
        $adminLink = '<p><a href="' . e(APP_URL) . '/admin/bookings.php">Open in admin panel</a></p>';
        send_templated_mail('enquiry_received', $enquiry['email'] ?? '', $enquiry['name'], enquiry_email_vars($enquiry, $room), $adminLink);
    }
}
