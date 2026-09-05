#!/bin/bash
# Regenerates the minified assets served in production from their editable sources.
#
# The site is plain PHP with no build pipeline on the server - Hostinger never runs
# Node. These .min files are pre-built once here (wherever Node happens to be
# available - a laptop, this dev environment) and committed to the repo like any
# other static asset, then referenced directly by index.php/404.php.
#
# Run this EVERY TIME you edit assets/css/site.css, assets/js/site.js or
# assets/js/admin-pickers.js - the .min.* files are build output, not hand-edited,
# and will silently keep serving the old version to visitors otherwise.
#
# Requires Node/npm (only on the machine running this script, never in production).
set -euo pipefail
cd "$(dirname "$0")/.."

npx --yes clean-css-cli -o assets/css/site.min.css assets/css/site.css
npx --yes terser assets/js/site.js -c -m -o assets/js/site.min.js --comments false
npx --yes terser assets/js/admin-pickers.js -c -m -o assets/js/admin-pickers.min.js --comments false

node --check assets/js/site.min.js
node --check assets/js/admin-pickers.min.js

echo "Rebuilt:"
for f in assets/css/site.min.css assets/js/site.min.js assets/js/admin-pickers.min.js; do
  printf "  %-32s %d bytes\n" "$f" "$(stat -c%s "$f")"
done
