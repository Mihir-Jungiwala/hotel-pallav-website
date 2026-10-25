# Google Analytics 4 and Google Search Console

Added 25 October 2026. Both are **off until an ID is pasted** in Admin > Settings. With empty fields the site behaves exactly as before: no Google script, no banner, no meta tag.

## What was added

| File | What |
| --- | --- |
| `migrate-analytics.sql` | Two new columns on `settings`: `ga4_measurement_id`, `gsc_verification` (also in `database.sql` and `migrate-all.sql`) |
| `includes/analytics.php` | The Search Console meta tag, GA4 (gtag.js with Consent Mode v2), the cookie banner and the hotel events |
| `index.php`, `privacy-policy.php`, `terms-conditions.php`, `404.php` | Call the helper in their `<head>`. These are the only pages that get it: nothing in `admin/`, and not the login, forgot-password or reset-password pages |
| `admin/settings.php`, `admin/settings-save.php` | New card **Google Analytics & Search Console**, with server-side validation |
| `privacy-policy.php` | New section **Analytics and cookies** (shown only while GA4 is on) with a "Change my cookie choice" button |
| `sitemap.xml` | Privacy policy and terms pages added; lastmod 2026-10-25 |

No build step is needed. The banner's CSS and JS are inline in `includes/analytics.php`, so `bin/build-assets.sh` does not need to run.

## 1. Run the SQL (once)

In phpMyAdmin > the site database > **SQL** tab, run each line of `migrate-analytics.sql` separately:

```sql
ALTER TABLE settings ADD COLUMN ga4_measurement_id VARCHAR(20) NULL;
ALTER TABLE settings ADD COLUMN gsc_verification VARCHAR(120) NULL;
ALTER TABLE settings ADD COLUMN ga4_property_id VARCHAR(20) NULL;
ALTER TABLE settings ADD COLUMN gsc_property VARCHAR(255) NULL;
```

If a line answers "Duplicate column name", it already ran, so skip it.

The code is safe to deploy before the SQL runs. The settings card then says "Needs update" and the rest of Settings still saves normally.

## 2. Paste the IDs

Admin > **Settings** > **Google Analytics & Search Console** > Save Settings.

- **GA4 Measurement ID**: Google Analytics > Admin > Data streams > your web stream. It looks like `G-XXXXXXXXXX`. It is trimmed and uppercased; anything that is not `G-` followed by 4 to 15 letters or digits is refused.
- **Search Console Verification Code**: Search Console > Add property > **URL prefix** > **HTML tag**. Paste only the `content="..."` value, or the whole `<meta ...>` tag (the value is extracted). If you verify with the **Domain** property (a DNS TXT record), leave this empty.

- **GA4 Property Number** (for the report links): the number under the property's name in Google Analytics' property list, e.g. `516209206` (a pasted Google Analytics page address works too).
- **Search Console Property** (for the report links): `hotelpallav.in` for a Domain property, or `https://hotelpallav.in/` for a URL-prefix property (a pasted Search Console page address works too).

With those two set, **Admin > Dashboard > Website Reports** (and a row of links under the Settings card) opens the Google report pages directly in a new tab: Analytics Overview, Realtime and All reports; Search Console Performance, Pages and Sitemaps. Google asks for the sign-in of an account that has access to the property.

If a value is invalid, an error is shown and nothing is saved. Emptying a field switches that part off.

## 3. Verify

1. **Search Console**: after saving the code, press **Verify** in Search Console. The tag `<meta name="google-site-verification" ...>` is in the `<head>` of the homepage (view source to check). Keep the code saved afterwards, because Google re-checks it.
2. **Sitemap**: Search Console > **Sitemaps** > enter `sitemap.xml` > Submit. `robots.txt` already points to it.
3. **GA4**: open https://hotelpallav.in in a private window, press **Accept** on the banner, then open Google Analytics > Reports > **Realtime**. You should appear within a minute. Test the events: press a Call or WhatsApp option, a directions button, or send a test enquiry.
   - Before Accept (or after Decline), Google receives nothing at all, so those visits do not appear in GA. Expect GA numbers to be lower than the real number of visitors.
4. Optional: in GA4 > Admin > Events, mark `generate_lead` as a **key event** (conversion).

## How consent works

- Google Consent Mode v2 in **basic** mode. The defaults are set first: `analytics_storage`, `ad_storage`, `ad_user_data` and `ad_personalization` are all `denied` (`wait_for_update: 500`). `gtag.js` is **not loaded from Google** until the visitor presses Accept (or accepted earlier). Before a choice and after Decline nothing is sent: no page view, no event, no cookieless ping. This is what the privacy policy says.
- The page address is sent without its query string or `#` part (`page_location`, `page_referrer`), so a token in a mistyped link never reaches Google.
- `404.php` is the error page for every missing URL, including `admin/...` ones. It adds neither GA nor the Search Console tag when the path starts with `/admin/` or `/includes/`.
- A small bar at the bottom of the public pages reads "We use cookies to understand how visitors use our site." with a Privacy policy link and the buttons **Accept** and **Decline**. On phones it sits above the Call / WhatsApp / Enquire bar, so it never covers the booking button.
- **Accept** sets only `analytics_storage: granted`. The three ad signals always stay denied.
- **Decline** keeps everything denied, does not load GA, and deletes any `_ga` cookies left from an earlier Accept. If GA was already running on that page (Change my cookie choice, then Decline), it is switched off at once (`ga-disable-<ID>`).
- Events (`call_click`, `whatsapp_click`, `get_directions`, `generate_lead`) are only sent after Accept. `generate_lead` is also sent if the visitor presses Accept on the page that thanks them for the enquiry.
- On phones the bar leaves the right-hand column free, so it does not cover the back-to-top button.
- The choice is kept in the visitor's browser (`localStorage`: `pallav_consent` = `granted` / `denied`, plus `pallav_consent_at`) for **180 days**, then the banner asks again. No banner shows while a choice is remembered.
- On the homepage, the existing form-autosave cookie notice is answered first. The analytics bar appears only after it, so the two never stack.
- A visitor can change their mind on the privacy policy page (**Change my cookie choice**) or by clearing the site's data.

## Events sent (only when GA4 is on)

| Event | When |
| --- | --- |
| `call_click` | A `tel:` link is pressed (for the "choose GM or Reception" picker, the option chosen, not the button that opens it) |
| `whatsapp_click` | A `wa.me` / `api.whatsapp.com` link is pressed (same picker rule) |
| `get_directions` | Get Directions, Open in Maps, a Nearby Places route option, or any Google Maps directions link |
| `generate_lead` (`form: booking`) | Once, on the page shown right after a booking enquiry was sent successfully |

No event carries personal data: no names, phone numbers, emails or dates.

The site has one form, the booking enquiry (`book-submit.php`). There is no `enquire-submit.php`, so `form: enquiry` is supported by the helper but not used today.

## Content Security Policy

The site sends no CSP header today. If one is added later, allow `https://www.googletagmanager.com` (script) and `https://*.google-analytics.com`, `https://*.analytics.google.com` and `https://*.googletagmanager.com` (connect / img), and either allow the inline scripts or move them to a file.
