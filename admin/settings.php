<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

$settings = get_settings();
$logoUrl = $settings['logo_path'] ? UPLOADS_URL . '/' . $settings['logo_path'] : null;
$faviconUrl = $settings['favicon_path'] ? UPLOADS_URL . '/' . $settings['favicon_path'] : null;

$title = 'Settings';
include __DIR__ . '/../includes/admin-layout-top.php';
?>
  <div class="mb-8">
    <h1 class="font-display text-2xl sm:text-3xl font-bold text-pallav-900">Site Settings</h1>
    <p class="text-sm text-pallav-500 mt-1">Everything here controls what guests see on the public website.</p>
  </div>

  <form method="POST" action="<?= e(APP_URL) ?>/admin/settings-save.php" enctype="multipart/form-data" class="space-y-6 max-w-4xl">
    <?= csrf_field() ?>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <div class="flex items-center gap-2.5 mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">Branding</h2>
        <span class="text-[10px] font-extrabold uppercase tracking-wide bg-pallav-100 text-pallav-700 rounded-full px-2.5 py-1">Logo &amp; Favicon</span>
      </div>
      <p class="text-xs text-pallav-400 mb-5">Upload your own logo to replace the default monogram everywhere — website header, admin panel, login screen. Upload a favicon for the browser tab.</p>

      <div class="grid sm:grid-cols-2 gap-6">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-2">Site Logo</label>
          <div class="flex items-center gap-4 mb-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden ring-1 ring-pallav-100 bg-pallav-50 flex items-center justify-center shrink-0">
              <?php if ($logoUrl): ?>
                <img src="<?= e($logoUrl) ?>" class="w-full h-full object-contain">
              <?php else: render_brand_mark(64); endif; ?>
            </div>
            <div class="text-xs text-pallav-400">
              <?php if ($logoUrl): ?>
                <label class="flex items-center gap-1.5 font-bold text-rose-500 hover:text-rose-700 cursor-pointer">
                  <input type="checkbox" name="remove_logo" value="1" class="rounded border-pallav-300 w-3.5 h-3.5">
                  Remove current logo &amp; use default
                </label>
              <?php else: ?>
                Using the default monogram — upload a PNG/SVG to replace it.
              <?php endif; ?>
            </div>
          </div>
          <input type="file" name="logo" accept="image/*" class="block w-full text-sm text-pallav-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pallav-100 file:text-pallav-700 hover:file:bg-pallav-200">
          <p class="text-[11px] text-pallav-400 mt-1.5">Square image recommended, transparent PNG or SVG works best.</p>
        </div>

        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-2">Favicon</label>
          <div class="flex items-center gap-4 mb-3">
            <div class="w-16 h-16 rounded-xl overflow-hidden ring-1 ring-pallav-100 bg-pallav-50 flex items-center justify-center shrink-0">
              <?php if ($faviconUrl): ?>
                <img src="<?= e($faviconUrl) ?>" class="w-10 h-10 object-contain">
              <?php else: ?>
                <span class="text-[10px] font-bold text-pallav-400 text-center px-1">No favicon set</span>
              <?php endif; ?>
            </div>
            <div class="text-xs text-pallav-400">
              <?php if ($faviconUrl): ?>
                <label class="flex items-center gap-1.5 font-bold text-rose-500 hover:text-rose-700 cursor-pointer">
                  <input type="checkbox" name="remove_favicon" value="1" class="rounded border-pallav-300 w-3.5 h-3.5">
                  Remove favicon
                </label>
              <?php else: ?>
                Shown in the browser tab for the website.
              <?php endif; ?>
            </div>
          </div>
          <input type="file" name="favicon" accept="image/png,image/x-icon,image/svg+xml" class="block w-full text-sm text-pallav-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-pallav-100 file:text-pallav-700 hover:file:bg-pallav-200">
          <p class="text-[11px] text-pallav-400 mt-1.5">Square PNG or ICO, at least 32×32px.</p>
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-5">Contact &amp; Hotel Info</h2>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Opened Year</label>
          <input type="number" name="opened_year" value="<?= e((string) $settings['opened_year']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Email</label>
          <input type="email" name="email" value="<?= e($settings['email']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">GM Phone (tel: format)</label>
          <input type="text" name="gm_phone" value="<?= e($settings['gm_phone']) ?>" placeholder="+919825735404" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Reception Phone</label>
          <input type="text" name="reception_phone" value="<?= e($settings['reception_phone']) ?>" placeholder="+917043535404" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">WhatsApp (digits only, with country code)</label>
          <input type="text" name="whatsapp" value="<?= e($settings['whatsapp']) ?>" placeholder="919825735404" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Google Business Profile Link</label>
          <input type="url" name="gbp_link" value="<?= e($settings['gbp_link'] ?? '') ?>" placeholder="https://g.page/..." class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Facebook Page Link</label>
          <input type="url" name="facebook_link" value="<?= e($settings['facebook_link'] ?? '') ?>" placeholder="https://facebook.com/yourpage" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Instagram Link</label>
          <input type="url" name="instagram_link" value="<?= e($settings['instagram_link'] ?? '') ?>" placeholder="https://instagram.com/yourpage" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Address</label>
          <input type="text" name="address" value="<?= e($settings['address']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-in Time</label>
          <input type="text" name="checkin_time" value="<?= e($settings['checkin_time']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Check-out Time</label>
          <input type="text" name="checkout_time" value="<?= e($settings['checkout_time']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <div class="flex items-center gap-2.5 mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">Google Maps &amp; Live Reviews</h2>
        <?php if ($settings['google_maps_api_key'] && $settings['google_place_id']): ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1">Live</span>
        <?php else: ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-amber-50 text-amber-700 rounded-full px-2.5 py-1">Not connected</span>
        <?php endif; ?>
      </div>
      <p class="text-xs text-pallav-400 mb-5">Add these two values to show a real interactive map and pull your actual Google reviews live onto the homepage. Without them, the site falls back to the placeholder map and sample reviews.</p>
      <div class="rounded-xl bg-pallav-50 ring-1 ring-pallav-100 p-4 text-xs text-pallav-600 mb-5 leading-relaxed">
        <b class="text-pallav-800">How to get these:</b>
        <ol class="list-decimal pl-4 mt-1.5 space-y-1">
          <li>Go to <a href="https://console.cloud.google.com/google/maps-apis" target="_blank" class="font-bold text-pallav-700 underline">Google Cloud Console</a>, create a project, and enable <b>"Maps Embed API"</b> and <b>"Places API"</b>.</li>
          <li>Under Credentials, create an API key — that's your <b>Maps API Key</b> below.</li>
          <li>Find your <b>Place ID</b> using Google's <a href="https://developers.google.com/maps/documentation/places/web-service/place-id" target="_blank" class="font-bold text-pallav-700 underline">Place ID Finder</a> — search "Hotel Pallav Rajkot" and copy the ID shown.</li>
        </ol>
      </div>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Google Maps API Key</label>
          <input type="text" name="google_maps_api_key" value="<?= e($settings['google_maps_api_key'] ?? '') ?>" placeholder="AIzaSy..." class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Google Place ID</label>
          <input type="text" name="google_place_id" value="<?= e($settings['google_place_id'] ?? '') ?>" placeholder="ChIJ..." class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Minimum Star Rating Shown</label>
          <select name="google_min_review_rating" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
            <?php for ($n = 1; $n <= 5; $n++): ?>
              <option value="<?= $n ?>" <?= (int) $settings['google_min_review_rating'] === $n ? 'selected' : '' ?>><?= $n ?> star<?= $n > 1 ? 's' : '' ?> &amp; above</option>
            <?php endfor; ?>
          </select>
          <p class="text-[11px] text-pallav-400 mt-1">Reviews below this rating are hidden from the site. Google only ever returns up to 5 reviews total, so this filters within that set.</p>
        </div>
      </div>
    </div>

    <?php require_once __DIR__ . '/../includes/gbp.php'; ?>
    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <div class="flex items-center gap-2.5 mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">Full Review History <span class="font-normal text-pallav-400">(all reviews, not just 5)</span></h2>
        <?php if (gbp_is_connected()): ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1">Connected</span>
        <?php else: ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-amber-50 text-amber-700 rounded-full px-2.5 py-1">Not connected</span>
        <?php endif; ?>
      </div>
      <p class="text-xs text-pallav-400 mb-5">The Places API above is capped at 5 reviews by Google — no way around that. Showing every review requires connecting your actual Google Business Profile account below, which uses a separate, restricted Google API.</p>
      <div class="rounded-xl bg-amber-50 ring-1 ring-amber-100 p-4 text-xs text-amber-800 mb-5 leading-relaxed">
        <b>Before this will work</b>, your Google Cloud project needs approval for the "Google My Business API" — this is a manual review by Google, separate from just enabling an API, and can take time or be declined. See
        <a href="https://developers.google.com/my-business/content/prereqs" target="_blank" class="font-bold underline">Google's access request form</a>.
        You'll also need an OAuth 2.0 Client ID (Web application type) from
        <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="font-bold underline">Cloud Console → Credentials</a>,
        with this exact Authorized redirect URI:
        <code class="block mt-1 bg-white rounded-lg px-3 py-2 ring-1 ring-amber-200 select-all"><?= e(APP_URL . '/admin/gbp-callback.php') ?></code>
      </div>
      <div class="grid sm:grid-cols-2 gap-5 mb-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">OAuth Client ID</label>
          <input type="text" name="gbp_oauth_client_id" value="<?= e($settings['gbp_oauth_client_id'] ?? '') ?>" placeholder="xxxxx.apps.googleusercontent.com" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">OAuth Client Secret</label>
          <input type="password" name="gbp_oauth_client_secret" value="<?= e($settings['gbp_oauth_client_secret'] ?? '') ?>" placeholder="GOCSPX-..." class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
      <p class="text-[11px] text-pallav-400 mb-4">Save this form first after entering the Client ID/Secret above, then use Connect below — it needs those saved to build the Google login link.</p>
      <div class="flex items-center gap-3 flex-wrap">
        <?php if (gbp_is_connected()): ?>
          <a href="<?= e(APP_URL) ?>/admin/gbp-connect.php?action=refresh" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-pallav-50 text-pallav-700 hover:bg-pallav-100 transition">Refresh All Reviews Now</a>
          <a href="<?= e(APP_URL) ?>/admin/gbp-connect.php?action=disconnect" class="px-5 py-2.5 rounded-xl text-sm font-bold text-rose-600 hover:bg-rose-50 transition" onclick="return confirm('Disconnect Google Business Profile? The site will fall back to the 5-review Places API display.')">Disconnect</a>
        <?php elseif (gbp_is_configured()): ?>
          <a href="<?= e(APP_URL) ?>/admin/gbp-connect.php" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Connect Google Business Profile</a>
        <?php else: ?>
          <span class="text-xs text-pallav-400 font-semibold">Save your Client ID/Secret above first to enable the Connect button.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <div class="flex items-center gap-2.5 mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">SEO</h2>
        <span class="text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1">Search visibility</span>
      </div>
      <p class="text-xs text-pallav-400 mb-5">Controls how the homepage appears in Google search results and when shared on social media.</p>
      <div class="space-y-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Meta Title <span class="normal-case font-semibold text-pallav-300">(shown as the browser tab / search result title, ~60 chars)</span></label>
          <input type="text" name="meta_title" maxlength="70" value="<?= e($settings['meta_title'] ?? '') ?>" placeholder="Hotel Pallav — Comfortable Stays in Rajkot" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Meta Description <span class="normal-case font-semibold text-pallav-300">(the snippet under the title in search results, ~155 chars)</span></label>
          <textarea name="meta_description" maxlength="200" rows="2" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none"><?= e($settings['meta_description'] ?? '') ?></textarea>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Meta Keywords <span class="normal-case font-semibold text-pallav-300">(comma separated)</span></label>
          <input type="text" name="meta_keywords" value="<?= e($settings['meta_keywords'] ?? '') ?>" placeholder="hotel rajkot, deluxe rooms rajkot, kalavad road hotel" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <div class="flex items-center gap-2.5 mb-1">
        <h2 class="font-display font-bold text-lg text-pallav-900">Email Notifications</h2>
        <?php if ($settings['smtp_host'] ?? null): ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-emerald-50 text-emerald-700 rounded-full px-2.5 py-1">Configured</span>
        <?php else: ?>
          <span class="text-[10px] font-extrabold uppercase tracking-wide bg-amber-50 text-amber-700 rounded-full px-2.5 py-1">Not configured</span>
        <?php endif; ?>
      </div>
      <p class="text-xs text-pallav-400 mb-5">Send password-reset emails, booking/enquiry alerts to you, and booking confirmations to guests — using Gmail SMTP.</p>
      <div class="rounded-xl bg-pallav-50 ring-1 ring-pallav-100 p-4 text-xs text-pallav-600 mb-5 leading-relaxed">
        <b class="text-pallav-800">Using Gmail:</b> enable 2-Step Verification on the Google account, then create an
        <a href="https://myaccount.google.com/apppasswords" target="_blank" class="font-bold text-pallav-700 underline">App Password</a>
        (Google no longer accepts your normal Gmail password here). Use <code class="bg-white rounded px-1.5 py-0.5 ring-1 ring-pallav-200">smtp.gmail.com</code>, port <code class="bg-white rounded px-1.5 py-0.5 ring-1 ring-pallav-200">587</code>, and the 16-character App Password below.
      </div>
      <div class="grid sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">SMTP Host</label>
          <input type="text" name="smtp_host" value="<?= e($settings['smtp_host'] ?? '') ?>" placeholder="smtp.gmail.com" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">SMTP Port</label>
          <input type="number" name="smtp_port" value="<?= e((string) ($settings['smtp_port'] ?? 587)) ?>" placeholder="587" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Gmail Address</label>
          <input type="email" name="smtp_username" value="<?= e($settings['smtp_username'] ?? '') ?>" placeholder="yourhotel@gmail.com" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">App Password</label>
          <input type="password" name="smtp_password" value="" placeholder="<?= !empty($settings['smtp_password']) ? 'Saved — leave blank to keep it' : '16-character app password' ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">"From" Email</label>
          <input type="email" name="smtp_from_email" value="<?= e($settings['smtp_from_email'] ?? '') ?>" placeholder="yourhotel@gmail.com" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <p class="text-[11px] text-pallav-400 mt-1">Usually the same as the Gmail address above.</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">"From" Name</label>
          <input type="text" name="smtp_from_name" value="<?= e($settings['smtp_from_name'] ?? '') ?>" placeholder="<?= e(APP_NAME) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Notify This Email (bookings &amp; enquiries)</label>
          <input type="email" name="notify_email" value="<?= e($settings['notify_email'] ?? '') ?>" placeholder="<?= e($settings['email'] ?? '') ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
          <p class="text-[11px] text-pallav-400 mt-1">Where you'll receive new booking and enquiry alerts. Leave blank to use the hotel Email above.</p>
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-white ring-1 ring-pallav-100 shadow-sm p-6 sm:p-8">
      <h2 class="font-display font-bold text-lg text-pallav-900 mb-5">Reviews &amp; Display</h2>
      <div class="grid sm:grid-cols-3 gap-5">
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Google Rating</label>
          <input type="text" name="google_rating" value="<?= e($settings['google_rating']) ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div>
          <label class="block text-xs font-bold text-pallav-500 uppercase tracking-wide mb-1.5">Google Review Count</label>
          <input type="number" name="google_review_count" value="<?= (int) $settings['google_review_count'] ?>" class="w-full rounded-xl border border-pallav-200 px-4 py-2.5 text-sm font-semibold focus:border-pallav-500 focus:ring-4 focus:ring-pallav-100 outline-none">
        </div>
        <div class="flex items-end pb-2.5">
          <label class="flex items-center gap-2 text-sm font-bold text-pallav-700">
            <input type="checkbox" name="show_prices" value="1" <?= $settings['show_prices'] ? 'checked' : '' ?> class="rounded border-pallav-300 text-pallav-600 w-4 h-4 focus:ring-pallav-400">
            Show room prices publicly
          </label>
        </div>
      </div>
    </div>

    <div class="rounded-2xl bg-pallav-50 ring-1 ring-pallav-100 p-6 sm:p-8 flex items-center justify-between gap-4">
      <div>
        <h2 class="font-display font-bold text-lg text-pallav-900">House Policies</h2>
        <p class="text-xs text-pallav-500 mt-1">Managed on their own page now — add as many policy cards as you like.</p>
      </div>
      <a href="<?= e(APP_URL) ?>/admin/policies.php" class="shrink-0 inline-flex items-center gap-2 rounded-xl bg-white ring-1 ring-pallav-200 text-pallav-700 text-sm font-bold px-5 py-2.5 hover:bg-pallav-100 transition">
        Manage Policies &rarr;
      </a>
    </div>

    <div class="flex justify-end gap-3">
      <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pallav-600 to-pallav-800 text-white text-sm font-bold shadow-lg shadow-pallav-900/15 hover:-translate-y-0.5 transition">Save Settings</button>
    </div>
  </form>
<?php include __DIR__ . '/../includes/admin-layout-bottom.php'; ?>
