"""NeoPlay logo proposals: SVG with text converted to paths (Space Grotesk Bold)."""
import io, os
from fontTools.ttLib import TTFont
from fontTools.varLib.instancer import instantiateVariableFont
from fontTools.pens.svgPathPen import SVGPathPen
from fontTools.pens.transformPen import TransformPen

HERE = os.path.dirname(os.path.abspath(__file__))
FONT = os.path.join(HERE, '../../premium-shop/assets/fonts/space-grotesk-var-latin.woff2')
OUT = os.path.join(HERE, 'out'); os.makedirs(OUT, exist_ok=True)

f = TTFont(FONT); f.flavor = None
f = instantiateVariableFont(f, {'wght': 700})
gs, cmap, upm = f.getGlyphSet(), f.getBestCmap(), f['head'].unitsPerEm
hmtx = f['hmtx']

def text_path(s, x, y, size, track=0):
    """Return (path d, advance width) for s, baseline at y."""
    sc = size / upm; pen = SVGPathPen(gs); cx = 0
    for ch in s:
        g = cmap[ord(ch)]
        gs[g].draw(TransformPen(pen, (sc, 0, 0, -sc, x + cx, y)))
        cx += hmtx[g][0] * sc + track
    return pen.getCommands(), cx - track

V, C, PINK = '#7c5cff', '#00d4ff', '#ff4fd8'
DEFS = f'''<defs>
<linearGradient id="np-g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{V}"/><stop offset="1" stop-color="{C}"/></linearGradient>
<linearGradient id="np-t" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="{V}"/><stop offset="1" stop-color="{C}"/></linearGradient>
</defs>'''

# --- Icons (120 x 120) ---
def icon_a():  # Rounded square, play button cut into an N-like shape
    return f'''<rect width="120" height="120" rx="30" fill="url(#np-g)"/>
<path d="M44 34 L88 60 L44 86 Z" fill="#fff" stroke="#fff" stroke-width="10" stroke-linejoin="round"/>
<circle cx="92" cy="30" r="7" fill="#fff" opacity=".9"/>'''

def icon_b():  # Hexagon with a stylised N made of two bars + diagonal (play arrow)
    return f'''<path d="M60 4 L108 32 L108 88 L60 116 L12 88 L12 32 Z" fill="url(#np-g)" stroke="url(#np-g)" stroke-width="6" stroke-linejoin="round"/>
<path d="M38 84 V36 L82 84 V36" fill="none" stroke="#fff" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>'''

def icon_c():  # Gamepad silhouette with play triangle
    return f'''<path d="M30 30 H90 C108 30 118 46 118 66 L118 82 C118 98 104 104 94 94 L82 82 H38 L26 94 C16 104 2 98 2 82 L2 66 C2 46 12 30 30 30 Z" fill="url(#np-g)"/>
<path d="M50 44 L76 58 L50 72 Z" fill="#fff" stroke="#fff" stroke-width="6" stroke-linejoin="round"/>
<circle cx="24" cy="58" r="5" fill="#fff" opacity=".85"/><circle cx="96" cy="58" r="5" fill="#fff" opacity=".85"/>'''

ICONS = {'a': icon_a, 'b': icon_b, 'c': icon_c}

def logo(icon, dark_text):
    """Horizontal logo: icon + 'Neo' (solid) + 'Play' (gradient)."""
    neo, w1 = text_path('Neo', 0, 0, 74)
    play, w2 = text_path('Play', 0, 0, 74)
    tx = 148
    neo, w1 = text_path('Neo', tx, 86, 74, -1)
    play, w2 = text_path('Play', tx + w1 + 2, 86, 74, -1)
    w = int(tx + w1 + 2 + w2 + 6)
    col = '#161b3d' if dark_text else '#ffffff'
    return f'''<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} 128" width="{w}" height="128">{DEFS}
<g transform="translate(0 4)">{ICONS[icon]()}</g>
<path d="{neo}" fill="{col}"/><path d="{play}" fill="url(#np-t)"/></svg>'''

def icon_svg(icon):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="120" height="120">{DEFS}{ICONS[icon]()}</svg>'

for k in ICONS:
    open(f'{OUT}/neoplay-{k}-logo.svg', 'w').write(logo(k, True))
    open(f'{OUT}/neoplay-{k}-logo-blanc.svg', 'w').write(logo(k, False))
    open(f'{OUT}/neoplay-{k}-icone.svg', 'w').write(icon_svg(k))
print('ok')
