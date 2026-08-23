<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$rate = db_one('SELECT * FROM room_rates WHERE id = ?', [$id]);
if ($rate) {
    $newActive = $rate['active'] ? 0 : 1;
    db_run('UPDATE room_rates SET active = ? WHERE id = ?', [$newActive, $id]);
    log_activity('rate.toggled', ($newActive ? 'Activated' : 'Paused') . " rate \"{$rate['label']}\"", 'room_rate', $id);
}
redirect('admin/pricing.php');
