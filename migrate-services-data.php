<?php
/**
 * One-time migration: copies services out of page_content.services (JSON blob)
 * into the new `services` table. Visit this file once in your browser while
 * logged into the admin panel, then delete it from the server.
 */
require_once __DIR__ . '/includes/helpers.php';
require_admin();

header('Content-Type: text/plain');

$already = (int) db_one('SELECT COUNT(*) c FROM services')['c'];
if ($already > 0) {
    echo "Nothing to do — the services table already has {$already} row(s). Delete this file.\n";
    exit;
}

$content = get_page_content();
$services = json_decode_field($content['services'] ?? null, []);

if (!$services) {
    echo "No services found in page_content.services — nothing to migrate. Delete this file.\n";
    exit;
}

$n = 0;
foreach ($services as $i => $svc) {
    db_run(
        'INSERT INTO services (title, description, icon, icon_path, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,NOW(),NOW())',
        [
            trim($svc['title'] ?? ''),
            trim($svc['desc'] ?? '') ?: null,
            $svc['icon'] ?? 'front-desk',
            $svc['icon_path'] ?? null,
            $i,
        ]
    );
    $n++;
}

echo "Migrated {$n} service(s) into the services table. Delete this file now.\n";
