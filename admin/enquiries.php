<?php
// Enquiries were merged into the Guest Activity page (admin/bookings.php) as a tab.
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
redirect('admin/bookings.php?filter=enquiry');
