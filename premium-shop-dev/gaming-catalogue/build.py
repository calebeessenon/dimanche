"""Build the WooCommerce CSV and the theme's PHP catalogue from catalogue.py.

python3 build.py THEME_DIR DIST_DIR
"""
import csv
import re
import os
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from catalogue import CATEGORIES, LABELS, PRODUCTS, description as base_description  # noqa: E402
from specs import ATTR, SPEC_LABELS, SPECS, gtin, value  # noqa: E402

THEME, DIST = sys.argv[1], sys.argv[2]
LANGS = ['fr', 'en', 'es']


GTIN = {p['sku']: gtin(i) for i, p in enumerate(PRODUCTS)}
MAX_ATTR = max(len(s[3]) for s in SPECS.values())


def gallery(p):
    sku = p['sku'].lower()
    return [p['img']] + [f'{sku}-{n}.jpg' for n in (2, 3, 4)]


def spec_rows(p, lang):
    brand, weight, (ln, wd, ht), attrs = SPECS[p['sku']]
    num = (lambda x: f'{x:g}'.replace('.', ',')) if lang in ('de', 'fr', 'es') else (lambda x: f'{x:g}')
    rows = [(SPEC_LABELS['brand'][lang], brand)]
    rows += [(ATTR[k][lang], value(v, lang)) for k, v in attrs]
    rows += [(SPEC_LABELS['weight'][lang], f'{num(weight)} kg'),
             (SPEC_LABELS['dims'][lang], f'{num(ln)} × {num(wd)} × {num(ht)} cm'),
             (SPEC_LABELS['gtin'][lang], GTIN[p['sku']]),
             (SPEC_LABELS['sku'][lang], p['sku'])]
    return rows


def description(p, lang):
    table = ''.join(f'<tr><th>{k}</th><td>{v}</td></tr>' for k, v in spec_rows(p, lang))
    return base_description(p, lang) + f"<h3>{SPEC_LABELS['title'][lang]}</h3><table class=\"ps-specs\"><tbody>{table}</tbody></table>"


def price(v):
    return '' if v is None else f'{v:.2f}'


# 1. CSV for Products → Import (shop language German, FR/EN/ES in the theme's translation fields).
header = ['Type', 'SKU', 'Name', 'Published', 'Is featured?', 'Visibility in catalog', 'Short description', 'Description',
          'Tax status', 'In stock?', 'Weight (kg)', 'Length (cm)', 'Width (cm)', 'Height (cm)', 'GTIN, UPC, EAN, or ISBN',
          'Sale price', 'Regular price', 'Categories', 'Tags', 'Brands', 'Images']
for n in range(1, MAX_ATTR + 1):
    header += [f'Attribute {n} name', f'Attribute {n} value(s)', f'Attribute {n} visible', f'Attribute {n} global']
for lang in LANGS:
    header += [f'Meta: _ps_name_{lang}', f'Meta: _ps_short_{lang}', f'Meta: _ps_desc_{lang}']

os.makedirs(DIST, exist_ok=True)
path = os.path.join(DIST, 'gaming-produits-import.csv')
with open(path, 'w', newline='', encoding='utf-8') as f:
    w = csv.writer(f)
    w.writerow(header)
    for p in PRODUCTS:
        row = ['simple', p['sku'], p['name']['de'], 1, 1 if p['featured'] else 0, 'visible', p['short']['de'], description(p, 'de'),
               'taxable', 1, f"{SPECS[p['sku']][1]:g}", *[f'{d:g}' for d in SPECS[p['sku']][2]], GTIN[p['sku']],
               price(p['sale']), price(p['price']), CATEGORIES[p['cat']]['de'], LABELS['gift']['de'], SPECS[p['sku']][0], ', '.join(gallery(p))]
        attrs = SPECS[p['sku']][3]
        for n in range(MAX_ATTR):
            if n < len(attrs):
                k, v = attrs[n]
                # Commas separate values; a decimal comma (2,4 GHz) is escaped.
                row += [ATTR[k]['de'], re.sub(r'(\d),(\d)', r'\1\\,\2', value(v, 'de')), 1, 0]
            else:
                row += ['', '', '', '']
        meta = []
        for lang in LANGS:
            meta += [p['name'][lang], p['short'][lang], description(p, lang)]
        w.writerow(row + meta)
print('csv', path, len(PRODUCTS))


# 2. PHP data for the theme's sample catalogue.
def php(v, ind=1):
    pad = '\t' * ind
    if isinstance(v, dict):
        inner = ''.join(f"{pad}\t{php(k)} => {php(x, ind + 1)},\n" for k, x in v.items())
        return 'array(\n' + inner + pad + ')'
    if isinstance(v, list):
        inner = ''.join(f'{pad}\t{php(x, ind + 1)},\n' for x in v)
        return 'array(\n' + inner + pad + ')'
    if isinstance(v, bool):
        return 'true' if v else 'false'
    if v is None:
        return "''"
    if isinstance(v, (int, float)):
        return repr(v)
    return "'" + str(v).replace('\\', '\\\\').replace("'", "\\'") + "'"


data = {
    'categories': {k: {'img': c['img'], 'name': {l: c[l] for l in ('de', 'fr', 'en', 'es')}, 'desc': c['desc']} for k, c in CATEGORIES.items()},
    'products': [{'sku': p['sku'], 'cat': p['cat'], 'img': p['img'], 'price': p['price'], 'sale': p['sale'], 'featured': p['featured'],
                  'name': p['name'], 'short': p['short'], 'desc': {l: description(p, l) for l in ('de', 'fr', 'en', 'es')},
                  'gallery': gallery(p)[1:], 'gtin': GTIN[p['sku']], 'brand': SPECS[p['sku']][0], 'weight': SPECS[p['sku']][1],
                  'dims': list(SPECS[p['sku']][2]),
                  'attrs': [{'name': ATTR[k], 'value': v if isinstance(v, dict) else {l: v for l in ('de', 'fr', 'en', 'es')}} for k, v in SPECS[p['sku']][3]]}
                 for p in PRODUCTS],
}
out = os.path.join(THEME, 'inc', 'gaming', 'catalogue-data.php')
with open(out, 'w', encoding='utf-8') as f:
    f.write("<?php\n/**\n * Gaming sample catalogue (generated by premium-shop-dev/gaming-catalogue/build.py — edit catalogue.py).\n *\n * @package Premium_Shop\n */\n\n")
    f.write("defined( 'ABSPATH' ) || exit;\n\nreturn " + php(data, 0) + ';\n')
print('php', out)
