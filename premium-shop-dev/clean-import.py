#!/usr/bin/env python3
"""Convert a WooCommerce product export (any admin language) into a clean
import file for Premium Shop: English column names, no old IDs, generated
SKUs, firewood data (species, length, unit, moisture, drying) read from the
product names and descriptions.

Usage: clean-import.py export.csv out.csv [--no-images] [--sku-prefix WH]
"""
import csv, html, re, sys

HEADERS = {  # French export header -> English importer header
    'Type': 'Type', 'UGS': 'SKU', 'Nom': 'Name', 'Publié': 'Published',
    'Mis en avant ?': 'Is featured?', 'Visibilité dans le catalogue': 'Visibility in catalog',
    'Description courte': 'Short description', 'Description': 'Description',
    'Date de début de promo': 'Date sale price starts', 'Date de fin de promo': 'Date sale price ends',
    'État de la TVA': 'Tax status', 'Classe de TVA': 'Tax class', 'En stock ?': 'In stock?',
    'Stock': 'Stock', 'Montant de stock faible': 'Low stock amount',
    'Autoriser les commandes de produits en rupture ?': 'Backorders allowed?',
    'Vendre individuellement ?': 'Sold individually?', 'Poids (kg)': 'Weight (kg)',
    'Longueur (cm)': 'Length (cm)', 'Largeur (cm)': 'Width (cm)', 'Hauteur (cm)': 'Height (cm)',
    'Autoriser les avis clients ?': 'Allow customer reviews?', 'Note de commande': 'Purchase note',
    'Tarif promo': 'Sale price', 'Tarif régulier': 'Regular price', 'Catégories': 'Categories',
    'Étiquettes': 'Tags', 'Classe d’expédition': 'Shipping class', 'Images': 'Images',
    'Position': 'Position',
}
DROP = {'ID', 'Parent', 'Groupes de produits', 'Produits suggérés', 'Ventes croisées',
        'URL externe', 'Libellé du bouton', 'Limite de téléchargement',
        'Jours d’expiration du téléchargement'}

SPECIES = [  # first match wins; several species -> mixed
    ('oak', r'ch[êe]ne'), ('beech', r'h[êe]tre'), ('birch', r'bouleau'),
    ('ash', r'fr[êe]ne'), ('hornbeam', r'charme'),
]
NOT_LOGS = r'granul|pellet|briquet|densifi|bûches? de nuit|hotrods|ecofire|compress'


def num(s):
    return float(s.replace(',', '.'))


def fmt(x):
    return ('%g' % x)


def plain(s):
    s = s.replace('\\n', ' ')
    s = re.sub(r'<[^>]+>', ' ', s)
    return re.sub(r'\s+', ' ', html.unescape(s)).strip()


def firewood(name, cat, desc):
    """Return firewood meta for a product."""
    n = name.lower()
    text = plain(desc).lower()
    m = {}
    is_logs = not re.search(NOT_LOGS, n + ' ' + cat.lower())
    if is_logs:
        found = [k for k, rx in SPECIES if re.search(rx, n)]
        if len(found) == 1 and not re.search(r'm[ée]lang', n):
            m['_ps_species'] = found[0]
        elif found or re.search(r'm[ée]lang|bois durs|feuillus', n):
            m['_ps_species'] = 'mixed'
        L = re.search(r'(\d{2,3})\s*-?\s*cm', n)
        if L:
            m['_ps_log_length'] = L.group(1)
        st = re.search(r'(\d+(?:[.,]\d+)?)\s*(?:st(?:è|e)res?|st\b)', n)
        if st and not re.search(r'&lt;|<', n.split(st.group(0))[0][-3:]):
            m['_ps_unit'] = 'rm'
            m['_ps_unit_qty'] = fmt(num(st.group(1)))
        if re.search(r's[ée]ch[ée]s? au four|s[ée]choir|kiln', n + ' ' + text):
            m['_ps_drying'] = 'kiln'
        elif re.search(r's[ée]chage naturel|s[ée]ch[ée]s? (?:à|a) l.air', text):
            m['_ps_drying'] = 'air'
        h = re.search(r'(?:moins de|inf[ée]rieur (?:à|a)|<)\s*(\d{1,2})\s*%', text)
        if h:
            m['_ps_moisture'] = h.group(1)
    if '_ps_unit' not in m:
        kg = None
        bags = re.search(r'(\d+)\s*sacs?\s*(?:de|x)\s*(\d+)\s*kg', n)
        if bags:
            kg = int(bags.group(1)) * int(bags.group(2))
        else:
            k = re.search(r'(\d+(?:[.,]\d+)?)\s*kg', n)
            t = re.search(r'(\d+)\s*tonnes?', n)
            if k:
                kg = num(k.group(1))
            elif t:
                kg = int(t.group(1)) * 1000
        if kg:
            m['_ps_unit'] = 'kg'
            m['_ps_unit_qty'] = fmt(kg)
    return m


def short_from(desc):
    """First two sentences of the description, as the short description."""
    t = plain(desc)
    t = re.sub(r'^(Présentation|Description)\s*', '', t)
    parts = re.split(r'(?<=[.!?])\s+', t)
    out = ''
    for p in parts:
        if len(out) + len(p) > 260 and out:
            break
        out = (out + ' ' + p).strip()
    return out


def main():
    src, dst = sys.argv[1], sys.argv[2]
    no_images = '--no-images' in sys.argv
    prefix = sys.argv[sys.argv.index('--sku-prefix') + 1] if '--sku-prefix' in sys.argv else 'WH'
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig', newline='')))
    meta_keys = ['_ps_species', '_ps_log_length', '_ps_moisture', '_ps_drying', '_ps_unit', '_ps_unit_qty']
    out_rows = []
    for i, r in enumerate(rows, 1):
        r = {k.replace('\xa0', ' '): v for k, v in r.items()}
        o = {}
        for fr, en in HEADERS.items():
            if fr in r:
                o[en] = r[fr]
        o['Name'] = html.unescape(o['Name']).strip()
        if not o.get('SKU'):
            o['SKU'] = '%s-%04d' % (prefix, i)
        if not o.get('Short description', '').strip():
            o['Short description'] = short_from(r.get('Description', ''))
        if no_images:
            o['Images'] = ''
        meta = firewood(o['Name'], o.get('Categories', ''), r.get('Description', ''))
        for k in meta_keys:
            o['Meta: ' + k] = meta.get(k, '')
        out_rows.append(o)
    cols = [en for fr, en in HEADERS.items() if any(en in o for o in out_rows)]
    cols = cols[:3] + [c for c in cols[3:]] + ['Meta: ' + k for k in meta_keys]
    with open(dst, 'w', encoding='utf-8-sig', newline='') as f:
        w = csv.DictWriter(f, fieldnames=cols, extrasaction='ignore')
        w.writeheader()
        w.writerows(out_rows)
    print('%d products -> %s' % (len(out_rows), dst))


if __name__ == '__main__':
    main()
