<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

function gallery_slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'hotel-pallav-rajkot';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'caption') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $caption = trim($_POST['caption'] ?? '');
    db_run('UPDATE gallery_photos SET caption = ? WHERE id = ?', [$caption ?: null, $id]);
    redirect('admin/gallery.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $maxSort = (int) (db_one('SELECT MAX(sort_order) m FROM gallery_photos')['m'] ?? 0);
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $uploaded = 0;
    $baseCaption = trim($_POST['caption'] ?? '');
    $baseSlug = gallery_slugify($baseCaption !== '' ? $baseCaption : 'hotel-pallav-rajkot');

    if (!empty($_FILES['photos']['name'][0])) {
        $count = count($_FILES['photos']['name']);
        if (!is_dir(UPLOADS_PATH . '/gallery')) mkdir(UPLOADS_PATH . '/gallery', 0755, true);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            $slug = $baseSlug;
            $filename = $slug . '.' . $ext;
            $n = 1;
            while (is_file(UPLOADS_PATH . '/gallery/' . $filename)) {
                $n++;
                $filename = $slug . '-' . $n . '.' . $ext;
            }

            $dest = UPLOADS_PATH . '/gallery/' . $filename;
            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $dest)) {
                $maxSort++;
                $caption = $baseCaption !== '' ? $baseCaption : 'Hotel Pallav, Rajkot';
                db_run('INSERT INTO gallery_photos (path, caption, sort_order, created_at, updated_at) VALUES (?,?,?,NOW(),NOW())', ['gallery/' . $filename, $caption, $maxSort]);
                $uploaded++;
            }
        }
    }

    if ($uploaded > 0) {
        log_activity('gallery.uploaded', "Uploaded {$uploaded} gallery photo(s)");
        flash('success', "{$uploaded} photo(s) uploaded.");
    } else {
        flash('error', 'No valid photos were uploaded.');
    }
    redirect('admin/gallery.php');
}

$photos = db_all('SELECT * FROM gallery_photos ORDER BY sort_order, id');

$title = 'Gallery';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
    <div>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Gallery</h1>
      <p class="text-sm text-pallav-500 mt-1">Photos shown in the homepage gallery section.</p>
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 mb-8 space-y-4">
    <?= csrf_field() ?>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Description / keywords for these photos <span class="normal-case font-semibold text-pallav-300">(used for the filename and alt text — good for SEO, e.g. "Deluxe Room Hotel Pallav Rajkot")</span></label>
      <input type="text" name="caption" placeholder="e.g. Deluxe Room Hotel Pallav Rajkot" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>
    <div>
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Upload photos</label>
      <input type="file" name="photos[]" accept="image/*" multiple class="block w-full text-sm text-pallav-600 file:mr-4 file:rounded-lg file:border-0 file:bg-pallav-100 file:px-4 file:py-2 file:text-xs file:font-bold file:text-pallav-700 hover:file:bg-pallav-200">
    </div>
    <div class="flex justify-end">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Upload</button>
    </div>
  </form>

  <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php if (!$photos): ?>
      <div class="col-span-full rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-10 text-center text-pallav-400">No photos yet — upload your first ones above.</div>
    <?php else: foreach ($photos as $photo): ?>
      <div class="relative group rounded-2xl overflow-hidden ring-1 ring-pallav-100 shadow-sm" x-data="{ editing: false }">
        <div class="aspect-square">
          <img src="<?= e(UPLOADS_URL . '/' . $photo['path']) ?>" alt="<?= e($photo['caption'] ?? 'Hotel Pallav, Rajkot') ?>" class="w-full h-full object-cover">
        </div>
        <form method="POST" action="<?= e(APP_URL) ?>/admin/gallery-delete.php" onsubmit="return confirm('Delete this photo?')" class="absolute top-2 right-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $photo['id'] ?>">
          <button class="w-8 h-8 rounded-lg bg-rose-600/90 hover:bg-rose-700 text-white flex items-center justify-center transition opacity-0 group-hover:opacity-100">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 6l12 12M18 6L6 18"/></svg>
          </button>
        </form>
        <button type="button" @click="editing = true" x-show="!editing" class="absolute top-2 left-2 w-8 h-8 rounded-lg bg-pallav-900/80 hover:bg-pallav-900 text-white flex items-center justify-center transition opacity-0 group-hover:opacity-100">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/></svg>
        </button>
        <div class="absolute inset-x-0 bottom-0 bg-black/70 px-2 py-1.5 text-[10px] text-white truncate" x-show="!editing"><?= e($photo['caption'] ?? '') ?></div>
        <form x-show="editing" x-cloak method="POST" class="absolute inset-x-0 bottom-0 bg-white/95 p-2 flex gap-1.5">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="caption">
          <input type="hidden" name="id" value="<?= $photo['id'] ?>">
          <input type="text" name="caption" value="<?= e($photo['caption'] ?? '') ?>" placeholder="Alt text / caption" class="flex-1 min-w-0 rounded-lg border border-pallav-200 px-2 py-1 text-xs focus:border-pallav-500 outline-none">
          <button type="submit" class="text-[10px] font-bold bg-pallav-700 text-white rounded-lg px-2">Save</button>
        </form>
      </div>
    <?php endforeach; endif; ?>
  </div>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
