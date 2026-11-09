#!/usr/bin/env python3
"""Extract translatable strings from the Premium Shop theme into a .pot file.

Handles __(), _e(), esc_html__(), esc_html_e(), esc_attr__(), esc_attr_e(),
_x(), esc_html_x(), esc_attr_x(), _n() with the 'premium-shop' domain, and
the setup assistant helper $t( '...' ).
"""
import os, re, sys, json, datetime

THEME = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'premium-shop')
DOMAIN = 'premium-shop'

STR = r"'((?:[^'\\]|\\.)*)'"
SP = r'\s*'
patterns = [
    ('simple', re.compile(r'\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(' + SP + STR + SP + ',' + SP + "'" + DOMAIN + "'" + SP + r'\)')),
    ('context', re.compile(r'\b(?:_x|esc_html_x|esc_attr_x)\(' + SP + STR + SP + ',' + SP + STR + SP + ',' + SP + "'" + DOMAIN + "'" + SP + r'\)')),
    ('plural', re.compile(r'\b_n\(' + SP + STR + SP + ',' + SP + STR + SP + ',' + r'[^;]*?' + "'" + DOMAIN + "'" + SP + r'\)')),
    ('setup', re.compile(r'\$t\(' + SP + STR + SP + r'\)')),
]

def unescape(s):
    return s.replace("\\'", "'").replace('\\\\', '\\')

def collect():
    entries = {}
    for base, _, files in os.walk(THEME):
        for f in sorted(files):
            if not f.endswith('.php'):
                continue
            path = os.path.join(base, f)
            rel = os.path.relpath(path, THEME)
            src = open(path, encoding='utf-8').read()
            for kind, rx in patterns:
                for m in rx.finditer(src):
                    line = src.count('\n', 0, m.start()) + 1
                    before = src[max(0, m.start() - 300):m.start()]
                    tc = re.findall(r'/\*\s*translators:(.*?)\*/', before.split(';')[-1] if ';' in before else before, re.S)
                    if kind == 'context':
                        key = (unescape(m.group(1)), unescape(m.group(2)), None)
                    elif kind == 'plural':
                        key = (unescape(m.group(1)), None, unescape(m.group(2)))
                    else:
                        key = (unescape(m.group(1)), None, None)
                    e = entries.setdefault(key, {'refs': [], 'comment': ''})
                    e['refs'].append(f'{rel}:{line}')
                    if tc and not e['comment']:
                        e['comment'] = 'translators:' + tc[-1].strip().rstrip()
    return entries

def po_str(s):
    s = s.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\t', '\\t')
    return '"' + s + '"'

def header(lang=None, plural='nplurals=2; plural=(n != 1);'):
    now = datetime.datetime.utcnow().strftime('%Y-%m-%d %H:%M+0000')
    h = [
        'msgid ""', 'msgstr ""',
        '"Project-Id-Version: Premium Shop 1.0.0\\n"',
        '"Report-Msgid-Bugs-To: \\n"',
        f'"POT-Creation-Date: {now}\\n"',
        f'"PO-Revision-Date: {now}\\n"',
        '"Last-Translator: Premium Shop Studio\\n"',
        '"Language-Team: Premium Shop Studio\\n"',
    ]
    if lang:
        h.append(f'"Language: {lang}\\n"')
    h += ['"MIME-Version: 1.0\\n"', '"Content-Type: text/plain; charset=UTF-8\\n"', '"Content-Transfer-Encoding: 8bit\\n"',
          f'"Plural-Forms: {plural}\\n"', '"X-Domain: premium-shop\\n"', '']
    return '\n'.join(h)

def write_po(path, entries, translations=None, lang=None):
    out = [header(lang)]
    for (msgid, ctx, plural), e in sorted(entries.items(), key=lambda kv: kv[1]['refs'][0]):
        if e['comment']:
            out.append('#. ' + e['comment'].replace('\n', ' '))
        out.append('#: ' + ' '.join(e['refs'][:4]))
        if ctx:
            out.append('msgctxt ' + po_str(ctx))
        out.append('msgid ' + po_str(msgid))
        tr = (translations or {}).get(msgid)
        if plural:
            out.append('msgid_plural ' + po_str(plural))
            if isinstance(tr, list):
                out.append('msgstr[0] ' + po_str(tr[0]))
                out.append('msgstr[1] ' + po_str(tr[1]))
            else:
                out.append('msgstr[0] ""')
                out.append('msgstr[1] ""')
        else:
            out.append('msgstr ' + po_str(tr if isinstance(tr, str) else ''))
        out.append('')
    open(path, 'w', encoding='utf-8').write('\n'.join(out))

if __name__ == '__main__':
    entries = collect()
    write_po(os.path.join(THEME, 'languages', 'premium-shop.pot'), entries)
    json.dump(sorted({k[0]: 1 for k in entries}.keys()), open(os.path.join(os.path.dirname(__file__), 'strings.json'), 'w'), ensure_ascii=False, indent=0)
    print(len(entries), 'strings')
