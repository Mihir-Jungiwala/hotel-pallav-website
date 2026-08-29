<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$templates = [];
foreach (EMAIL_TEMPLATE_LABELS as $key => $label) {
    $templates[$key] = email_template($key);
}

$tokensByGroup = [
    'booking_received' => ['guest_name', 'reference', 'room_name', 'check_in', 'check_out', 'guests', 'guest_phone', 'hotel_name', 'reception_phone'],
    'booking_approved' => ['guest_name', 'reference', 'room_name', 'check_in', 'check_out', 'guests', 'hotel_name', 'reception_phone'],
    'booking_declined' => ['guest_name', 'reference', 'check_in', 'check_out', 'decision_note', 'hotel_name', 'reception_phone'],
    'enquiry_received' => ['guest_name', 'message', 'hotel_name', 'reception_phone'],
    'enquiry_confirmed' => ['guest_name', 'message', 'hotel_name', 'reception_phone'],
    'enquiry_declined' => ['guest_name', 'message', 'hotel_name', 'reception_phone'],
];

$descriptions = [
    'booking_received' => 'Sent to the guest the moment they submit a booking request.',
    'booking_approved' => 'Sent to the guest when you approve their booking.',
    'booking_declined' => 'Sent to the guest when you decline their booking.',
    'enquiry_received' => 'Sent to the guest the moment they submit a contact-page enquiry.',
    'enquiry_confirmed' => 'Sent to the guest when you confirm their enquiry.',
    'enquiry_declined' => 'Sent to the guest when you decline their enquiry.',
];

$title = 'Email Templates';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8 max-w-4xl mx-auto">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Email Templates</h1>
    <p class="text-sm text-pallav-500 mt-1">Customize the emails guests receive at each stage of a booking or enquiry. A copy of every email is also sent to the notify address set in Settings.</p>
  </div>

  <form method="POST" action="<?= e(APP_URL) ?>/admin/email-templates-save.php" class="max-w-4xl mx-auto space-y-6" x-data="{ tab: 'booking_received' }">
    <?= csrf_field() ?>

    <div class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 flex-wrap">
      <?php foreach (EMAIL_TEMPLATE_LABELS as $key => $label): ?>
        <button type="button" @click="tab = <?= e(json_encode($key)) ?>" :class="tab === <?= e(json_encode($key)) ?> ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>

    <?php foreach (EMAIL_TEMPLATE_LABELS as $key => $label): ?>
    <div x-show="tab === <?= e(json_encode($key)) ?>" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <div>
        <h2 class="font-display font-bold text-lg text-pallav-900 mb-1"><?= e($label) ?></h2>
        <p class="text-xs text-pallav-400"><?= e($descriptions[$key]) ?></p>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Subject <span class="normal-case font-semibold text-pallav-300">("Hotel Pallav — " is added automatically, no need to type it)</span></label>
        <input type="text" name="subject_<?= e($key) ?>" value="<?= e($templates[$key]['subject']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Body</label>
        <input type="hidden" name="body_<?= e($key) ?>" value="<?= e($templates[$key]['body']) ?>">
        <div class="rte" data-target="body_<?= e($key) ?>"></div>
      </div>
      <div class="rounded-xl bg-pallav-50 px-4 py-3">
        <div class="text-[10px] font-bold uppercase tracking-wide text-pallav-400 mb-1.5">Available placeholders — click to copy</div>
        <div class="flex flex-wrap gap-1.5">
          <?php foreach ($tokensByGroup[$key] as $token): ?>
            <button type="button" class="token-copy text-[11px] font-mono font-bold bg-white ring-1 ring-pallav-200 text-pallav-700 rounded-lg px-2 py-1 hover:bg-pallav-100 transition" data-token="{{<?= e($token) ?>}}">{{<?= e($token) ?>}}</button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <div class="flex justify-end gap-3">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Templates</button>
    </div>
  </form>

<script>
document.addEventListener('click', function(e){
  var btn = e.target.closest('.token-copy');
  if (!btn) return;
  navigator.clipboard && navigator.clipboard.writeText(btn.getAttribute('data-token')).catch(function(){});
  var original = btn.textContent;
  btn.textContent = 'Copied!';
  setTimeout(function(){ btn.textContent = original; }, 900);
});
</script>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
