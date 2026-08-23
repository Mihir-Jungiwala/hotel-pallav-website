<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/enquiries.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
db_run('UPDATE enquiries SET is_read = 1 WHERE id = ?', [$id]);
redirect('admin/enquiries.php');
