const { chromium } = require('playwright'); const fs = require('fs');
(async () => {
  const b = await chromium.launch(); const p = await b.newPage();
  const svg = fs.readFileSync(__dirname + '/out/neoplay-b-icone.svg', 'utf8');
  for (const s of [16, 32, 48, 180, 512]) {
    await p.setContent(`<style>body{margin:0;background:transparent}</style>` + svg);
    const el = await p.$('svg'); await el.evaluate((n, s) => { n.setAttribute('width', s); n.setAttribute('height', s); }, s);
    await el.screenshot({ path: `${__dirname}/out/final-${s}.png`, omitBackground: true });
  }
  for (const v of ['logo', 'logo-blanc']) {
    await p.setContent(`<style>body{margin:0;background:transparent}</style>` + fs.readFileSync(`${__dirname}/out/neoplay-b-${v}.svg`, 'utf8'));
    const el = await p.$('svg'); await el.evaluate(n => { const w = +n.getAttribute('width'); n.setAttribute('width', w * 3); n.setAttribute('height', 384); });
    await el.screenshot({ path: `${__dirname}/out/final-${v}.png`, omitBackground: true });
  }
  await b.close();
})();
