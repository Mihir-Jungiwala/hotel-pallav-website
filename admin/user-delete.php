<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/users.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$me = current_user();

if ($id === (int) $me['id']) {
    flash('error', 'You cannot delete your own account while signed in.');
    redirect('admin/users.php');
}

$user = db_one('SELECT * FROM users WHERE id = ?', [$id]);
if ($user) {
    db_run('DELETE FROM users WHERE id = ?', [$id]);
    log_activity('user.deleted', "Deleted user {$user['name']}");
    flash('success', "{$user['name']} removed.");
}
redirect('admin/users.php');
