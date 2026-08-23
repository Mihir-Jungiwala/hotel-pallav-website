<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$roomId = (int) ($_POST['room_id'] ?? 0);
$label = trim($_POST['label'] ?? '');
$start = $_POST['start_date'] ?? '';
$end = $_POST['end_date'] ?? '';
$price = (int) ($_POST['price'] ?? 0);
$priceBreakfast = $_POST['price_with_breakfast'] !== '' ? (int) $_POST['price_with_breakfast'] : null;

if (!$roomId || $label === '' || !$start || !$end || !$price) {
    flash('error', 'Please fill in all required special period fields.');
    redirect('admin/pricing.php');
}

$id = db_insert('INSERT INTO room_rates (room_id, label, start_date, end_date, price, price_with_breakfast, active) VALUES (?,?,?,?,?,?,1)',
    [$roomId, $label, $start, $end, $price, $priceBreakfast]);

$room = db_one('SELECT name FROM rooms WHERE id = ?', [$roomId]);
log_activity('rate.created', "Added rate \"{$label}\" for " . ($room['name'] ?? ''), 'room_rate', $id, ['price' => $price, 'range' => "$start to $end"]);
flash('success', "Rate plan \"{$label}\" added.");
redirect('admin/pricing.php');
