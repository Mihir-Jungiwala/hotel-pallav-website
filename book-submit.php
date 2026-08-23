<?php
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

// Honeypot — bots fill hidden fields, humans never see them.
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

$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) === 12 && str_starts_with($digits, '91')) $digits = substr($digits, 2);
if (strlen($digits) === 11 && $digits[0] === '0') $digits = substr($digits, 1);
if (strlen($digits) !== 10 || strpos('6789', $digits[0]) === false) {
    $errors[] = 'Please enter a valid 10-digit mobile number.';
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'That email address does not look right.';
}

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('index.php#enquire');
}

$adults = max(1, min(20, $adults ?: 1));
$children = max(0, min(10, $children));

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

function generate_booking_reference(): string
{
    do {
        $ref = 'HP-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    } while (db_one('SELECT id FROM bookings WHERE reference = ?', [$ref]));
    return $ref;
}

$reference = generate_booking_reference();

db_insert(
    'INSERT INTO bookings (reference, room_id, guest_name, guest_phone, guest_email, check_in, check_out, guests, message, status, ip_address, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", ?, NOW(), NOW())',
    [
        $reference,
        $room['id'] ?? null,
        $name,
        $digits,
        $email !== '' ? $email : null,
        $checkinDate,
        $checkoutDate,
        $adults + $children,
        $message !== '' ? $message : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]
);

flash('success', "Thank you! Your enquiry reference is {$reference}. We will call you shortly to confirm.");
redirect('index.php#enquire');
