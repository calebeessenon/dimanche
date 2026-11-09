const { chromium } = require('playwright');
(async () => {
  const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1572, height: 800 }, deviceScaleFactor: 2 });
  await p.goto('file://' + __dirname + '/out/apercu.html');
  await p.screenshot({ path: __dirname + '/out/neoplay-propositions.png', fullPage: true });
  // PNG exports
  for (const k of ['a','b','c']) for (const v of ['logo','logo-blanc','icone']) {
    const f = `neoplay-${k}-${v}`;
    await p.setContent(`<style>body{margin:0;background:transparent}</style>` + require('fs').readFileSync(__dirname + `/out/${f}.svg`, 'utf8'));
    const el = await p.$('svg');
    if (v === 'icone') await el.evaluate(s => { s.setAttribute('width', 256); s.setAttribute('height', 256); });
    await el.screenshot({ path: __dirname + `/out/${f}.png`, omitBackground: true });
  }
  await b.close();
})();
