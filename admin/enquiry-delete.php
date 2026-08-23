<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/enquiries.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
db_run('DELETE FROM enquiries WHERE id = ?', [$id]);
flash('success', 'Enquiry deleted.');
redirect('admin/enquiries.php');
