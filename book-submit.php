<?php
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf('index.php#enquire');

// Honeypot - bots fill hidden fields, humans never see them.
if (trim($_POST['company'] ?? '') !== '') {
    redirect('index.php#enquire');
}

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

// International-friendly: a bare 10-digit number is assumed Indian (default +91),
// any other country code + length is accepted too via a sane digit-count range.
// Always stored with a leading + so the country code is never lost.
$hasPlus = isset($phone[0]) && $phone[0] === '+';
$digits = preg_replace('/\D/', '', $phone);
if (!$hasPlus && strlen($digits) === 10) $digits = '91' . $digits;
if (strlen($digits) < 7 || strlen($digits) > 15) {
    $errors[] = 'Please enter a valid phone number with country code.';
}
$phone = '+' . $digits;

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'That email address does not look right.';
}

if ($checkin === '' || !strtotime($checkin)) $errors[] = 'Please pick a check-in date.';
if ($checkout === '' || !strtotime($checkout)) $errors[] = 'Please pick a check-out date.';
if ($roomQuery === '') $errors[] = 'Please pick a room.';
if ($message === '') $errors[] = 'Please tell us anything we should know (or write "none").';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('index.php#enquire');
}

$adults = max(1, min(20, $adults ?: 1));
$children = max(0, min(9, $children));

$room = null;
if ($roomQuery !== '') {
    $room = db_one("SELECT * FROM rooms WHERE name LIKE ? ORDER BY id LIMIT 1", ['%' . $roomQuery . '%']);
}
if (!$room) {
    $room = db_one('SELECT * FROM rooms ORDER BY id LIMIT 1');
}

$checkinDate = $checkin !== '' ? $checkin : date('Y-m-d', strtotime('+1 day'));
$checkoutDate = $checkout !== '' ? $checkout : date('Y-m-d', strtotime('+2 day'));
if (strtotime($checkoutDate) < strtotime($checkinDate)) {
    $checkoutDate = $checkinDate;
}

$reference = generate_reference();

$enquiryId = db_insert(
    'INSERT INTO enquiries (reference, room_id, name, phone, email, check_in, check_out, guests, message, status, ip_address, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "new", ?, NOW(), NOW())',
    [
        $reference,
        $room['id'] ?? null,
        $name,
        $phone,
        $email !== '' ? $email : null,
        $checkinDate,
        $checkoutDate,
        $adults + $children,
        $message !== '' ? $message : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]
);

if (smtp_is_configured()) {
    $enquiry = db_one('SELECT * FROM enquiries WHERE id = ?', [$enquiryId]);
    if ($enquiry) {
        $adminLink = '<p><a href="' . e(APP_URL) . '/admin/bookings.php">Open in admin panel</a></p>';
        send_templated_mail('enquiry_received', $enquiry['email'] ?? '', $enquiry['name'], enquiry_email_vars($enquiry, $room), $adminLink);
    }
}

flash('success', "Thank you! Your enquiry reference is {$reference}. We will call you shortly to confirm.");
redirect('index.php#enquire');
