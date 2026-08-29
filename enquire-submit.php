<?php
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

if (trim($_POST['company'] ?? '') !== '') {
    redirect('index.php#enquire');
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) < 2) $errors[] = 'Please enter your name.';
if ($message === '') $errors[] = 'Please enter a message.';
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'That email address does not look right.';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('index.php#enquire');
}

$enquiryId = db_insert(
    'INSERT INTO enquiries (name, phone, email, message, is_read, ip_address, created_at, updated_at) VALUES (?, ?, ?, ?, 0, ?, NOW(), NOW())',
    [$name, $phone !== '' ? $phone : null, $email !== '' ? $email : null, $message, $_SERVER['REMOTE_ADDR'] ?? null]
);

if (smtp_is_configured()) {
    $enquiry = db_one('SELECT * FROM enquiries WHERE id = ?', [$enquiryId]);
    if ($enquiry) {
        $adminLink = '<p><a href="' . e(APP_URL) . '/admin/bookings.php?filter=enquiry">Open in admin panel</a></p>';
        send_templated_mail('enquiry_received', $enquiry['email'] ?? '', $enquiry['name'], enquiry_email_vars($enquiry), $adminLink);
    }
}

flash('success', 'Thank you! Your message has been sent — our team will get back to you shortly.');
redirect('index.php#enquire');
