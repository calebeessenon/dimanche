"""Original product illustrations for the gaming catalogue (no brand logos).

Usage: python3 gaming_art.py OUT_DIR  -> writes one SVG per image.
Render to JPG with render.js (Playwright).
"""
import os
import sys

OUT = sys.argv[1]


def studio(hue='#7c5cff', w=1000, h=1000):
    lines = ''.join(f'<line x1="0" y1="{int(h*0.78 + i*i*6)}" x2="{w}" y2="{int(h*0.78 + i*i*6)}"/>' for i in range(8))
    lines += ''.join(f'<line x1="{w/2 + i*30}" y1="{int(h*0.78)}" x2="{w/2 + i*160}" y2="{h}"/>' for i in range(-9, 10))
    return f'''<defs>
<radialGradient id="bg" cx="50%" cy="42%" r="78%"><stop offset="0" stop-color="#3a4290"/><stop offset=".55" stop-color="#262d6a"/><stop offset="1" stop-color="#181d48"/></radialGradient>
<radialGradient id="glow" cx="50%" cy="45%" r="45%"><stop offset="0" stop-color="{hue}" stop-opacity=".45"/><stop offset="1" stop-color="{hue}" stop-opacity="0"/></radialGradient>
<radialGradient id="glow2" cx="85%" cy="10%" r="40%"><stop offset="0" stop-color="#00d4ff" stop-opacity=".18"/><stop offset="1" stop-color="#00d4ff" stop-opacity="0"/></radialGradient>
<radialGradient id="sh" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="{hue}" stop-opacity=".55"/><stop offset=".5" stop-color="#000" stop-opacity=".45"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
<linearGradient id="white" x1="0" x2="1"><stop offset="0" stop-color="#ffffff"/><stop offset=".65" stop-color="#eef0f6"/><stop offset="1" stop-color="#c9cfdb"/></linearGradient>
<linearGradient id="dark" x1="0" x2="1"><stop offset="0" stop-color="#2a2e3a"/><stop offset="1" stop-color="#15171e"/></linearGradient>
<linearGradient id="gshade" x1="0" x2="1"><stop offset="0" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".25"/></linearGradient>
</defs><rect width="{w}" height="{h}" fill="url(#bg)"/><rect width="{w}" height="{h}" fill="url(#glow)"/><rect width="{w}" height="{h}" fill="url(#glow2)"/>
<g opacity=".18" stroke="{hue}" stroke-width="1.5">{lines}</g>'''


def shadow(cx, cy, rx, ry):
    return f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="url(#sh)"/>'


def pad(x, y, s, body='#f6f7fb', core='#1d2027', accent='#5aa9ff', two_tone=False):
    """Game controller, top view (scaled group)."""
    g = f'<g transform="translate({x} {y}) scale({s})">'
    g += f'<path d="M150 40 C 220 0 500 0 570 40 C 650 80 710 240 720 330 C 730 410 660 450 610 400 L 520 300 H 200 L 110 400 C 60 450 -10 410 0 330 C 10 240 70 80 150 40Z" fill="{body}" stroke="#d3d8e3" stroke-opacity=".5" stroke-width="3"/>'
    if two_tone:
        g += f'<path d="M150 40 C 90 70 30 200 10 330 C 0 410 60 450 110 400 L 200 300 Z" fill="{core}"/>'
        g += f'<path d="M570 40 C 630 70 690 200 710 330 C 720 410 660 450 610 400 L 520 300 Z" fill="{core}"/>'
    g += f'<path d="M220 60 H500 C 520 60 530 80 525 100 L 500 200 H 220 L 195 100 C 190 80 200 60 220 60Z" fill="{core}"/>'
    g += '<rect x="280" y="75" width="160" height="90" rx="14" fill="#2c3039"/>'
    g += f'<path d="M275 180 Q 360 210 445 180" stroke="{accent}" stroke-width="5" fill="none"/>'
    for cx in (255, 465):
        g += f'<circle cx="{cx}" cy="255" r="44" fill="{core}"/><circle cx="{cx}" cy="255" r="28" fill="#30343d"/><circle cx="{cx}" cy="255" r="18" fill="#3d424c"/>'
    for dx, dy in [(0, -34), (0, 34), (-34, 0), (34, 0)]:
        g += f'<rect x="{122+dx-13}" y="{140+dy-13}" width="26" height="26" rx="6" fill="#c9ced9"/>'
        g += f'<circle cx="{598+dx}" cy="{140+dy}" r="15" fill="#c9ced9"/>'
    return g + '</g>'


def screen(x, y, w, h, rid, sky='#7fd3ff', hill='#5cc96b', hill2='#3fae55'):
    return f'''<clipPath id="{rid}"><rect x="{x}" y="{y}" width="{w}" height="{h}" rx="10"/></clipPath>
<g clip-path="url(#{rid})"><rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{sky}"/>
<circle cx="{x+w*0.78}" cy="{y+h*0.28}" r="{h*0.13}" fill="#ffe27a"/>
<path d="M{x} {y+h*0.7} Q{x+w*0.2} {y+h*0.45} {x+w*0.42} {y+h*0.68} T{x+w} {y+h*0.6} V{y+h} H{x}Z" fill="{hill}"/>
<path d="M{x} {y+h*0.82} Q{x+w*0.3} {y+h*0.68} {x+w*0.6} {y+h*0.84} T{x+w} {y+h*0.8} V{y+h} H{x}Z" fill="{hill2}"/>
<rect x="{x+w*0.3}" y="{y+h*0.52}" width="{w*0.06}" height="{h*0.18}" rx="4" fill="#ff5a3d"/>
<circle cx="{x+w*0.33}" cy="{y+h*0.48}" r="{h*0.06}" fill="#ffcf9e"/>
<rect x="{x+w*0.55}" y="{y+h*0.4}" width="{w*0.1}" height="{w*0.1}" rx="6" fill="#ffcc33" stroke="#c98b10" stroke-width="3"/>
</g>'''


def ps5_slim():
    s = studio('#3b82f6')
    s += shadow(470, 830, 300, 34)
    # Slim console, standing, white panels with black band.
    s += '<g transform="translate(330 170)">'
    s += '<rect x="110" y="0" width="80" height="640" rx="14" fill="url(#dark)"/>'
    s += '<path d="M110 20 C 60 14 40 40 36 80 L 24 600 C 24 626 44 640 70 640 L 110 640Z" fill="url(#white)"/>'
    s += '<path d="M190 20 C 240 14 260 40 264 80 L 276 600 C 276 626 256 640 230 640 L 190 640Z" fill="url(#white)"/>'
    s += '<rect x="36" y="300" width="74" height="4" fill="#d5d9e3"/><rect x="190" y="300" width="74" height="4" fill="#d5d9e3"/>'
    s += '<circle cx="150" cy="560" r="7" fill="#5aa9ff"/><rect x="132" y="40" width="36" height="5" rx="2" fill="#3b3f4a"/></g>'
    s += pad(560, 600, .42)
    return s


def ps5_pro():
    s = studio('#7c5cff')
    s += shadow(500, 830, 300, 34)
    s += '<g transform="translate(330 150)">'
    s += '<rect x="0" y="0" width="340" height="660" rx="28" fill="url(#white)"/>'
    s += '<rect x="0" y="210" width="340" height="40" fill="url(#dark)"/><rect x="0" y="420" width="340" height="40" fill="url(#dark)"/>'
    s += '<rect x="0" y="0" width="340" height="660" rx="28" fill="none" stroke="#fff" stroke-opacity=".5" stroke-width="3"/>'
    s += '<circle cx="170" cy="600" r="8" fill="#5aa9ff"/></g>'
    return s


def portal():
    s = studio('#5aa9ff')
    s += shadow(500, 780, 360, 34)
    s += pad(140, 380, 1.0, two_tone=False)
    s += '<rect x="300" y="250" width="400" height="260" rx="18" fill="#1b1d24"/>'
    s += screen(316, 266, 368, 228, 'pscr', sky='#ffb37a', hill='#8a5cff', hill2='#5b3bd6')
    return s


def switch2(main='#2b2f3a', left='#21242c', right='#21242c', accent_l='#00b7e8', accent_r='#ff4b45', rid='s2'):
    s = f'<path d="M110 420 a100 100 0 0 1 100 -100 h50 v360 h-50 a100 100 0 0 1 -100 -100Z" fill="{left}"/>'
    s += f'<path d="M740 320 h50 a100 100 0 0 1 100 100 v160 a100 100 0 0 1 -100 100 h-50Z" fill="{right}"/>'
    s += f'<rect x="255" y="324" width="6" height="352" fill="{accent_l}"/><rect x="739" y="324" width="6" height="352" fill="{accent_r}"/>'
    s += f'<rect x="260" y="320" width="480" height="360" rx="16" fill="{main}"/>'
    s += '<rect x="274" y="334" width="452" height="332" rx="10" fill="#0b0c11"/>'
    s += screen(288, 348, 424, 304, rid)
    s += '<circle cx="185" cy="430" r="28" fill="#3b3f49"/><circle cx="185" cy="430" r="18" fill="#4a4f5b"/>'
    s += '<circle cx="815" cy="575" r="28" fill="#3b3f49"/><circle cx="815" cy="575" r="18" fill="#4a4f5b"/>'
    for dx, dy in [(0, -30), (0, 30), (-30, 0), (30, 0)]:
        s += f'<circle cx="{190+dx}" cy="{570+dy}" r="11" fill="#4a4f5b"/><circle cx="{812+dx}" cy="{440+dy}" r="11" fill="#4a4f5b"/>'
    return s


def nsw2():
    s = studio('#ff4b45')
    s += shadow(500, 760, 400, 38)
    s += switch2()
    return s


def nsw2_bundle():
    s = studio('#ff4b45')
    s += shadow(500, 840, 420, 38)
    s += '<g transform="translate(60 120) scale(.82)">' + switch2(rid='s2b') + '</g>'
    # Game case in front.
    s += '<g transform="rotate(8 760 640)"><rect x="660" y="470" width="220" height="330" rx="14" fill="#f5f6fa"/><rect x="672" y="510" width="196" height="278" rx="8" fill="#e63950"/>'
    s += '<circle cx="770" cy="620" r="54" fill="#ffd34d"/><path d="M700 720 h140 l-20 40 h-100z" fill="#1d2027" opacity=".5"/><rect x="672" y="478" width="196" height="24" rx="4" fill="#e63950"/></g>'
    return s


def oled(rid='o'):
    s = studio('#9b5de5')
    s += shadow(500, 720, 380, 36)
    s += '<path d="M150 420 a90 90 0 0 1 90 -90 h40 v330 h-40 a90 90 0 0 1 -90 -90Z" fill="#f2f3f7"/>'
    s += '<path d="M720 330 h40 a90 90 0 0 1 90 90 v150 a90 90 0 0 1 -90 90 h-40Z" fill="#f2f3f7"/>'
    s += '<rect x="280" y="330" width="440" height="330" rx="14" fill="#f2f3f7"/><rect x="292" y="342" width="416" height="306" rx="8" fill="#0d0f14"/>'
    s += screen(304, 356, 392, 278, rid)
    s += '<circle cx="215" cy="430" r="24" fill="#2a2d35"/><circle cx="785" cy="555" r="24" fill="#2a2d35"/>'
    return s


def lite(c='#ffcc33', rid='l'):
    s = studio('#ffcc33')
    s += shadow(500, 690, 360, 38)
    s += f'<rect x="150" y="340" width="700" height="310" rx="150" fill="{c}"/>'
    s += '<rect x="300" y="365" width="400" height="260" rx="12" fill="#14161c"/>'
    s += screen(316, 380, 368, 230, rid)
    s += '<circle cx="230" cy="430" r="24" fill="#20252b"/><circle cx="770" cy="560" r="24" fill="#20252b"/>'
    for dx, dy in [(0, -26), (0, 26), (-26, 0), (26, 0)]:
        s += f'<circle cx="{232+dx}" cy="{548+dy}" r="10" fill="#20252b"/><circle cx="{768+dx}" cy="{440+dy}" r="10" fill="#20252b"/>'
    return s


def pad_img(body, core, accent, hue, two_tone=False):
    s = studio(hue)
    s += shadow(500, 780, 340, 34)
    s += pad(140, 330, 1.0, body, core, accent, two_tone)
    return s


def pro_controller():
    s = studio('#00b7e8')
    s += shadow(500, 780, 340, 34)
    s += pad(140, 330, 1.0, '#24272f', '#15171d', '#00d4ff')
    return s


def joycons():
    s = studio('#00b7e8')
    s += shadow(500, 800, 300, 30)
    for x, c, rot in [(330, '#00b7e8', -8), (530, '#ff4b45', 8)]:
        s += f'<g transform="rotate({rot} {x+70} 520)"><rect x="{x}" y="260" width="140" height="520" rx="70" fill="{c}"/><rect x="{x}" y="260" width="140" height="520" rx="70" fill="url(#gshade)"/>'
        s += f'<circle cx="{x+70}" cy="400" r="30" fill="#2a2d35"/><circle cx="{x+70}" cy="400" r="20" fill="#3b3f49"/>'
        for dx, dy in [(0, -30), (0, 30), (-30, 0), (30, 0)]:
            s += f'<circle cx="{x+70+dx}" cy="{560+dy}" r="11" fill="#2a2d35"/>'
        s += '</g>'
    return s


def speaker(body='#1d2027', grill='#2b2f3a', ring='#3b3f49', hue='#7c5cff'):
    s = studio(hue)
    s += shadow(500, 770, 380, 36)
    # Cylinder speaker lying horizontally, fabric grille, passive radiator ends.
    s += f'<rect x="170" y="400" width="660" height="300" rx="150" fill="{body}"/>'
    s += f'<rect x="200" y="420" width="600" height="260" rx="130" fill="{grill}"/>'
    s += f'<g opacity=".35" fill="{ring}">' + ''.join(f'<circle cx="{220 + (i % 30) * 20}" cy="{440 + (i // 30) * 20}" r="3"/>' for i in range(30 * 12)) + '</g>'
    s += f'<rect x="170" y="400" width="660" height="300" rx="150" fill="url(#gshade)"/>'
    for cx in (215, 785):
        s += f'<ellipse cx="{cx}" cy="550" rx="44" ry="128" fill="{ring}"/><ellipse cx="{cx}" cy="550" rx="28" ry="96" fill="{body}"/>'
    s += f'<rect x="440" y="400" width="120" height="22" rx="11" fill="{ring}"/>'
    s += '<rect x="470" y="404" width="12" height="14" rx="3" fill="#fff" opacity=".7"/><rect x="518" y="404" width="12" height="14" rx="3" fill="#fff" opacity=".7"/>'
    s += f'<rect x="300" y="700" width="400" height="22" rx="11" fill="{body}"/>'
    return s


def mini_speaker(body='#e63950', hue='#ff4b45'):
    s = studio(hue)
    s += shadow(500, 770, 260, 30)
    s += f'<rect x="300" y="360" width="400" height="390" rx="70" fill="{body}"/>'
    s += f'<rect x="300" y="360" width="400" height="390" rx="70" fill="url(#gshade)"/>'
    s += '<circle cx="500" cy="560" r="130" fill="#000" opacity=".25"/><circle cx="500" cy="560" r="100" fill="#000" opacity=".2"/>'
    s += f'<g opacity=".45" fill="#000">' + ''.join(f'<circle cx="{400 + (i % 11) * 20}" cy="{460 + (i // 11) * 20}" r="3"/>' for i in range(11 * 11)) + '</g>'
    s += '<rect x="560" y="300" width="40" height="80" rx="20" fill="none" stroke="#1d2027" stroke-width="14"/>'
    return s


def gift_card(c1, c2, hue, symbol):
    s = studio(hue)
    s += shadow(500, 780, 320, 30)
    s += f'<g transform="rotate(-8 500 520)"><rect x="220" y="330" width="560" height="350" rx="30" fill="{c1}"/>'
    s += f'<rect x="220" y="330" width="560" height="350" rx="30" fill="url(#gshade)"/>'
    s += f'<circle cx="680" cy="420" r="160" fill="{c2}" opacity=".35"/>'
    s += f'<text x="270" y="430" font-family="Helvetica,Arial,sans-serif" font-size="46" font-weight="700" fill="#fff">GIFT CARD</text>'
    s += f'<text x="270" y="630" font-family="Helvetica,Arial,sans-serif" font-size="80" font-weight="700" fill="#fff">{symbol}</text>'
    s += '<rect x="620" y="560" width="120" height="80" rx="12" fill="#ffd34d"/><rect x="636" y="580" width="88" height="40" rx="6" fill="#c9a227"/></g>'
    # Ribbon
    s += '<path d="M640 300 l60 120 l-60 -20 l-30 50 l-30 -150z" fill="#ffd34d" opacity=".9"/>'
    return s


def game_box(c1, c2, hue, rid):
    s = studio(hue)
    s += shadow(500, 820, 260, 30)
    s += '<rect x="330" y="200" width="340" height="600" rx="18" fill="#f5f6fa"/>'
    s += f'<rect x="346" y="240" width="308" height="544" rx="10" fill="{c1}"/>'
    s += f'<rect x="346" y="208" width="308" height="26" rx="5" fill="{c2}"/>'
    s += f'<circle cx="500" cy="470" r="110" fill="{c2}" opacity=".9"/>'
    s += f'<path d="M346 640 Q500 560 654 640 V784 H346Z" fill="#000" opacity=".2"/>'
    s += '<path d="M420 500 l40 -80 l40 60 l30 -40 l50 70z" fill="#fff" opacity=".85"/>'
    return s


FILES = {
    'ps5-slim': ps5_slim(),
    'ps5-pro': ps5_pro(),
    'ps-portal': portal(),
    'switch2': nsw2(),
    'switch2-bundle': nsw2_bundle(),
    'switch-oled-white': oled(),
    'switch-lite-yellow': lite(),
    'dualsense-white': pad_img('#f6f7fb', '#1d2027', '#5aa9ff', '#3b82f6'),
    'dualsense-red': pad_img('#f6f7fb', '#c81e3c', '#ff6b85', '#ff4b45', True),
    'dualsense-blue': pad_img('#f6f7fb', '#2563eb', '#60a5fa', '#3b82f6', True),
    'dualsense-edge': pad_img('#f6f7fb', '#0f1115', '#a78bfa', '#7c5cff', True),
    'pro-controller': pro_controller(),
    'joycon-pair': joycons(),
    'speaker-black': speaker(),
    'speaker-blue': speaker('#1e40af', '#2546b8', '#3b5bd0', '#3b82f6'),
    'speaker-red': speaker('#b91c3a', '#c8264a', '#de4565', '#ff4b45'),
    'speaker-mini': mini_speaker(),
    'giftcard-blue': gift_card('#1d4ed8', '#60a5fa', '#3b82f6', 'CHF 50'),
    'giftcard-red': gift_card('#dc2626', '#fca5a5', '#ff4b45', 'CHF 50'),
    'game-racing': game_box('#e63950', '#ffd34d', '#ff4b45', 'g1'),
    'game-football': game_box('#15803d', '#a3e635', '#2ed47a', 'g2'),
    'game-adventure': game_box('#7c3aed', '#f0abfc', '#9b5de5', 'g3'),
}

os.makedirs(OUT, exist_ok=True)
for name, body in FILES.items():
    with open(f'{OUT}/{name}.svg', 'w') as f:
        f.write(f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000" width="1000" height="1000">{body}</svg>')
print(len(FILES))
