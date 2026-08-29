<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/users.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$me = current_user();
$target = db_one('SELECT * FROM users WHERE id = ?', [$id]);

if (!$target) {
    flash('error', 'User not found.');
    redirect('admin/users.php');
}
if ((int) $target['id'] === (int) $me['id']) {
    flash('error', 'You are already the Master Admin.');
    redirect('admin/users.php');
}

db_run('UPDATE users SET role = ? WHERE id = ?', ['admin', $me['id']]);
db_run('UPDATE users SET role = ? WHERE id = ?', ['master_admin', $target['id']]);
log_activity('user.master_transferred', "Transferred Master Admin to {$target['name']}", 'user', $target['id']);
flash('success', "{$target['name']} is now the Master Admin. Your account is now a regular Admin.");
redirect('admin/users.php');
