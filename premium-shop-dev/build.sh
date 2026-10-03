#!/usr/bin/env bash
# Premium Shop — build: translations, minified assets, installable zip.
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
THEME="$HERE/../premium-shop"
DIST="$HERE/../dist"

cd "$HERE"
[ -d node_modules/esbuild ] || npm install --silent

echo "→ Translations (.pot, .po, .mo, .l10n.php)"
python3 build-languages.py

echo "→ Minifying CSS & JS"
for f in main woocommerce editor customizer-controls; do
	npx esbuild "$THEME/assets/css/$f.css" --minify --log-level=warning --outfile="$THEME/assets/css/$f.min.css"
done
for f in theme shop product firewood customizer-controls customizer-preview; do
	npx esbuild "$THEME/assets/js/$f.js" --minify --target=es2017 --log-level=warning --outfile="$THEME/assets/js/$f.min.js"
done

echo "→ PHP lint"
find "$THEME" -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v '^No syntax errors' || true

echo "→ Packaging"
mkdir -p "$DIST"
rm -f "$DIST/premium-shop.zip"
(cd "$THEME/.." && zip -qr "$DIST/premium-shop.zip" premium-shop -x '*.DS_Store' -x '*/node_modules/*')
ls -lh "$DIST/premium-shop.zip"
