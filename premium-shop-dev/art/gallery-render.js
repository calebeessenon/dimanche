// node gallery-render.js HTML_DIR JPG_DIR — renders each HTML page (1000×1000) to JPEG.
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');
const [,, dir, out] = process.argv;
(async () => {
  const b = await chromium.launch();
  const p = await b.newPage({ viewport: { width: 1000, height: 1000 } });
  fs.mkdirSync(out, { recursive: true });
  for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.html')).sort()) {
    await p.goto('file://' + path.resolve(dir, f));
    await p.evaluate(() => document.fonts.ready);
    await p.screenshot({ path: path.join(out, f.replace('.html', '.jpg')), type: 'jpeg', quality: 80 });
  }
  await b.close();
})();
