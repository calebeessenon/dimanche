"""Extra product views for the gaming catalogue: back, profile / edge,
3/4 perspective, TV mode, packaging… (original drawings, no brand logos).

python3 views.py SVG_OUT_DIR  -> writes <sku>-view-2.svg … <sku>-view-4.svg
Render with render.js (same as the main pictures).
"""
import os
import sys

HERE = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, HERE)
from gaming_art import (studio, shadow, screen, pad, switch2, ps5_slim, ps5_pro, portal,  # noqa: E402
                        nsw2, oled, lite, joycons, speaker, mini_speaker, gift_card, game_box)

EXTRA_DEFS = '''<defs>
<filter id="dk"><feColorMatrix type="matrix" values=".38 0 0 0 0  0 .38 0 0 0  0 0 .42 0 0  0 0 0 1 0"/></filter>
<linearGradient id="metal" x1="0" x2="1"><stop offset="0" stop-color="#b9bfcc"/><stop offset=".5" stop-color="#eef1f6"/><stop offset="1" stop-color="#9aa1b0"/></linearGradient>
<linearGradient id="vshade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset="1" stop-color="#000" stop-opacity=".22"/></linearGradient>
</defs>'''


def page(hue, body):
    return studio(hue) + EXTRA_DEFS + body


def strip_bg(svg):
    """Drawing of a main picture without its studio background."""
    marker = '</g>'  # studio() ends with the grid group
    i = svg.find('<g opacity=".18"')
    j = svg.find(marker, i) + len(marker)
    return svg[j:]


def persp(inner, depth=10, skew=-9, sx=.84, cy=520):
    """3/4 view: skewed drawing with a thickness made of darkened copies."""
    t = f'translate(500 {cy}) skewY({skew}) scale({sx} .94) translate(-500 -{cy})'
    out = f'<g transform="{t}">'
    for k in range(depth, 0, -1):
        out += f'<g transform="translate({k * 2.2} {k * 1.6})" filter="url(#dk)">{inner}</g>'
    return out + inner + '</g>'


PAD_PATH = 'M150 40 C 220 0 500 0 570 40 C 650 80 710 240 720 330 C 730 410 660 450 610 400 L 520 300 H 200 L 110 400 C 60 450 -10 410 0 330 C 10 240 70 80 150 40Z'


# ----------------------------------------------------------------- controllers
def pad_back(x, y, s, body, core, paddles=False, grip=None):
    g = f'<g transform="translate({x} {y}) scale({s})">'
    g += f'<path d="{PAD_PATH}" fill="{body}" stroke="#d3d8e3" stroke-opacity=".45" stroke-width="3"/>'
    g += f'<path d="{PAD_PATH}" fill="url(#vshade)"/>'
    if grip:
        g += f'<path d="M150 40 C 90 70 30 200 10 330 C 0 410 60 450 110 400 L 200 300 Z" fill="{grip}"/>'
        g += f'<path d="M570 40 C 630 70 690 200 710 330 C 720 410 660 450 610 400 L 520 300 Z" fill="{grip}"/>'
    # back cover seam, centre plate, vents
    g += f'<path d="M190 70 C 260 40 460 40 530 70 L 560 250 H 160 Z" fill="{core}" opacity=".9"/>'
    g += ''.join(f'<rect x="{300 + i * 26}" y="120" width="10" height="70" rx="5" fill="#000" opacity=".35"/>' for i in range(5))
    g += '<circle cx="360" cy="230" r="9" fill="#000" opacity=".35"/>'  # reset hole
    g += '<path d="M120 120 C 80 220 60 300 70 360" stroke="#000" stroke-opacity=".18" stroke-width="4" fill="none"/>'
    g += '<path d="M600 120 C 640 220 660 300 650 360" stroke="#000" stroke-opacity=".18" stroke-width="4" fill="none"/>'
    if paddles:
        for px in (150, 570):
            g += f'<rect x="{px - 30}" y="250" width="60" height="110" rx="26" fill="#2c3039" stroke="#545a66" stroke-width="3"/>'
    # triggers seen from below (top edge)
    for tx in (140, 520):
        g += f'<path d="M{tx} 40 Q {tx + 30} -40 {tx + 60} 40Z" fill="{core}"/>'
    return g + '</g>'


def pad_edge(body, core, accent, hue):
    """Front edge: shoulder buttons, triggers, USB-C port, light bar."""
    s = page(hue, shadow(500, 720, 360, 30))
    s += '<g transform="translate(140 430)">'
    s += f'<path d="M0 120 C 0 40 60 0 140 0 H 580 C 660 0 720 40 720 120 C 720 190 660 220 600 220 H 120 C 60 220 0 190 0 120Z" fill="{body}" stroke="#d3d8e3" stroke-opacity=".4" stroke-width="3"/>'
    s += '<path d="M0 120 C 0 40 60 0 140 0 H 580 C 660 0 720 40 720 120 C 720 190 660 220 600 220 H 120 C 60 220 0 190 0 120Z" fill="url(#vshade)"/>'
    for tx in (60, 520):  # L2 / R2 triggers
        s += f'<path d="M{tx} 20 C {tx + 10} -110 {tx + 130} -110 {tx + 140} 20Z" fill="{core}"/>'
        s += f'<rect x="{tx + 10}" y="30" width="120" height="34" rx="17" fill="{core}" opacity=".85"/>'  # L1 / R1
    s += f'<rect x="250" y="70" width="220" height="70" rx="35" fill="{core}"/>'
    s += '<rect x="335" y="92" width="50" height="22" rx="11" fill="#0b0c10"/><rect x="345" y="99" width="30" height="8" rx="4" fill="#5b616e"/>'  # USB-C
    s += f'<rect x="260" y="160" width="200" height="8" rx="4" fill="{accent}"/>'  # light bar
    s += f'<rect x="260" y="160" width="200" height="8" rx="4" fill="{accent}" opacity=".6" filter="blur(6px)"/>'
    return s + '</g>'


def controller_views(body, core, accent, hue, two_tone=False, paddles=False):
    back = page(hue, shadow(500, 780, 340, 34) + pad_back(140, 330, 1.0, body, core, paddles, core if two_tone else None))
    edge = pad_edge(body, core, accent, hue)
    front = pad(140, 330, 1.0, body, core, accent, two_tone)
    tilt = page(hue, shadow(520, 800, 330, 32) + persp(front, depth=12))
    return [back, edge, tilt]


# --------------------------------------------------------------------- PS5
def ps5_slim_views(hue='#3b82f6'):
    # Back: ports in the black core.
    b = '<g transform="translate(350 170)">'
    b += '<path d="M100 20 C 50 14 30 40 26 80 L 14 600 C 14 626 34 640 60 640 L 100 640Z" fill="url(#white)"/>'
    b += '<path d="M200 20 C 250 14 270 40 274 80 L 286 600 C 286 626 266 640 240 640 L 200 640Z" fill="url(#white)"/>'
    b += '<rect x="100" y="0" width="100" height="640" rx="14" fill="url(#dark)"/>'
    b += ''.join(f'<rect x="116" y="{40 + i * 22}" width="68" height="9" rx="4" fill="#0b0c10"/>' for i in range(9))
    b += '<path d="M122 430 h56 l-6 22 h-44z" fill="#0b0c10"/>'  # HDMI
    b += '<rect x="126" y="470" width="48" height="18" rx="3" fill="#0b0c10"/><rect x="126" y="498" width="48" height="18" rx="3" fill="#0b0c10"/>'  # USB-A
    b += '<rect x="132" y="528" width="36" height="32" rx="3" fill="#0b0c10"/>'  # LAN
    b += '<circle cx="140" cy="590" r="10" fill="#0b0c10"/><circle cx="160" cy="590" r="10" fill="#0b0c10"/>'  # power
    b += '<rect x="26" y="300" width="74" height="4" fill="#d5d9e3"/><rect x="200" y="300" width="74" height="4" fill="#d5d9e3"/></g>'
    back = page(hue, shadow(500, 830, 260, 30) + b)
    # Profile: the curved white panel seen from the side.
    p = '<g transform="translate(360 160)">'
    p += '<path d="M40 0 C 160 10 250 60 270 160 L 290 600 C 292 640 270 660 240 660 L 60 660 C 30 660 10 640 10 610 L 0 40 C 0 14 16 0 40 0Z" fill="url(#white)"/>'
    p += '<path d="M0 40 L 10 610 C 10 640 30 660 60 660 L 40 660 C 14 660 -6 640 -6 610 L -16 40 C -16 14 0 0 24 0Z" fill="url(#dark)"/>'
    p += '<path d="M30 300 C 120 300 220 300 280 300" stroke="#d5d9e3" stroke-width="4"/>'
    p += ''.join(f'<rect x="{60 + i * 34}" y="20" width="18" height="6" rx="3" fill="#c4c9d4"/>' for i in range(5))
    p += '<circle cx="40" cy="590" r="6" fill="#5aa9ff"/></g>'
    profile = page(hue, shadow(500, 830, 220, 28) + p)
    tilt = page(hue, shadow(520, 840, 280, 30) + persp(strip_bg(ps5_slim()), depth=10, skew=-7))
    return [back, profile, tilt]


def ps5_pro_views(hue='#7c5cff'):
    b = '<g transform="translate(330 150)"><rect width="340" height="660" rx="28" fill="url(#white)"/>'
    b += '<rect y="210" width="340" height="40" fill="url(#dark)"/><rect y="420" width="340" height="40" fill="url(#dark)"/>'
    b += ''.join(f'<rect x="{40 + (i % 10) * 27}" y="{40 + (i // 10) * 26}" width="14" height="14" rx="4" fill="#c7ccd7"/>' for i in range(60))
    b += '<rect x="60" y="490" width="220" height="130" rx="14" fill="url(#dark)"/>'
    b += '<path d="M80 515 h56 l-6 20 h-44z" fill="#0b0c10"/><rect x="152" y="512" width="44" height="18" rx="3" fill="#0b0c10"/><rect x="210" y="512" width="44" height="18" rx="3" fill="#0b0c10"/>'
    b += '<rect x="86" y="555" width="36" height="32" rx="3" fill="#0b0c10"/><circle cx="182" cy="572" r="10" fill="#0b0c10"/><circle cx="202" cy="572" r="10" fill="#0b0c10"/></g>'
    back = page(hue, shadow(500, 830, 300, 34) + b)
    p = '<g transform="translate(420 150)"><rect width="160" height="660" rx="22" fill="url(#white)"/>'
    p += '<rect y="210" width="160" height="40" fill="url(#dark)"/><rect y="420" width="160" height="40" fill="url(#dark)"/>'
    p += ''.join(f'<rect x="30" y="{60 + i * 24}" width="100" height="8" rx="4" fill="#c7ccd7"/>' for i in range(5))
    p += '<rect x="58" y="600" width="44" height="10" rx="5" fill="#2b2f3a"/></g>'
    profile = page(hue, shadow(500, 830, 180, 28) + p)
    tilt = page(hue, shadow(520, 840, 300, 32) + persp(strip_bg(ps5_pro()), depth=14, skew=-8))
    return [back, profile, tilt]


def portal_views(hue='#5aa9ff'):
    b = pad_back(140, 380, 1.0, '#f6f7fb', '#1d2027')
    b += '<rect x="300" y="250" width="400" height="260" rx="18" fill="#2a2d36"/><rect x="300" y="250" width="400" height="260" rx="18" fill="url(#vshade)"/>'
    b += ''.join(f'<rect x="{420 + i * 20}" y="300" width="8" height="60" rx="4" fill="#15171d"/>' for i in range(8))
    back = page(hue, shadow(500, 780, 360, 34) + b)
    e = '<g transform="translate(120 450)">'
    e += '<rect x="170" y="40" width="420" height="40" rx="12" fill="#1b1d24"/>'
    for gx in (0, 540):
        e += f'<path d="M{gx} 120 C {gx} 50 {gx + 40} 20 {gx + 110} 20 H {gx + 150} C {gx + 200} 20 {gx + 220} 60 {gx + 220} 120 C {gx + 220} 170 {gx + 190} 200 {gx + 140} 200 H {gx + 80} C {gx + 30} 200 {gx} 170 {gx} 120Z" fill="#f6f7fb"/>'
        e += f'<path d="M{gx + 40} 30 C {gx + 50} -60 {gx + 170} -60 {gx + 180} 30Z" fill="#1d2027"/>'
    e += '<rect x="360" y="52" width="40" height="16" rx="8" fill="#0b0c10"/><rect x="250" y="52" width="40" height="12" rx="6" fill="#3b3f49"/><rect x="470" y="52" width="40" height="12" rx="6" fill="#3b3f49"/></g>'
    edge = page(hue, shadow(500, 720, 400, 30) + e)
    tilt = page(hue, shadow(520, 800, 360, 32) + persp(strip_bg(portal()), depth=10))
    return [back, edge, tilt]


# ------------------------------------------------------------- Nintendo Switch
def switch_back(body, jl, jr, stand_w=440, lite_color=None):
    if lite_color:
        g = f'<rect x="150" y="340" width="700" height="310" rx="150" fill="{lite_color}"/><rect x="150" y="340" width="700" height="310" rx="150" fill="url(#vshade)"/>'
        g += ''.join(f'<rect x="{420 + i * 18}" y="372" width="8" height="40" rx="4" fill="#000" opacity=".25"/>' for i in range(9))
        g += '<rect x="380" y="560" width="240" height="44" rx="10" fill="#000" opacity=".12"/>'
        g += ''.join(f'<circle cx="{cx}" cy="{cy}" r="6" fill="#000" opacity=".3"/>' for cx, cy in [(260, 380), (740, 380), (260, 610), (740, 610)])
        return g
    g = f'<path d="M110 420 a100 100 0 0 1 100 -100 h50 v360 h-50 a100 100 0 0 1 -100 -100Z" fill="{jr}"/>'
    g += f'<path d="M740 320 h50 a100 100 0 0 1 100 100 v160 a100 100 0 0 1 -100 100 h-50Z" fill="{jl}"/>'
    g += f'<rect x="260" y="320" width="480" height="360" rx="16" fill="{body}"/><rect x="260" y="320" width="480" height="360" rx="16" fill="url(#vshade)"/>'
    g += ''.join(f'<rect x="{330 + i * 20}" y="340" width="8" height="46" rx="4" fill="#0b0c10" opacity=".7"/>' for i in range(18))
    sx = 500 - stand_w / 2
    g += f'<rect x="{sx}" y="520" width="{stand_w}" height="130" rx="10" fill="#000" opacity=".22"/><rect x="{sx + 8}" y="528" width="{stand_w - 16}" height="114" rx="8" fill="none" stroke="#fff" stroke-opacity=".12" stroke-width="2"/>'
    g += '<rect x="246" y="400" width="10" height="70" rx="4" fill="#3b3f49"/><rect x="744" y="400" width="10" height="70" rx="4" fill="#3b3f49"/>'
    return g


def switch_edge(body, jl, jr, lite_color=None):
    g = '<g transform="translate(110 470)">'
    if lite_color:
        g += f'<rect x="40" y="20" width="700" height="70" rx="35" fill="{lite_color}"/><rect x="40" y="20" width="700" height="70" rx="35" fill="url(#vshade)"/>'
        for tx in (90, 590):
            g += f'<rect x="{tx}" y="0" width="100" height="26" rx="13" fill="#20252b"/>'
        g += '<rect x="330" y="40" width="120" height="16" rx="4" fill="#0b0c10"/><rect x="480" y="38" width="40" height="12" rx="6" fill="#20252b"/><rect x="540" y="38" width="60" height="12" rx="6" fill="#20252b"/>'
        return g + '</g>'
    g += f'<rect x="0" y="20" width="160" height="70" rx="35" fill="{jl}"/><rect x="620" y="20" width="160" height="70" rx="35" fill="{jr}"/>'
    g += f'<rect x="140" y="24" width="500" height="62" rx="8" fill="{body}"/><rect x="0" y="20" width="780" height="70" rx="35" fill="url(#vshade)"/>'
    for tx in (30, 650):
        g += f'<path d="M{tx} 22 C {tx + 10} -30 {tx + 90} -30 {tx + 100} 22Z" fill="#2a2d35"/>'
    g += '<rect x="200" y="44" width="120" height="16" rx="4" fill="#0b0c10"/>'  # game card slot
    g += ''.join(f'<rect x="{350 + i * 14}" y="40" width="6" height="24" rx="3" fill="#0b0c10" opacity=".7"/>' for i in range(10))
    g += '<rect x="520" y="44" width="34" height="12" rx="6" fill="#3b3f49"/><rect x="570" y="44" width="50" height="12" rx="6" fill="#3b3f49"/>'
    return g + '</g>'


def docked(console_inner, dock_color, hue):
    s = page(hue, shadow(500, 820, 330, 32))
    s += f'<g transform="translate(130 60) scale(.74)">{console_inner}</g>'
    s += f'<path d="M250 470 H750 L 770 790 H230Z" fill="{dock_color}"/><path d="M250 470 H750 L 770 790 H230Z" fill="url(#vshade)"/>'
    s += '<rect x="470" y="740" width="60" height="8" rx="4" fill="#5aa9ff"/>'
    s += f'<rect x="250" y="462" width="500" height="18" rx="6" fill="{dock_color}"/>'
    return s


def switch_views(kind):
    if kind == 's2':
        hue, body, jl, jr = '#ff4b45', '#2b2f3a', '#21242c', '#21242c'
        back = page(hue, shadow(500, 760, 400, 38) + switch_back(body, jl, jr))
        edge = page(hue, shadow(500, 660, 400, 30) + switch_edge(body, jl, jr))
        tv = docked(switch2(rid='s2d'), '#22252d', hue)
        return [back, edge, tv]
    if kind == 'oled':
        hue = '#9b5de5'
        back = page(hue, shadow(500, 720, 380, 36) + switch_back('#2e313a', '#f2f3f7', '#f2f3f7', stand_w=470))
        edge = page(hue, shadow(500, 660, 400, 30) + switch_edge('#2e313a', '#f2f3f7', '#f2f3f7'))
        tv = docked(strip_bg(oled(rid='od')), '#f2f3f7', hue)
        return [back, edge, tv]
    hue, c = '#ffcc33', '#ffcc33'
    back = page(hue, shadow(500, 690, 360, 38) + switch_back(None, None, None, lite_color=c))
    edge = page(hue, shadow(500, 660, 380, 30) + switch_edge(None, None, None, lite_color=c))
    tilt = page(hue, shadow(520, 760, 360, 34) + persp(strip_bg(lite(rid='lt')), depth=9))
    return [back, edge, tilt]


def joycon_views(hue='#00b7e8'):
    b = ''
    for x, c, rot in [(330, '#ff4b45', -8), (530, '#00b7e8', 8)]:
        b += f'<g transform="rotate({rot} {x + 70} 520)"><rect x="{x}" y="260" width="140" height="520" rx="70" fill="{c}"/><rect x="{x}" y="260" width="140" height="520" rx="70" fill="url(#vshade)"/>'
        b += f'<rect x="{x + 50}" y="300" width="40" height="440" rx="10" fill="#2a2d35"/>'  # rail
        b += f'<rect x="{x + 58}" y="380" width="24" height="44" rx="8" fill="#5b616e"/><rect x="{x + 58}" y="600" width="24" height="44" rx="8" fill="#5b616e"/></g>'
    back = page(hue, shadow(500, 800, 300, 30) + b)
    e = ''
    for x, c in [(250, '#00b7e8'), (530, '#ff4b45')]:
        e += f'<rect x="{x}" y="470" width="220" height="80" rx="40" fill="{c}"/><rect x="{x}" y="470" width="220" height="80" rx="40" fill="url(#vshade)"/>'
        e += f'<path d="M{x + 20} 474 C {x + 40} 400 {x + 180} 400 {x + 200} 474Z" fill="#2a2d35"/>'
        e += f'<rect x="{x + 40}" y="455" width="140" height="22" rx="11" fill="#3b3f49"/>'
    edge = page(hue, shadow(500, 640, 320, 26) + e)
    tilt = page(hue, shadow(520, 820, 300, 30) + persp(strip_bg(joycons()), depth=10))
    return [back, edge, tilt]


# --------------------------------------------------------------------- audio
def flip_views(body, grill, ring, hue):
    end = f'<circle cx="500" cy="520" r="230" fill="{body}"/><circle cx="500" cy="520" r="230" fill="url(#vshade)"/>'
    end += f'<circle cx="500" cy="520" r="170" fill="{ring}"/><circle cx="500" cy="520" r="128" fill="{body}"/><circle cx="500" cy="520" r="128" fill="url(#vshade)"/>'
    end += f'<path d="M690 330 C 790 330 800 420 740 450" stroke="{ring}" stroke-width="18" fill="none" stroke-linecap="round"/>'
    end_v = page(hue, shadow(500, 790, 230, 28) + end)
    b = f'<rect x="170" y="400" width="660" height="300" rx="150" fill="{body}"/>'
    b += f'<rect x="200" y="420" width="600" height="260" rx="130" fill="{grill}"/>'
    b += f'<g opacity=".3" fill="{ring}">' + ''.join(f'<circle cx="{220 + (i % 30) * 20}" cy="{440 + (i // 30) * 20}" r="3"/>' for i in range(30 * 12)) + '</g>'
    b += f'<rect x="170" y="400" width="660" height="300" rx="150" fill="url(#gshade)"/>'
    b += f'<rect x="300" y="640" width="400" height="60" rx="20" fill="{ring}"/>'  # rubber foot
    b += f'<rect x="740" y="480" width="44" height="140" rx="20" fill="{ring}"/><rect x="752" y="530" width="20" height="40" rx="6" fill="#0b0c10"/>'  # port flap
    b += f'<path d="M190 470 C 120 470 120 630 190 630" stroke="{ring}" stroke-width="16" fill="none"/>'  # strap
    back = page(hue, shadow(500, 770, 380, 36) + b)
    st = f'<rect x="350" y="170" width="300" height="660" rx="150" fill="{body}"/><rect x="372" y="200" width="256" height="600" rx="128" fill="{grill}"/>'
    st += f'<g opacity=".35" fill="{ring}">' + ''.join(f'<circle cx="{392 + (i % 12) * 20}" cy="{230 + (i // 12) * 20}" r="3"/>' for i in range(12 * 28)) + '</g>'
    st += f'<rect x="350" y="170" width="300" height="660" rx="150" fill="url(#gshade)"/>'
    st += f'<rect x="478" y="380" width="44" height="240" rx="22" fill="{ring}"/>' + ''.join(f'<rect x="490" y="{410 + i * 44}" width="20" height="20" rx="5" fill="#fff" opacity=".7"/>' for i in range(4))
    st += f'<ellipse cx="500" cy="186" rx="120" ry="18" fill="{ring}"/>'
    stand = page(hue, shadow(500, 840, 170, 26) + st)
    return [back, end_v, stand]


def go_views(body='#e63950', hue='#ff4b45'):
    b = f'<rect x="300" y="360" width="400" height="390" rx="70" fill="{body}"/><rect x="300" y="360" width="400" height="390" rx="70" fill="url(#vshade)"/>'
    b += '<rect x="340" y="400" width="320" height="310" rx="50" fill="#000" opacity=".18"/>'
    b += '<rect x="470" y="690" width="60" height="22" rx="8" fill="#0b0c10"/>'
    b += '<rect x="400" y="300" width="40" height="80" rx="20" fill="none" stroke="#1d2027" stroke-width="14"/>'
    back = page(hue, shadow(500, 770, 260, 30) + b)
    p = f'<rect x="430" y="360" width="140" height="390" rx="50" fill="{body}"/><rect x="430" y="360" width="140" height="390" rx="50" fill="url(#gshade)"/>'
    p += '<rect x="470" y="300" width="40" height="80" rx="20" fill="none" stroke="#1d2027" stroke-width="14"/>'
    p += '<rect x="560" y="420" width="16" height="160" rx="8" fill="#000" opacity=".3"/>'
    profile = page(hue, shadow(500, 770, 120, 24) + p)
    t = f'<rect x="300" y="420" width="400" height="170" rx="70" fill="{body}"/><rect x="300" y="420" width="400" height="170" rx="70" fill="url(#vshade)"/>'
    t += ''.join(f'<rect x="{390 + i * 60}" y="490" width="36" height="30" rx="8" fill="#000" opacity=".28"/>' for i in range(4))
    t += '<rect x="560" y="360" width="40" height="80" rx="20" fill="none" stroke="#1d2027" stroke-width="14"/>'
    top = page(hue, shadow(500, 680, 260, 26) + t)
    return [back, profile, top]


def headset_views(hue='#7c5cff'):
    body, cush, acc = '#2a2d38', '#1a1c24', '#7c5cff'
    side = f'<path d="M560 260 C 460 150 300 170 260 300" stroke="{body}" stroke-width="44" fill="none" stroke-linecap="round"/>'
    side += f'<circle cx="520" cy="560" r="200" fill="{body}"/><circle cx="520" cy="560" r="200" fill="url(#vshade)"/>'
    side += f'<circle cx="520" cy="560" r="150" fill="none" stroke="{acc}" stroke-width="6" opacity=".8"/><circle cx="520" cy="560" r="60" fill="{cush}"/>'
    side += f'<rect x="500" y="300" width="40" height="80" rx="12" fill="{body}"/>'
    side += f'<path d="M390 700 C 330 780 260 790 200 760" stroke="{body}" stroke-width="22" fill="none" stroke-linecap="round"/><circle cx="196" cy="758" r="24" fill="{cush}"/>'
    profile = page(hue, shadow(520, 820, 260, 28) + side)
    bk = f'<path d="M260 560 C 260 240 740 240 740 560" stroke="{body}" stroke-width="50" fill="none" stroke-linecap="round"/>'
    bk += f'<path d="M290 540 C 290 290 710 290 710 540" stroke="{cush}" stroke-width="20" fill="none"/>'
    for cx in (270, 730):
        bk += f'<ellipse cx="{cx}" cy="620" rx="96" ry="150" fill="{body}"/><ellipse cx="{cx + (24 if cx < 500 else -24)}" cy="620" rx="70" ry="126" fill="{cush}"/>'
        bk += f'<ellipse cx="{cx + (24 if cx < 500 else -24)}" cy="620" rx="40" ry="90" fill="#2f3240"/>'
    back = page(hue, shadow(500, 820, 330, 30) + bk)
    tp = f'<path d="M230 520 C 230 300 770 300 770 520" stroke="{body}" stroke-width="70" fill="none" stroke-linecap="round"/>'
    tp += f'<path d="M230 520 C 230 300 770 300 770 520" stroke="{acc}" stroke-width="6" fill="none" opacity=".7"/>'
    for cx in (230, 770):
        tp += f'<rect x="{cx - 60}" y="500" width="120" height="200" rx="50" fill="{body}"/><rect x="{cx - 60}" y="500" width="120" height="200" rx="50" fill="url(#vshade)"/>'
    tp += f'<rect x="760" y="640" width="14" height="120" rx="7" fill="{body}"/>'
    top = page(hue, shadow(500, 780, 340, 28) + tp)
    return [profile, back, top]


# ---------------------------------------------------------- gift cards, games
def card_views(c1, c2, hue, symbol):
    bk = '<g transform="rotate(6 500 520)"><rect x="220" y="330" width="560" height="350" rx="30" fill="#f4f5f9"/>'
    bk += '<rect x="220" y="380" width="560" height="60" fill="#1d2027"/>'
    bk += '<rect x="260" y="480" width="300" height="56" rx="10" fill="url(#metal)"/>'
    bk += ''.join(f'<rect x="260" y="{560 + i * 22}" width="{300 - i * 60}" height="10" rx="5" fill="#c7ccd7"/>' for i in range(4))
    bk += ''.join(f'<rect x="{600 + i * 6}" y="560" width="{3 if i % 3 else 5}" height="70" fill="#1d2027"/>' for i in range(24)) + '</g>'
    back = page(hue, shadow(500, 780, 320, 30) + bk)
    pk = f'<rect x="300" y="140" width="400" height="700" rx="24" fill="{c1}"/><rect x="300" y="140" width="400" height="700" rx="24" fill="url(#vshade)"/>'
    pk += '<rect x="450" y="170" width="100" height="30" rx="15" fill="#161b3d"/>'  # euro hanger hole
    pk += f'<circle cx="600" cy="330" r="140" fill="{c2}" opacity=".35"/>'
    pk += '<rect x="340" y="420" width="320" height="230" rx="20" fill="#fff" opacity=".18"/>'
    pk += f'<g transform="translate(355 440) scale(.52)"><rect width="560" height="350" rx="30" fill="{c1}" stroke="#fff" stroke-width="6"/><text x="50" y="290" font-family="Helvetica,Arial,sans-serif" font-size="90" font-weight="700" fill="#fff">{symbol}</text></g>'
    pk += '<text x="340" y="740" font-family="Helvetica,Arial,sans-serif" font-size="44" font-weight="700" fill="#fff">GIFT CARD</text>'
    pack = page(hue, shadow(500, 860, 230, 26) + pk)
    tilt = page(hue, shadow(520, 790, 320, 30) + persp(strip_bg(gift_card(c1, c2, hue, symbol)), depth=4, skew=-12, sx=.8))
    return [back, pack, tilt]


def game_views(c1, c2, hue, kind):
    bk = '<rect x="330" y="200" width="340" height="600" rx="18" fill="#f5f6fa"/>'
    bk += f'<rect x="346" y="240" width="308" height="544" rx="10" fill="{c1}"/><rect x="346" y="240" width="308" height="544" rx="10" fill="#000" opacity=".25"/>'
    bk += ''.join(f'<rect x="{366 + i * 96}" y="270" width="84" height="64" rx="6" fill="{c2}" opacity="{.9 - i * .2}"/>' for i in range(3))
    bk += ''.join(f'<rect x="366" y="{360 + i * 22}" width="{268 - (i % 3) * 40}" height="10" rx="5" fill="#fff" opacity=".6"/>' for i in range(10))
    bk += '<rect x="366" y="640" width="70" height="90" rx="6" fill="#fff"/><text x="401" y="702" text-anchor="middle" font-family="Helvetica,Arial,sans-serif" font-size="48" font-weight="700" fill="#2ed47a">3</text>'
    bk += '<rect x="520" y="660" width="114" height="70" rx="4" fill="#fff"/>' + ''.join(f'<rect x="{528 + i * 5}" y="668" width="{2 if i % 3 else 3}" height="54" fill="#111"/>' for i in range(20))
    back = page(hue, shadow(500, 820, 260, 30) + bk)
    sp = '<rect x="450" y="200" width="100" height="600" rx="14" fill="#f5f6fa"/>'
    sp += f'<rect x="462" y="230" width="76" height="560" rx="8" fill="{c1}"/><rect x="462" y="208" width="76" height="18" rx="4" fill="{c2}"/>'
    sp += f'<circle cx="500" cy="300" r="24" fill="{c2}"/>' + ''.join(f'<rect x="490" y="{360 + i * 30}" width="20" height="20" rx="4" fill="#fff" opacity=".7"/>' for i in range(8))
    sp += '<rect x="470" y="720" width="60" height="50" rx="6" fill="#fff"/>'
    spine = page(hue, shadow(500, 820, 120, 24) + sp)
    op = '<rect x="150" y="260" width="340" height="520" rx="18" fill="#f5f6fa"/><rect x="510" y="260" width="340" height="520" rx="18" fill="#f5f6fa"/>'
    op += '<rect x="170" y="290" width="300" height="460" rx="10" fill="#e6e8ef"/>' + ''.join(f'<rect x="200" y="{330 + i * 30}" width="{240 - (i % 2) * 60}" height="10" rx="5" fill="#c7ccd7"/>' for i in range(6))
    if kind == 'disc':
        op += '<circle cx="680" cy="520" r="150" fill="url(#metal)"/><circle cx="680" cy="520" r="150" fill="' + c1 + '" opacity=".35"/><circle cx="680" cy="520" r="34" fill="#f5f6fa"/><circle cx="680" cy="520" r="14" fill="#c7ccd7"/>'
    else:
        op += f'<rect x="620" y="430" width="120" height="150" rx="12" fill="{c1}"/><rect x="636" y="446" width="88" height="80" rx="6" fill="{c2}"/><rect x="636" y="545" width="88" height="18" rx="3" fill="#1d2027" opacity=".5"/>'
        op += '<rect x="610" y="420" width="140" height="170" rx="16" fill="none" stroke="#c7ccd7" stroke-width="6"/>'
    opened = page(hue, shadow(500, 820, 400, 30) + op)
    return [back, spine, opened]


# ----------------------------------------------------------------------- GPU
def gpu_views(shroud, plate, ring, fans, hue):
    w = 600 if fans == 3 else 520
    x0 = 500 - w // 2
    bp = f'<rect x="{x0}" y="380" width="{w}" height="240" rx="14" fill="{plate}"/><rect x="{x0}" y="380" width="{w}" height="240" rx="14" fill="url(#vshade)"/>'
    bp += ''.join(f'<rect x="{x0 + 40 + i * 30}" y="420" width="14" height="80" rx="7" fill="#000" opacity=".3"/>' for i in range((w - 200) // 30))
    bp += f'<rect x="{x0 + 40}" y="530" width="{w - 200}" height="6" rx="3" fill="{ring}"/>'
    bp += ''.join(f'<circle cx="{x0 + w - 120 + (i % 3) * 30}" cy="{440 + (i // 3) * 30}" r="5" fill="#000" opacity=".35"/>' for i in range(9))
    bp += f'<rect x="{x0 + w - 300}" y="620" width="220" height="16" fill="#d6a738"/>'  # PCIe fingers (mirrored side)
    bp += f'<rect x="{x0 - 26}" y="370" width="26" height="270" rx="4" fill="url(#metal)"/>'  # bracket on the left
    back = page(hue, shadow(500, 700, w // 2 + 20, 28) + bp)
    ed = f'<rect x="{x0}" y="460" width="{w}" height="110" rx="12" fill="{shroud}"/>'
    ed += ''.join(f'<rect x="{x0 + 20 + i * 12}" y="474" width="6" height="82" fill="#000" opacity=".35"/>' for i in range((w - 40) // 12))
    ed += f'<rect x="{x0}" y="460" width="{w}" height="14" rx="6" fill="{plate}"/>'
    ed += f'<rect x="{x0 + 60}" y="490" width="{w - 260}" height="10" rx="5" fill="{ring}"/><rect x="{x0 + 60}" y="490" width="{w - 260}" height="10" rx="5" fill="{ring}" opacity=".6" filter="blur(6px)"/>'
    ed += f'<rect x="{x0 + w - 160}" y="436" width="70" height="30" rx="4" fill="#111318"/>' + ''.join(f'<rect x="{x0 + w - 152 + i * 11}" y="442" width="6" height="16" fill="#3b3f49"/>' for i in range(6))
    ed += f'<rect x="{x0 + w}" y="440" width="22" height="150" rx="3" fill="url(#metal)"/>'
    edge = page(hue, shadow(500, 640, w // 2 + 20, 24) + ed)
    io = '<rect x="380" y="200" width="240" height="600" rx="10" fill="url(#metal)"/><rect x="380" y="200" width="240" height="600" rx="10" fill="url(#vshade)"/>'
    io += f'<rect x="620" y="300" width="70" height="420" rx="8" fill="{shroud}"/>'
    ports = ['dp', 'dp', 'dp', 'hdmi'] if fans == 3 else ['dp', 'dp', 'hdmi']
    for i, kind in enumerate(ports):
        y = 260 + i * 70
        if kind == 'dp':
            io += f'<path d="M430 {y} h120 v40 h-108 l-12 -12z" fill="#16181e"/><path d="M440 {y + 10} h100 v20 h-92 l-8 -8z" fill="#3b3f49"/>'
        else:
            io += f'<path d="M430 {y} h120 l-12 40 h-96z" fill="#16181e"/><path d="M440 {y + 10} h100 l-8 20 h-84z" fill="#3b3f49"/>'
    io += ''.join(f'<rect x="{410 + (i % 8) * 22}" y="{560 + (i // 8) * 34}" width="12" height="24" rx="6" fill="#5b616e"/>' for i in range(40))
    bracket = page(hue, shadow(520, 840, 200, 26) + io)
    return [back, edge, bracket]


# -------------------------------------------------------------- per product
VIEWS = {
    'GM-PS5-SLIM-STD': lambda: ps5_slim_views(),
    'GM-PS5-SLIM-DIG': lambda: ps5_slim_views('#5aa9ff'),
    'GM-PS5-PRO': lambda: ps5_pro_views(),
    'GM-PS-PORTAL': lambda: portal_views(),
    'GM-DS-WHITE': lambda: controller_views('#f6f7fb', '#1d2027', '#5aa9ff', '#3b82f6'),
    'GM-DS-RED': lambda: controller_views('#f6f7fb', '#c81e3c', '#ff6b85', '#ff4b45', True),
    'GM-DS-BLUE': lambda: controller_views('#f6f7fb', '#2563eb', '#60a5fa', '#3b82f6', True),
    'GM-DS-EDGE': lambda: controller_views('#f6f7fb', '#0f1115', '#a78bfa', '#7c5cff', True, paddles=True),
    'GM-NSW2': lambda: switch_views('s2'),
    'GM-NSW2-MKW': lambda: switch_views('s2'),
    'GM-NSW-OLED': lambda: switch_views('oled'),
    'GM-NSW-LITE': lambda: switch_views('lite'),
    'GM-NSW2-PRO': lambda: controller_views('#24272f', '#15171d', '#00d4ff', '#00b7e8', paddles=True),
    'GM-JOYCON2': lambda: joycon_views(),
    'GM-JBL-FLIP-BK': lambda: flip_views('#1d2027', '#2b2f3a', '#3b3f49', '#7c5cff'),
    'GM-JBL-FLIP-BL': lambda: flip_views('#1e40af', '#2546b8', '#3b5bd0', '#3b82f6'),
    'GM-JBL-FLIP-RD': lambda: flip_views('#b91c3a', '#c8264a', '#de4565', '#ff4b45'),
    'GM-JBL-GO': lambda: go_views(),
    'GM-HEADSET': lambda: headset_views(),
    'GM-GC-PSN50': lambda: card_views('#1d4ed8', '#60a5fa', '#3b82f6', 'CHF 50'),
    'GM-GC-ESHOP50': lambda: card_views('#dc2626', '#fca5a5', '#ff4b45', 'CHF 50'),
    'GM-GAME-MKW': lambda: game_views('#e63950', '#ffd34d', '#ff4b45', 'cart'),
    'GM-GAME-FC': lambda: game_views('#15803d', '#a3e635', '#2ed47a', 'disc'),
    'GM-RTX5070': lambda: gpu_views('#1d2027', '#2a2e3a', '#8b5cf6', 3, '#7c5cff'),
    'GM-RTX5080': lambda: gpu_views('#1d2027', '#2a2e3a', '#ff4b45', 3, '#ff4b45'),
    'GM-RX9070XT': lambda: gpu_views('#e9ecf3', '#f4f5f9', '#00d4ff', 2, '#00b7e8'),
}

if __name__ == '__main__':
    out = sys.argv[1]
    os.makedirs(out, exist_ok=True)
    for sku, make in VIEWS.items():
        for n, body in enumerate(make(), start=2):
            with open(os.path.join(out, f'{sku.lower()}-view-{n}.svg'), 'w') as f:
                f.write(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" width="1000" height="1000">{body}</svg>')
    print(len(VIEWS) * 3, 'views')
