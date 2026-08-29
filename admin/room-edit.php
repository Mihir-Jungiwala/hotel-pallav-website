<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$room = $id ? db_one('SELECT * FROM rooms WHERE id = ?', [$id]) : null;
if ($id && !$room) { flash('error', 'Room not found.'); redirect('admin/rooms.php'); }
$photos = $room ? normalize_room_photos(json_decode_field($room['photos'])) : [];
$errors = [];

function slugify(string $name): string
{
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
    return $slug ?: 'room';
}

function unique_room_slug(string $name, ?int $ignoreId = null): string
{
    $base = slugify($name);
    $slug = $base;
    $i = 1;
    while (true) {
        $existing = $ignoreId
            ? db_one('SELECT id FROM rooms WHERE slug = ? AND id != ?', [$slug, $ignoreId])
            : db_one('SELECT id FROM rooms WHERE slug = ?', [$slug]);
        if (!$existing) return $slug;
        $slug = $base . '-' . (++$i);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $bedType = trim($_POST['bed_type'] ?? '');
    $maxGuests = max(1, (int) ($_POST['max_guests'] ?? 2));
    $totalCount = max(1, (int) ($_POST['total_count'] ?? 1));
    $showPrice = !empty($_POST['show_price']) ? 1 : 0;
    $note = trim($_POST['note'] ?? '');

    if ($name === '') { $errors[] = 'Room name is required.'; }

    if (!$errors) {
        // handle photo removals
        if ($room && !empty($_POST['remove_photos'])) {
            foreach ($_POST['remove_photos'] as $path) {
                $full = UPLOADS_PATH . '/rooms/' . basename($path);
                if (is_file($full)) @unlink($full);
                $photos = array_values(array_filter($photos, static fn ($p) => $p['path'] !== $path));
            }
        }
        // apply name / alt text edits for existing photos (keyed by path)
        $photoNames = $_POST['photo_name'] ?? [];
        $photoAlts = $_POST['photo_alt'] ?? [];
        foreach ($photos as &$p) {
            if (array_key_exists($p['path'], $photoNames)) $p['name'] = trim($photoNames[$p['path']]) ?: null;
            if (array_key_exists($p['path'], $photoAlts)) $p['alt'] = trim($photoAlts[$p['path']]) ?: null;
        }
        unset($p);
        // handle new uploads
        if (!empty($_FILES['photos']['name'][0])) {
            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if (!is_uploaded_file($tmp)) continue;
                $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) continue;
                $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                if (move_uploaded_file($tmp, UPLOADS_PATH . '/rooms/' . $filename)) {
                    $photos[] = ['path' => $filename, 'name' => null, 'alt' => null];
                }
            }
        }

        $photosJson = json_encode(array_values($photos));

        if ($room) {
            db_run('UPDATE rooms SET name=?, size=?, bed_type=?, max_guests=?, total_count=?, show_price=?, note=?, photos=? WHERE id=?',
                [$name, $size, $bedType, $maxGuests, $totalCount, $showPrice, $note ?: null, $photosJson, $room['id']]);
            log_activity('room.updated', "Updated room {$name}", 'room', $room['id']);
            flash('success', "{$name} updated.");
        } else {
            $slug = unique_room_slug($name);
            db_insert('INSERT INTO rooms (slug, name, size, bed_type, max_guests, show_price, note, photos, total_count, rooms_left, available) VALUES (?,?,?,?,?,?,?,?,?,?,1)',
                [$slug, $name, $size, $bedType, $maxGuests, $showPrice, $note ?: null, $photosJson, $totalCount, $totalCount]);
            log_activity('room.created', "Added room category \"{$name}\"");
            flash('success', "{$name} added.");
        }
        redirect('admin/rooms.php');
    }
}

$title = $room ? 'Edit ' . $room['name'] : 'Add Room Category';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8 max-w-3xl mx-auto">
    <a href="<?= e(APP_URL) ?>/admin/rooms.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to rooms</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2"><?= $room ? 'Edit ' . e($room['name']) : 'Add Room Category' ?></h1>
    <?php if (!$room): ?><p class="text-sm text-pallav-500 mt-1">Create a new room type — it appears on the homepage automatically once saved.</p><?php endif; ?>
  </div>

  <?php foreach ($errors as $err): ?>
    <div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold max-w-3xl mx-auto"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="POST" enctype="multipart/form-data" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-3xl mx-auto">
    <?= csrf_field() ?>
    <div class="grid sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room Name</label>
        <input type="text" name="name" value="<?= e($room['name'] ?? '') ?>" required placeholder="e.g. Executive Suite" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Bed Type</label>
        <input type="text" name="bed_type" value="<?= e($room['bed_type'] ?? '') ?>" placeholder="e.g. Queen Bed" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Size</label>
        <input type="text" name="size" value="<?= e($room['size'] ?? '') ?>" placeholder="e.g. 160 sq.ft" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Max Guests</label>
        <input type="number" name="max_guests" min="1" max="20" value="<?= e((string) ($room['max_guests'] ?? 2)) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Total Rooms in this Category</label>
        <input type="number" name="total_count" min="1" max="255" value="<?= e((string) ($room['total_count'] ?? 1)) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        <p class="text-[11px] text-pallav-400 mt-1">How many physical rooms of this type exist. Used for the "Rooms" count shown on the website.</p>
      </div>
    </div>

    <div class="mt-5">
      <label class="flex items-center gap-2 text-sm font-bold text-pallav-700">
        <input type="checkbox" name="show_price" value="1" <?= !empty($room['show_price']) ? 'checked' : '' ?> class="rounded border-pallav-300 text-pallav-600 w-4 h-4 focus:ring-pallav-400">
        Show price publicly
      </label>
      <p class="text-[11px] text-pallav-400 mt-1">Inventory, availability and pricing are all managed under Pricing &amp; Rates once this category is created.</p>
    </div>

    <div class="mt-5">
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Note shown on site <span class="normal-case font-semibold text-pallav-300">(optional)</span></label>
      <input type="text" name="note" value="<?= e($room['note'] ?? '') ?>" placeholder="e.g. Currently under renovation until March" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
    </div>

    <div class="mt-5">
      <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-2">Room Photos</label>
      <p class="text-xs text-pallav-400 mb-3">First photo shows first in the site's photo slider. Give each one a name and alt text (for SEO / accessibility) — same as Gallery.</p>
      <?php if ($photos): ?>
      <div class="space-y-3 mb-4">
        <?php foreach ($photos as $photo): ?>
        <div class="flex gap-3 items-start rounded-xl ring-1 ring-pallav-100 p-3">
          <div class="relative shrink-0 w-20 h-20 group">
            <img src="<?= e(UPLOADS_URL . '/rooms/' . $photo['path']) ?>" class="w-full h-full object-cover rounded-lg ring-1 ring-pallav-100">
            <label class="absolute inset-0 rounded-lg bg-rose-900/0 group-hover:bg-rose-900/50 flex items-center justify-center transition cursor-pointer">
              <input type="checkbox" name="remove_photos[]" value="<?= e($photo['path']) ?>" class="hidden peer">
              <span class="opacity-0 group-hover:opacity-100 peer-checked:opacity-100 peer-checked:bg-rose-600 text-white text-[9px] font-extrabold uppercase tracking-wide bg-rose-500/90 rounded-full px-2 py-0.5 transition">Remove</span>
            </label>
          </div>
          <div class="flex-1 min-w-0 space-y-1.5">
            <input type="text" name="photo_name[<?= e($photo['path']) ?>]" value="<?= e($photo['name'] ?? '') ?>" placeholder="Image name" class="w-full rounded-lg border border-pallav-200 px-3 py-1.5 text-xs font-semibold focus:border-pallav-500 outline-none">
            <input type="text" name="photo_alt[<?= e($photo['path']) ?>]" value="<?= e($photo['alt'] ?? '') ?>" placeholder="Alt text (for SEO / accessibility)" class="w-full rounded-lg border border-pallav-200 px-3 py-1.5 text-xs font-semibold focus:border-pallav-500 outline-none">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <input type="file" name="photos[]" multiple accept="image/*" class="block w-full text-sm text-pallav-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pallav-100 file:text-pallav-700 hover:file:bg-pallav-200">
    </div>

    <div class="flex justify-end gap-3 pt-6">
      <a href="<?= e(APP_URL) ?>/admin/rooms.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition"><?= $room ? 'Save Changes' : 'Create Room' ?></button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
