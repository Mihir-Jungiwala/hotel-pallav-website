<?php
require_once __DIR__ . '/../includes/helpers.php';
redirect(current_user() ? 'admin/dashboard.php' : 'admin/login.php');
