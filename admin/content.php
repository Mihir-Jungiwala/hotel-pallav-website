<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$content = get_page_content();
$services = json_decode_field($content['services'], []);
$enquirePoints = json_decode_field($content['enquire_points'], []);

$iconLabels = [
    'wifi' => 'High-Speed Wi-Fi', 'parking' => 'Parking', 'restaurant' => 'Restaurant',
    'front-desk' => 'Front Desk', 'power' => 'Power Backup', 'shield' => 'Safety / CCTV', 'laundry' => 'Laundry',
];

$title = 'Page Content';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Page Content</h1>
    <p class="text-sm text-pallav-500 mt-1">Edit the actual wording on the homepage — hero, about, services, enquiry section and footer.</p>
  </div>

  <form method="POST" action="<?= e(APP_URL) ?>/admin/content-save.php" x-data="{ tab: 'hero' }">
    <?= csrf_field() ?>

    <div class="flex gap-1.5 bg-white rounded-xl ring-1 ring-pallav-100 p-1.5 mb-6 overflow-x-auto max-w-full">
      <button type="button" @click="tab='hero'" :class="tab==='hero' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Hero</button>
      <button type="button" @click="tab='about'" :class="tab==='about' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">About</button>
      <button type="button" @click="tab='services'" :class="tab==='services' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Services</button>
      <button type="button" @click="tab='enquire'" :class="tab==='enquire' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Enquiry Section</button>
      <button type="button" @click="tab='footer'" :class="tab==='footer' ? 'bg-pallav-700 text-white' : 'text-pallav-500 hover:bg-pallav-50'" class="px-4 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap">Footer</button>
    </div>

    <div x-show="tab==='hero'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Hero Banner</h2>
      <p class="text-xs text-pallav-400 mb-4">The big purple section at the very top of the homepage.</p>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Eyebrow text <span class="normal-case font-semibold text-pallav-300">(appears after "24+ Years")</span></label>
        <input type="text" name="hero_eyebrow" value="<?= e($content['hero_eyebrow']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
      </div>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Headline — line 1</label>
          <input type="text" name="hero_title_line1" value="<?= e($content['hero_title_line1']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Headline — line 2 <span class="normal-case font-semibold text-pallav-300">(shown in gold italic)</span></label>
          <input type="text" name="hero_title_emphasis" value="<?= e($content['hero_title_emphasis']) ?>" required class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Lead paragraph</label>
        <input type="hidden" name="hero_lead" value="<?= e($content['hero_lead'] ?? '') ?>">
        <div class="rte" data-target="hero_lead"></div>
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
      <p class="text-xs text-pallav-400">Note: years-open, room count and Google rating shown in this section are calculated automatically from Settings — no need to type numbers here.</p>
    </div>

    <div x-show="tab==='services'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8" x-data="{ items: <?= e(json_encode($services)) ?> }">
      <div class="flex items-center justify-between mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">Services &amp; Facilities</h2>
        <button type="button" @click="items.push({icon:'wifi', title:'', desc:''})" class="text-xs font-bold bg-pallav-100 hover:bg-pallav-200 text-pallav-700 rounded-lg px-3 py-1.5 transition">+ Add Service</button>
      </div>
      <p class="text-xs text-pallav-400 mb-4">The card grid guests see under "Services & Facilities". Reorder by editing top to bottom.</p>

      <div class="space-y-3">
        <template x-for="(item, i) in items" :key="i">
          <div class="rounded-xl ring-1 ring-pallav-100 p-4 grid sm:grid-cols-[140px_1fr_1fr_auto] gap-3 items-start">
            <select :name="'service_icon['+i+']'" x-model="item.icon" class="rounded-lg border border-pallav-200 px-2.5 py-2 text-xs font-semibold focus:border-pallav-500 outline-none">
              <?php foreach ($iconLabels as $key => $label): ?>
                <option value="<?= e($key) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
            <input type="text" :name="'service_title['+i+']'" x-model="item.title" placeholder="Title" class="rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            <input type="text" :name="'service_desc['+i+']'" x-model="item.desc" placeholder="One-line description" class="rounded-lg border border-pallav-200 px-3 py-2 text-sm font-semibold focus:border-pallav-500 outline-none">
            <button type="button" @click="items.splice(i,1)" class="w-9 h-9 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center transition">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
          </div>
        </template>
      </div>
    </div>

    <div x-show="tab==='enquire'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Enquiry Section</h2>
      <p class="text-xs text-pallav-400 mb-4">The purple booking-form section near the bottom of the page.</p>
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

    <div x-show="tab==='footer'" x-cloak class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8 space-y-5">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-1">Footer</h2>
      <p class="text-xs text-pallav-400 mb-4">Contact details in the footer come from Settings — this is just the tagline under the logo.</p>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Footer tagline</label>
        <input type="hidden" name="footer_tagline" value="<?= e($content['footer_tagline'] ?? '') ?>">
        <div class="rte" data-target="footer_tagline"></div>
      </div>
      <div>
        <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Bottom Credit Line <span class="normal-case font-semibold text-pallav-300">(the very last line of the footer — copyright, developer credit, anything you want)</span></label>
        <input type="hidden" name="footer_credit" value="<?= e($content['footer_credit'] ?? '') ?>">
        <div class="rte" data-target="footer_credit"></div>
      </div>
    </div>

    <div class="flex justify-end gap-3 mt-6">
      <a href="<?= e(APP_URL) ?>/index.php" target="_blank" class="px-5 py-2.5 rounded-xl text-sm font-bold text-pallav-600 hover:bg-white transition">Preview site &rarr;</a>
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save All Changes</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
