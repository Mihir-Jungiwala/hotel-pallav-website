<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$enquiry = db_one('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$enquiry) { flash('error', 'Enquiry not found.'); redirect('admin/bookings.php'); }
$rooms = db_all('SELECT id, name FROM rooms ORDER BY name');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rawPhone = trim($_POST['phone'] ?? '');
    // Same international-friendly normalization as the booking form/edit page: a
    // bare 10-digit number is assumed Indian (default +91), any other country code
    // + length is accepted too, always saved with a leading +.
    $hasPlus = isset($rawPhone[0]) && $rawPhone[0] === '+';
    $phoneDigits = preg_replace('/\D/', '', $rawPhone);
    if (!$hasPlus && strlen($phoneDigits) === 10) $phoneDigits = '91' . $phoneDigits;

    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'phone' => $phoneDigits !== '' ? '+' . $phoneDigits : '',
        'email' => trim($_POST['email'] ?? '') ?: null,
        'room_id' => (int) ($_POST['room_id'] ?? 0),
        'check_in' => $_POST['check_in'] ?? '',
        'check_out' => $_POST['check_out'] ?? '',
        'guests' => max(1, (int) ($_POST['guests'] ?? 1)),
        'message' => trim($_POST['message'] ?? ''),
    ];

    if ($data['name'] === '' || mb_strlen($data['name']) < 2) $errors[] = 'Please enter the guest name.';
    if (strlen($phoneDigits) < 7 || strlen($phoneDigits) > 15) $errors[] = 'Please enter a valid phone number with country code.';
    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'That email address does not look right.';
    if (!strtotime($data['check_in']) || !strtotime($data['check_out'])) $errors[] = 'Please pick a check-in and check-out date.';
    elseif ($data['check_out'] < $data['check_in']) $errors[] = 'Check-out must be on or after check-in.';
    if ($data['message'] === '') $errors[] = 'Please enter the enquiry message.';

    if (!$errors) {
        db_run('UPDATE enquiries SET name=?, phone=?, email=?, room_id=?, check_in=?, check_out=?, guests=?, message=? WHERE id=?',
            [$data['name'], $data['phone'], $data['email'], $data['room_id'] ?: null, $data['check_in'], $data['check_out'], $data['guests'], $data['message'], $id]);
        log_activity('enquiry.edited', "Edited enquiry {$enquiry['reference']} for {$enquiry['name']}", 'enquiry', $id);
        flash('success', "Enquiry {$enquiry['reference']} updated.");
        redirect('admin/bookings.php');
    }
}

$title = 'Edit Enquiry ' . $enquiry['reference'];
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="max-w-2xl mx-auto">
    <div class="mb-8">
      <a href="<?= e(APP_URL) ?>/admin/bookings.php" class="text-xs font-bold text-pallav-500 hover:text-pallav-700">Back to Guest Activity</a>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900 mt-2">Edit Enquiry <span class="text-xl text-pallav-500"><?= e($enquiry['reference']) ?></span></h1>
    </div>

    <?php foreach ($errors as $err): ?><div class="mb-6 rounded-xl bg-rose-50 text-rose-700 ring-1 ring-rose-200 px-5 py-3.5 text-sm font-semibold"><?= e($err) ?></div><?php endforeach; ?>

    <form method="POST" id="enquiryEditForm" class="edit-form rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="grid sm:grid-cols-2 gap-5">
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Guest Name</label><input id="ee-name" type="text" name="name" value="<?= e($enquiry['name']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Mobile Number</label><input id="ee-phone" type="tel" inputmode="tel" name="phone" maxlength="20" value="<?= e($enquiry['phone'] ?: '+91 ') ?>" placeholder="+91 98765 43210" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
        <div class="sm:col-span-2"><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email <span class="normal-case font-semibold text-pallav-300">(optional)</span></label><input id="ee-email" type="email" name="email" value="<?= e($enquiry['email'] ?? '') ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room</label>
          <select name="room_id" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
            <?php foreach ($rooms as $r): ?><option value="<?= $r['id'] ?>" <?= $enquiry['room_id'] == $r['id'] ? 'selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Guests</label><input id="ee-guests" type="number" name="guests" min="1" max="20" value="<?= (int) ($enquiry['guests'] ?: 1) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-in</label><input id="ee-checkin" type="date" name="check_in" value="<?= e($enquiry['check_in'] ?? '') ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
        <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-out</label><input id="ee-checkout" type="date" name="check_out" value="<?= e($enquiry['check_out'] ?? '') ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"></div>
      </div>
      <div><label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Message</label><textarea id="ee-message" name="message" rows="4" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"><?= e($enquiry['message'] ?? '') ?></textarea></div>
      <p id="ee-error" class="hidden text-sm font-semibold text-rose-600"></p>
      <div class="flex justify-end gap-3 pt-2">
        <a href="<?= e(APP_URL) ?>/admin/bookings.php" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-pallav-50 transition">Cancel</a>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Changes</button>
      </div>
    </form>
  </div>

  <script>
  (function(){
    var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var form = document.getElementById('enquiryEditForm');
    var errorEl = document.getElementById('ee-error');
    var phone = document.getElementById('ee-phone');

    // Same international-friendly typing filter as the public site's booking form -
    // keep a single leading + (if present) plus digits and spaces, nothing else.
    phone.addEventListener('input', function(){
      var v = phone.value;
      var plus = v.charAt(0) === '+' ? '+' : '';
      phone.value = plus + v.slice(plus.length).replace(/[^\d ]/g, '');
    });

    function showError(text){ errorEl.textContent = text; errorEl.classList.remove('hidden'); }

    form.addEventListener('submit', function(e){
      errorEl.classList.add('hidden');
      var name = document.getElementById('ee-name');
      var email = document.getElementById('ee-email');
      var checkin = document.getElementById('ee-checkin');
      var checkout = document.getElementById('ee-checkout');
      var message = document.getElementById('ee-message');

      if (name.value.trim().length < 2) { e.preventDefault(); showError('Please enter the guest name.'); name.focus(); return; }

      var hasPlus = phone.value.trim().charAt(0) === '+';
      var digits = phone.value.replace(/\D/g, '');
      if (!hasPlus && digits.length === 10) digits = '91' + digits;
      if (digits.length < 7 || digits.length > 15) { e.preventDefault(); showError('Please enter a valid phone number with country code.'); phone.focus(); return; }
      phone.value = '+' + digits;

      if (email.value.trim() !== '' && !EMAIL_RE.test(email.value.trim())) { e.preventDefault(); showError('That email address does not look right. Leave it blank if you prefer.'); email.focus(); return; }
      if (!checkin.value) { e.preventDefault(); showError('Please pick a check-in date.'); checkin.focus(); return; }
      if (!checkout.value) { e.preventDefault(); showError('Please pick a check-out date.'); checkout.focus(); return; }
      if (checkout.value < checkin.value) { e.preventDefault(); showError('Check-out must be on or after check-in.'); checkout.focus(); return; }
      if (message.value.trim() === '') { e.preventDefault(); showError('Please enter the enquiry message.'); message.focus(); return; }
    });
  })();
  </script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
