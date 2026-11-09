"""Extra gallery pictures for each product of the gaming catalogue.

python3 gallery.py IMAGES_DIR HTML_OUT_DIR

For every product it writes the data sheet picture <sku>-specs (rendered to JPEG
by gallery-render.js): package dimensions, weight, brand, EAN-13 barcode.
The other views (<sku>-view-2 to -4: back, profile/edge, 3/4…) are drawn by
views.py.
All texts are language-neutral (units and numbers only).
"""
import base64
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(HERE, '..', 'gaming-catalogue'))
from catalogue import PRODUCTS  # noqa: E402
from specs import SPECS, gtin  # noqa: E402

IMAGES, OUT = sys.argv[1], sys.argv[2]
os.makedirs(OUT, exist_ok=True)

FONT = os.path.abspath(os.path.join(HERE, '..', '..', 'premium-shop', 'assets', 'fonts', 'space-grotesk-var-latin.woff2'))

# EAN-13 encoding tables.
L_CODE = ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011']
G_CODE = ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111']
R_CODE = ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100']
PARITY = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL']


def ean_svg(code, module=3, height=90):
    bits, guard = '101', set()
    guard.update(range(0, 3))
    for i, d in enumerate(code[1:7]):
        bits += (L_CODE if PARITY[int(code[0])][i] == 'L' else G_CODE)[int(d)]
    guard.update(range(len(bits), len(bits) + 5))
    bits += '01010'
    for d in code[7:]:
        bits += R_CODE[int(d)]
    guard.update(range(len(bits), len(bits) + 3))
    bits += '101'
    quiet = 11 * module
    w = len(bits) * module + 2 * quiet
    rects = ''.join(
        f'<rect x="{quiet + i * module}" y="0" width="{module}" height="{height + (10 if i in guard else 0)}"/>'
        for i, b in enumerate(bits) if b == '1'
    )
    digits = (f'<text x="{quiet - 6}" y="{height + 26}" text-anchor="end">{code[0]}</text>'
              f'<text x="{quiet + 3 * module + 21 * module}" y="{height + 26}" text-anchor="middle">{code[1:7]}</text>'
              f'<text x="{quiet + 50 * module + 21 * module}" y="{height + 26}" text-anchor="middle">{code[7:]}</text>')
    return (f'<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{height + 34}" viewBox="0 0 {w} {height + 34}">'
            f'<rect width="100%" height="100%" fill="#fff"/><g fill="#111">{rects}</g>'
            f'<g font-family="monospace" font-size="22" fill="#111">{digits}</g></svg>')


def data_uri(path):
    with open(path, 'rb') as f:
        return 'data:image/jpeg;base64,' + base64.b64encode(f.read()).decode()


def neutral_chips(attrs, n=2):
    out = []
    for key, val in attrs:
        if isinstance(val, str) and key in ('storage', 'resolution', 'display', 'memory', 'protection', 'value', 'psu', 'age', 'connectivity') and len(val) <= 22:
            out.append(val)
        if len(out) == n:
            break
    return out


BASE = f'''<style>
@font-face {{ font-family: SG; src: url("file://{FONT}") format("woff2"); font-weight: 300 700; }}
* {{ box-sizing: border-box; margin: 0; }}
body {{ width: 1000px; height: 1000px; overflow: hidden; font-family: SG, sans-serif; color: #fff; background: #161b3d; }}
.stage {{ position: absolute; inset: 0; overflow: hidden; }}
.stage img {{ position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }}
.chip {{ display: inline-block; padding: 14px 24px; border-radius: 999px; background: rgba(15, 18, 45, .72); border: 2px solid rgba(124, 92, 255, .8);
        font-size: 34px; font-weight: 700; letter-spacing: -.01em; box-shadow: 0 10px 40px rgba(0, 0, 0, .35); }}
</style>'''


def page(body):
    return f'<!doctype html><html><head><meta charset="utf-8">{BASE}</head><body>{body}</body></html>'


done = 0
for index, p in enumerate(PRODUCTS):
    sku = p['sku'].lower()
    brand, weight, (ln, wd, ht), attrs = SPECS[p['sku']]
    img = data_uri(os.path.join(IMAGES, p['img']))
    chips = neutral_chips(attrs)

    # 2. Close-up with key specs.
    chip_html = ''.join(f'<span class="chip" style="margin-right:14px">{c}</span>' for c in chips)
    html2 = page(f'''<div class="stage"><img src="{img}" style="transform: scale(1.32) translate(1%, 3%); transform-origin: 50% 50%;"></div>
<div class="stage" style="background: radial-gradient(circle at 50% 50%, transparent 55%, rgba(10,12,35,.55) 100%)"></div>
<div style="position:absolute; left:48px; bottom:52px">{chip_html}</div>''')

    # 3. Second view: mirrored, other light.
    html3 = page(f'''<div class="stage"><img src="{img}" style="transform: scaleX(-1) scale(1.12) translate(3%, 2%);"></div>
<div class="stage" style="background: linear-gradient(115deg, rgba(0,212,255,.22), transparent 45%), radial-gradient(circle at 15% 90%, rgba(255,79,216,.25), transparent 45%)"></div>
<div class="stage" style="box-shadow: inset 0 0 160px rgba(5,8,30,.55)"></div>''')

    # 4. Data sheet: dimensions, weight, brand, barcode.
    code = gtin(index)
    barcode = ean_svg(code)
    html4 = page(f'''<div class="stage" style="background: radial-gradient(circle at 50% 35%, #2b3378, #161b3d 70%)"></div>
<div style="position:absolute; left:150px; top:120px; width:560px; height:560px; border-radius:28px; overflow:hidden; box-shadow:0 30px 80px rgba(0,0,0,.45)">
  <img src="{img}" style="width:100%; height:100%; object-fit:cover"></div>
<!-- width -->
<div style="position:absolute; left:150px; top:84px; width:560px; border-top:3px solid #9fe8ff"></div>
<div style="position:absolute; left:150px; top:74px; height:22px; border-left:3px solid #9fe8ff"></div>
<div style="position:absolute; left:707px; top:74px; height:22px; border-left:3px solid #9fe8ff"></div>
<div style="position:absolute; left:150px; width:560px; top:30px; text-align:center; font-size:34px; font-weight:700; color:#9fe8ff">{ln} cm</div>
<!-- height -->
<div style="position:absolute; left:750px; top:120px; height:560px; border-left:3px solid #9fe8ff"></div>
<div style="position:absolute; left:740px; top:120px; width:22px; border-top:3px solid #9fe8ff"></div>
<div style="position:absolute; left:740px; top:677px; width:22px; border-top:3px solid #9fe8ff"></div>
<div style="position:absolute; left:772px; top:370px; font-size:34px; font-weight:700; color:#9fe8ff">{wd} cm</div>
<div style="position:absolute; left:772px; top:420px; font-size:24px; color:#c5cbef">↕ {ht} cm</div>
<!-- facts -->
<div style="position:absolute; left:60px; top:730px; display:flex; gap:16px; align-items:center">
  <span class="chip">⚖ {weight:.2f} kg</span>
  <span class="chip" style="border-color:rgba(0,212,255,.8)">{brand}</span>
</div>
<div style="position:absolute; left:60px; top:820px; font-size:24px; color:#c5cbef; letter-spacing:.04em">{ln} × {wd} × {ht} cm</div>
<div style="position:absolute; right:56px; bottom:48px; padding:14px 16px 8px; background:#fff; border-radius:14px">{barcode}</div>
<div style="position:absolute; left:60px; bottom:60px; font-size:22px; color:#8f97cf; letter-spacing:.12em">{p["sku"]}</div>''')

    for n, html in ((5, html4),):
        with open(os.path.join(OUT, f'{sku}-specs.html'), 'w', encoding='utf-8') as f:
            f.write(html)
        done += 1
print(done, 'pages')
