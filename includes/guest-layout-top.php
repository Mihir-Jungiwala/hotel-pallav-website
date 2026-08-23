<?php
require_once __DIR__ . '/brand-mark.php';
$favicon = favicon_url();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title ?? 'Admin') ?> — Hotel Pallav</title>
<?php if ($favicon): ?><link rel="icon" href="<?= e($favicon) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&family=Tiro+Devanagari+Hindi&display=swap" rel="stylesheet">
<?php include __DIR__ . '/admin-tailwind-config.php'; ?>
</head>
<body class="min-h-screen bg-gradient-to-br from-pallav-900 via-pallav-800 to-pallav-600 antialiased relative overflow-hidden">
  <div class="pointer-events-none absolute -top-40 -left-32 w-[520px] h-[520px] rounded-full bg-pallav-400/30 blur-[90px]"></div>
  <div class="pointer-events-none absolute -bottom-40 -right-24 w-[460px] h-[460px] rounded-full bg-gold-500/20 blur-[90px]"></div>

  <div class="relative min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="flex flex-col items-center mb-8">
        <span class="w-14 h-14 rounded-2xl overflow-hidden shadow-xl mb-3"><?php render_brand_mark(56); ?></span>
        <div class="text-white font-display font-bold text-xl">Hotel Pallav</div>
        <div class="text-pallav-300 text-[11px] font-bold tracking-[.22em] uppercase mt-1">Admin Control Panel</div>
      </div>

      <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-10">
