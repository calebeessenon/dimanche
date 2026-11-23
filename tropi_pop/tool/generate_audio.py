"""Génère les effets sonores et la musique originaux de Tropi Pop (synthèse).

Tous les sons sont créés par ce script : aucun droit tiers. Usage :
    python3 tool/generate_audio.py
"""
import wave
import numpy as np

SR = 22050
OUT = "assets/audio/"


def env(n, a=0.005, r=0.08):
    e = np.ones(n)
    na, nr = min(n, int(a * SR)), min(n, int(r * SR))
    if na: e[:na] = np.linspace(0, 1, na)
    if nr: e[-nr:] *= np.linspace(1, 0, nr)
    return e


def tone(freq, dur, kind="sine", vol=0.5, slide=None):
    n = int(dur * SR)
    t = np.arange(n) / SR
    f = np.full(n, freq) if slide is None else np.linspace(freq, slide, n)
    ph = 2 * np.pi * np.cumsum(f) / SR
    if kind == "sine":
        w = np.sin(ph)
    elif kind == "tri":
        w = 2 / np.pi * np.arcsin(np.sin(ph))
    elif kind == "square":
        w = np.sign(np.sin(ph)) * 0.5
    else:
        w = np.sin(ph) + 0.3 * np.sin(2 * ph)
    return w * vol


def save(name, data):
    data = np.clip(data, -1, 1)
    pcm = (data * 32000).astype(np.int16)
    with wave.open(OUT + name, "wb") as f:
        f.setnchannels(1)
        f.setsampwidth(2)
        f.setframerate(SR)
        f.writeframes(pcm.tobytes())


def seq(parts):
    return np.concatenate(parts)


def note(m):  # MIDI -> Hz
    return 440 * 2 ** ((m - 69) / 12)


# --- Effets ---------------------------------------------------------------
w = tone(500, 0.09, "sine", 0.5, slide=900); save("swap.wav", w * env(len(w), r=0.04))
w = tone(300, 0.18, "square", 0.25, slide=180); save("invalid.wav", w * env(len(w)))
for i, base in enumerate([660, 740, 830, 880, 990, 1100]):
    w = tone(base, 0.12, "tri", 0.55, slide=base * 1.6) + tone(base * 2, 0.12, "sine", 0.15)
    save(f"pop{i}.wav", w * env(len(w), r=0.07))
noise = np.random.default_rng(1).uniform(-1, 1, int(0.45 * SR))
boom = tone(140, 0.45, "sine", 0.7, slide=40) + noise * np.linspace(0.5, 0, len(noise))
save("bomb.wav", boom * env(len(boom), r=0.2))
w = tone(400, 0.3, "saw", 0.4, slide=1800); save("line.wav", w * env(len(w), r=0.12))
w = seq([tone(note(m), 0.07, "tri", 0.45) * env(int(0.07 * SR), r=0.02) for m in [72, 76, 79, 84, 88, 91]])
save("rainbow.wav", w)
w = seq([tone(note(m), 0.06, "sine", 0.5) * env(int(0.06 * SR), r=0.02) for m in [84, 91]]); save("special.wav", w)
win = seq([tone(note(m), d, "tri", 0.5) * env(int(d * SR), r=0.05) for m, d in
           [(72, .12), (76, .12), (79, .12), (84, .3), (79, .1), (84, .45)]])
save("win.wav", win)
lose = seq([tone(note(m), d, "tri", 0.45) * env(int(d * SR), r=0.06) for m, d in
            [(67, .2), (66, .2), (65, .2), (64, .5)]])
save("lose.wav", lose)
w = tone(1200, 0.05, "sine", 0.4) * env(int(0.05 * SR), r=0.02); save("tap.wav", w)
w = seq([tone(note(m), 0.08, "sine", 0.45) * env(int(0.08 * SR), r=0.03) for m in [88, 93, 100]]); save("coin.wav", w)
w = tone(note(79), 0.25, "tri", 0.5) * env(int(0.25 * SR), r=0.15); save("star.wav", w)

# --- Musique : boucle tropicale (≈ 19 s, 100 BPM) -------------------------
bpm = 100
beat = 60 / bpm
bars = 8
total = int(bars * 4 * beat * SR)
music = np.zeros(total)


def add(start_beat, data):
    s = int(start_beat * beat * SR)
    e = min(total, s + len(data))
    music[s:e] += data[: e - s]


chords = [(60, 64, 67), (57, 60, 64), (65, 69, 72), (67, 71, 74)] * 2  # C Am F G
melody = [76, 79, 81, 79, 76, 74, 72, 74, 76, 76, 72, 69, 71, 72, 74, 71,
          76, 79, 84, 83, 81, 79, 76, 79, 81, 79, 77, 76, 74, 72, 74, 72]
for b, ch in enumerate(chords):
    root = ch[0] - 24
    for k in range(4):  # basse "reggae"
        d = beat * 0.45
        add(b * 4 + k, tone(note(root if k % 2 == 0 else root + 7), d, "sine", 0.32) * env(int(d * SR), r=0.05))
    for k in [0.5, 1.5, 2.5, 3.5]:  # accords en contretemps (marimba)
        d = beat * 0.3
        c = sum(tone(note(m), d, "sine", 0.09) + tone(note(m) * 4, d, "sine", 0.015) for m in ch)
        add(b * 4 + k, c * env(int(d * SR), a=0.002, r=0.2))
    for k in range(4):  # mélodie
        m = melody[(b * 4 + k) % len(melody)]
        d = beat * 0.9
        add(b * 4 + k, (tone(note(m), d, "tri", 0.13) + tone(note(m) * 2, d, "sine", 0.03)) * env(int(d * SR), r=0.3))
    for k in range(8):  # shaker
        n = np.random.default_rng(b * 8 + k).uniform(-1, 1, int(0.04 * SR)) * 0.05
        add(b * 4 + k * 0.5, n * np.linspace(1, 0, len(n)))
music /= max(1.0, np.abs(music).max() / 0.8)
save("music.wav", music)
print("ok")
