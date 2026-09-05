<?php
/**
 * Lazy-load endpoint for the full Google Business Profile review history.
 * Only used when admin/gbp-connect.php is connected - the homepage renders
 * an initial batch server-side and the carousel calls here for the rest as
 * the visitor browses, instead of dumping hundreds of cards into the page.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/gbp.php';

header('Content-Type: application/json');

if (!gbp_is_connected()) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Not connected']);
    exit;
}

$offset = max(0, (int) ($_GET['offset'] ?? 0));
$limit = max(1, min(30, (int) ($_GET['limit'] ?? 15)));

$data = gbp_fetch_all_reviews();
if (!$data['ok']) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => $data['error']]);
    exit;
}

$slice = array_slice($data['reviews'], $offset, $limit);
$html = '';
foreach ($slice as $r) {
    $long = mb_strlen($r['text']) > 150;
    $html .= '<div class="rev rv">';
    $html .= '<span class="big-q">"</span>';
    $html .= '<div class="rev-who">';
    if (!empty($r['photo'])) {
        $html .= '<span class="rev-av"><img src="' . e($r['photo']) . '" alt="" loading="lazy" referrerpolicy="no-referrer"></span>';
    } else {
        $html .= '<span class="rev-av">' . e($r['initials']) . '</span>';
    }
    $html .= '<div class="rev-who-tx"><b>' . e($r['author']) . ' <svg class="rev-g" aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 48 48"><path fill="#4285F4" d="M45 24.5c0-1.6-.1-2.7-.4-4H24v7.6h12c-.2 2-1.5 5-4.4 7l6.7 5.2c4-3.7 6.7-9.1 6.7-15.8z"/><path fill="#34A853" d="M24 46c5.9 0 10.9-2 14.5-5.3l-6.9-5.4c-1.9 1.3-4.4 2.2-7.6 2.2-5.8 0-10.7-3.8-12.4-9.1l-7.1 5.5C8.1 41 15.5 46 24 46z"/><path fill="#FBBC05" d="M11.6 28.4c-.5-1.4-.8-2.8-.8-4.4s.3-3 .7-4.4l-7.1-5.5C2.9 17 2 20.4 2 24s.9 7 2.4 9.9z"/><path fill="#EA4335" d="M24 10.2c4.1 0 6.9 1.8 8.5 3.3l6.2-6C34.9 4 29.9 2 24 2 15.5 2 8.1 7 4.4 14.1l7.1 5.5C13.3 14.3 18.2 10.2 24 10.2z"/></svg></b>';
    $html .= '<small>' . e($r['when']) . '</small></div></div>';
    $html .= '<div class="stars">';
    for ($s = 1; $s <= 5; $s++) {
        $html .= '<svg aria-hidden="true" focusable="false" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" opacity="' . ($s > $r['rating'] ? '.3' : '1') . '"><path d="M12 2l2.9 6.1 6.6.9-4.8 4.6 1.2 6.6L12 17.1 6.1 20.2l1.2-6.6L2.5 9l6.6-.9z"/></svg>';
    }
    $html .= '</div>';
    $html .= '<p' . ($long ? ' class="clamp"' : '') . '>' . e($r['text']) . '</p>';
    if ($long) $html .= '<button type="button" class="rev-more">Read more</button>';
    $html .= '</div>';
}

echo json_encode(['ok' => true, 'html' => $html, 'count' => count($slice), 'total' => count($data['reviews'])]);
