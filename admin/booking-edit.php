<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$booking = db_one('SELECT * FROM bookings WHERE id = ?', [$id]);
if (!$booking) { flash('error', 'Booking not found.'); redirect('admin/bookings.php'); }
$rooms = db_all('SELECT id, name FROM rooms ORDER BY name');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = [
        'room_id' => (int) $_POST['room_id'],
        'guest_name' => trim($_POST['guest_name']),
        'guest_phone' => trim($_POST['guest_phone']),
        'guest_email' => trim($_POST['guest_email']) ?: null,
        'check_in' => $_POST['check_in'],
        'check_out' => $_POST['check_out'],
        'guests' => max(1, (int) $_POST['guests']),
        'message' => trim($_POST['message']) ?: null,
    ];

    if ($data['guest_name'] === '' || $data['guest_phone'] === '') $errors[] = 'Guest name and phone are required.';
    if ($data['check_out'] < $data['check_in']) $errors[] = 'Check-out must be on or after check-in.';

    if (!$errors) {
        db_run('UPDATE bookings SET room_id=?, guest_name=?, guest_phone=?, guest_email=?, check_in=?, check_out=?, guests=?, message=? WHERE id=?',
            [$data['room_id'], $data['guest_name'], $data['guest_phone'], $data['guest_email'], $data['check_in'], $data['check_out'], $data['guests'], $data['message'], $id]);
        log_activity('booking.edited', "Edited booking {$booking['reference']}", 'booking', $id);
        flash('success', "Booking {$booking['reference']} updated.");
        redirect('admin/bookings.php');
    }
}

$title = 'Edit Booking ' . $booking['reference'];
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <a href="<?= e(APP_URL) ?>/admin/bookings.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">&larr; Back to bookings</a>
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Edit Booking <span class="font-mono text-xl text-pallav-500"><?= e($booking['reference']) ?></span></h1>
  </div>

  <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold"><?= e($err) ?></div><?php endforeach; ?>

  <form method="POST" class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 max-w-2xl space-y-5">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="grid sm:grid-cols-2 gap-5">
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Guest Name</label><input type="text" name="guest_name" value="<?= e($booking['guest_name']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Phone</label><input type="text" name="guest_phone" value="<?= e($booking['guest_phone']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      <div class="sm:col-span-2"><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email <span class="normal-case font-semibold text-pallav-300">(optional)</span></label><input type="email" name="guest_email" value="<?= e($booking['guest_email'] ?? '') ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room</label>
        <select name="room_id" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>" <?= $booking['room_id'] == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Guests</label><input type="number" name="guests" min="1" max="20" value="<?= (int) $booking['guests'] ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-in</label><input type="date" name="check_in" value="<?= e($booking['check_in']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-out</label><input type="date" name="check_out" value="<?= e($booking['check_out']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
    </div>
    <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Guest Note <span class="normal-case font-semibold text-pallav-300">(optional)</span></label><textarea name="message" rows="3" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"><?= e($booking['message'] ?? '') ?></textarea></div>
    <div class="flex justify-end gap-3 pt-2">
      <a href="<?= e(APP_URL) ?>/admin/bookings.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Changes</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
