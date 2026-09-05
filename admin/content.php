<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$content = get_page_content();
$enquirePoints = json_decode_field($content['enquire_points'], []);

$title = 'Page Content';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Page Content</h1>
    <p class="text-sm text-pallav-500 mt-1">Edit the actual wording on the homepage - hero, about, enquiry section and footer.</p>
  </div>

  <?php
  // Saving submits this form as a real POST, so the browser leaves the page and comes
  // back - Alpine's in-memory `tab` state doesn't survive that round trip on its own,
  // which is why every save used to land back on Hero. Remembering the tab in
  // sessionStorage (cleared when the tab closes, unlike localStorage) means the
  // reload after a save reopens on whichever section was actually being edited.
  ?>
  <form method="POST" action="<?= e(APP_URL) ?>/admin/content-save.php" enctype="multipart/form-data"
        x-data="{ tab: sessionStorage.getItem('contentTab') || 'hero' }"
        x-init="$watch('tab', v => sessionStorage.setItem('contentTab', v))">
    <?= csrf_field() ?>

    <div class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 mb-6 overflow-x-auto no-scrollbar max-w-full">
      <button type="button" @click="tab='hero'" :class="tab==='hero' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Hero</button>
      <button type="button" @click="tab='checkavail'" :class="tab==='checkavail' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Check Availability</button>
      <button type="button" @click="tab='about'" :class="tab==='about' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">About</button>
      <button type="button" @click="tab='enquire'" :class="tab==='enquire' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Enquiry Section</button>
      <button type="button" @click="tab='formmsg'" :class="tab==='formmsg' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Form Messages</button>
      <button type="button" @click="tab='footer'" :class="tab==='footer' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Footer</button>
    </div>

    <div x-show="tab==='hero'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Hero Banner</h2>
      <p class="text-xs text-pallav-400 mb-4">The big purple section at the very top of the homepage.</p>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Headline - line 1</label>
          <input type="text" name="hero_title_line1" value="<?= e($content['hero_title_line1']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Headline - line 2 <span class="normal-case font-semibold text-pallav-300">(shown in gold italic)</span></label>
          <input type="text" name="hero_title_emphasis" value="<?= e($content['hero_title_emphasis']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Lead paragraph</label>
        <input type="hidden" name="hero_lead" value="<?= e($content['hero_lead'] ?? '') ?>">
        <div class="rte" data-target="hero_lead"></div>
      </div>
    </div>

    <div x-show="tab==='checkavail'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Check Availability</h2>
      <p class="text-xs text-pallav-400 mb-4">The white "Check availability" strip just under the hero, and the messages it shows once a guest checks dates.</p>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Bar title</label>
        <input type="text" name="quick_check_title" value="<?= e($content['quick_check_title'] ?? '') ?>" required maxlength="100" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div class="pt-4 mt-2 border-t border-pallav-100">
        <h3 class="font-display font-bold text-sm text-pallav-800 mb-1">Result Messages</h3>
        <p class="text-xs text-pallav-400 mb-4">Shown after a guest picks dates and clicks "Check Availability".</p>
        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">No dates picked</label>
            <input type="text" name="qc_msg_pick_dates" value="<?= e($content['qc_msg_pick_dates'] ?? '') ?>" required maxlength="200" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Rooms available</label>
            <input type="text" name="qc_msg_available" value="<?= e($content['qc_msg_available'] ?? '') ?>" required maxlength="200" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Rooms not available</label>
            <input type="text" name="qc_msg_unavailable" value="<?= e($content['qc_msg_unavailable'] ?? '') ?>" required maxlength="300" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Could not check (network/server error)</label>
            <input type="text" name="qc_msg_error" value="<?= e($content['qc_msg_error'] ?? '') ?>" required maxlength="200" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
      </div>
    </div>

    <div x-show="tab==='about'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">About / Our Story</h2>
      <p class="text-xs text-pallav-400 mb-4">The "Our Story" section with the years-of-service circle.</p>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Kicker label</label>
          <input type="text" name="about_kicker" value="<?= e($content['about_kicker']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Heading</label>
          <input type="text" name="about_heading" value="<?= e($content['about_heading']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Paragraph 1</label>
        <input type="hidden" name="about_p1" value="<?= e($content['about_p1'] ?? '') ?>">
        <div class="rte" data-target="about_p1"></div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Paragraph 2</label>
        <input type="hidden" name="about_p2" value="<?= e($content['about_p2'] ?? '') ?>">
        <div class="rte" data-target="about_p2"></div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Paragraph 3</label>
        <input type="hidden" name="about_p3" value="<?= e($content['about_p3'] ?? '') ?>">
        <div class="rte" data-target="about_p3"></div>
      </div>
      <p class="text-xs text-pallav-400">Note: years-open, room count and Google rating shown in this section are calculated automatically from Settings - no need to type numbers here.</p>
    </div>

    <div x-show="tab==='enquire'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Enquiry Section</h2>
      <p class="text-xs text-pallav-400 mb-4">The purple booking enquiry form section near the bottom of the page.</p>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Heading</label>
        <input type="text" name="enquire_heading" value="<?= e($content['enquire_heading']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Lead paragraph</label>
        <input type="hidden" name="enquire_lead" value="<?= e($content['enquire_lead'] ?? '') ?>">
        <div class="rte" data-target="enquire_lead"></div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Bullet points <span class="normal-case font-semibold text-pallav-300">(one per line)</span></label>
        <textarea name="enquire_points" rows="5" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"><?= e(implode("\n", $enquirePoints)) ?></textarea>
      </div>
    </div>

    <div x-show="tab==='formmsg'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Booking Enquiry Form Messages</h2>
      <p class="text-xs text-pallav-400 mb-4">Shown when a guest leaves a required field blank or types something invalid in the big booking enquiry form near the bottom of the homepage.</p>
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Name missing / too short</label>
          <input type="text" name="fm_msg_name" value="<?= e($content['fm_msg_name'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Invalid mobile number</label>
          <input type="text" name="fm_msg_phone" value="<?= e($content['fm_msg_phone'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Invalid email</label>
          <input type="text" name="fm_msg_email" value="<?= e($content['fm_msg_email'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-in missing</label>
            <input type="text" name="fm_msg_checkin" value="<?= e($content['fm_msg_checkin'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-out missing</label>
            <input type="text" name="fm_msg_checkout" value="<?= e($content['fm_msg_checkout'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Room missing</label>
          <input type="text" name="fm_msg_room" value="<?= e($content['fm_msg_room'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Adults missing</label>
            <input type="text" name="fm_msg_adults" value="<?= e($content['fm_msg_adults'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
          <div>
            <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Children missing</label>
            <input type="text" name="fm_msg_children" value="<?= e($content['fm_msg_children'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          </div>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">"Anything we should know" missing</label>
          <input type="text" name="fm_msg_message" value="<?= e($content['fm_msg_message'] ?? '') ?>" required maxlength="150" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
    </div>

    <div x-show="tab==='footer'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Footer</h2>
      <p class="text-xs text-pallav-400 mb-4">Contact details in the footer come from Settings - this is just the tagline under the logo.</p>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Footer tagline</label>
        <input type="hidden" name="footer_tagline" value="<?= e($content['footer_tagline'] ?? '') ?>">
        <div class="rte" data-target="footer_tagline"></div>
      </div>
      <?php // The bottom credit line ("© {year} Hotel Pallav. All rights reserved.") is fixed
      // and no longer editable here - it always shows the current year automatically. ?>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 bg-pallav-50 hover:bg-white transition hover:-translate-y-0.5">Preview Site</a>
      <?php if (can_edit_site()): ?>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save All Changes</button>
      <?php endif; ?>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
