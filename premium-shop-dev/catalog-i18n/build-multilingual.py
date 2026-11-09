#!/usr/bin/env python3
"""Build a multilingual WooCommerce import file from a French export.

Main product texts = German (default shop language); French (original),
English and Spanish go to the theme's translation fields
(Meta: _ps_name_xx / _ps_short_xx / _ps_desc_xx). Everything else (prices,
categories, images, SKUs, firewood data) is prepared like clean-import.py.

Usage: build-multilingual.py export.csv out.csv [--no-images]
"""
import csv, glob, html, importlib.util, json, os, re, sys

HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('clean_import', os.path.join(HERE, '..', 'clean-import.py'))
ci = importlib.util.module_from_spec(spec)
spec.loader.exec_module(ci)

LANGS = ('de', 'en', 'es')
MAIN = 'de'


def load_translations():
    segments = json.load(open(os.path.join(HERE, 'segments-fr.json'), encoding='utf-8'))
    tr = {}
    for path in sorted(glob.glob(os.path.join(HERE, 'batch-*.json'))):
        for idx, values in json.load(open(path, encoding='utf-8')).items():
            tr[segments[int(idx)]] = values
    missing = [s for s in segments if s not in tr]
    if missing:
        sys.exit('Missing translations: %d (first: %r)' % (len(missing), missing[0][:80]))
    return tr


def translate_text(text, tr, lang):
    """Translate a plain text (name / short description)."""
    t = html.unescape(text).strip()
    return tr[t][lang] if t in tr else t


def translate_description(desc, tr, lang):
    """Replace every text node of the description HTML by its translation."""
    d = desc.replace('\\n', '\n')
    out = []
    for token in re.split(r'(<[^>]+>)', d):
        if not token or token.startswith('<'):
            out.append(token)
            continue
        raw = html.unescape(token)
        key = raw.strip()
        if key in tr:
            lead = token[:len(token) - len(token.lstrip())]
            trail = token[len(token.rstrip()):]
            value = tr[key][lang]
            # Text that contained escaped markup is replaced by clean HTML / text.
            out.append(lead + (value if '<' in key else html.escape(value, quote=False)) + trail)
        else:
            out.append(token)
    return ''.join(out)


def short_from_html(desc):
    return ci.short_from(desc)


def main():
    src, dst = sys.argv[1], sys.argv[2]
    no_images = '--no-images' in sys.argv
    tr = load_translations()
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig', newline='')))

    meta_keys = ['_ps_species', '_ps_log_length', '_ps_moisture', '_ps_drying', '_ps_unit', '_ps_unit_qty']
    i18n_cols = []
    for lang in ('fr',) + tuple(l for l in LANGS if l != MAIN):
        i18n_cols += ['Meta: _ps_name_' + lang, 'Meta: _ps_short_' + lang, 'Meta: _ps_desc_' + lang]

    out_rows = []
    for i, r in enumerate(rows, 1):
        r = {k.replace('\xa0', ' '): v for k, v in r.items()}
        o = {}
        for fr, en in ci.HEADERS.items():
            if fr in r:
                o[en] = r[fr]

        name_fr = html.unescape(r['Nom']).strip()
        desc_fr = r.get('Description', '')
        short_fr = r.get('Description courte', '').strip()

        texts = {'fr': {'name': name_fr, 'desc': desc_fr.replace('\\n', '\n'), 'short': short_fr or short_from_html(desc_fr)}}
        for lang in LANGS:
            desc = translate_description(desc_fr, tr, lang)
            short = translate_text(short_fr, tr, lang) if short_fr else short_from_html(desc)
            texts[lang] = {'name': translate_text(name_fr, tr, lang), 'desc': desc, 'short': short}

        o['Name'] = texts[MAIN]['name']
        o['Short description'] = texts[MAIN]['short']
        o['Description'] = texts[MAIN]['desc']
        o['SKU'] = o.get('SKU') or 'WH-%04d' % i
        if no_images:
            o['Images'] = ''

        meta = ci.firewood(name_fr, o.get('Categories', ''), desc_fr)
        for k in meta_keys:
            o['Meta: ' + k] = meta.get(k, '')
        for lang in ('fr',) + tuple(l for l in LANGS if l != MAIN):
            o['Meta: _ps_name_' + lang] = texts[lang]['name']
            o['Meta: _ps_short_' + lang] = texts[lang]['short']
            o['Meta: _ps_desc_' + lang] = texts[lang]['desc']
        out_rows.append(o)

    cols = [en for fr, en in ci.HEADERS.items() if any(en in o for o in out_rows)] + ['Meta: ' + k for k in meta_keys] + i18n_cols
    with open(dst, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.DictWriter(f, fieldnames=cols, extrasaction='ignore')
        w.writeheader()
        w.writerows(out_rows)
    print('%d products -> %s' % (len(out_rows), dst))


if __name__ == '__main__':
    main()
