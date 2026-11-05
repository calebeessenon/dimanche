// node render.js SVG_DIR JPG_DIR — renders each SVG to a JPEG of the same size.
const { chromium } = require('playwright');
const fs = require('fs');
const [,, dir, out] = process.argv;
(async () => {
  const b = await chromium.launch();
  const p = await b.newPage();
  fs.mkdirSync(out, { recursive: true });
  for (const f of fs.readdirSync(dir).filter(f => f.endsWith('.svg'))) {
    const svg = fs.readFileSync(dir + '/' + f, 'utf8');
    const [, w, h] = svg.match(/width="(\d+)" height="(\d+)"/);
    await p.setViewportSize({ width: +w, height: +h });
    await p.setContent(`<html><body style="margin:0">${svg}</body></html>`);
    await p.screenshot({ path: `${out}/${f.replace('.svg', '.jpg')}`, type: 'jpeg', quality: 84 });
  }
  await b.close();
})();
