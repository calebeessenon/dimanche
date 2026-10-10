#!/usr/bin/env python3
"""Extract the unique French text segments of a WooCommerce export
(names, short descriptions, text nodes of the descriptions) to translate."""
import csv, html, json, re, sys

NUMERIC = re.compile(r'^[\d\s.,/x×+\-–%<>≈~()]*(?:cm|mm|m3|m³|m\b|kg|g|t|%|l)?[\s.,]*$', re.I)


def segments_of(description):
    d = description.replace('\\n', '\n')
    for t in re.split(r'<[^>]+>', d):
        t = html.unescape(t).strip()
        if t:
            yield t


def main():
    src, dst = sys.argv[1], sys.argv[2]
    rows = list(csv.DictReader(open(src, encoding='utf-8-sig', newline='')))
    seen, out = set(), []
    def add(t):
        t = t.strip()
        if t and t not in seen and re.search(r'[A-Za-zÀ-ÿ]{2}', t) and not NUMERIC.match(t):
            seen.add(t)
            out.append(t)
    for r in rows:
        add(html.unescape(r['Nom']))
        add(html.unescape(r['Description courte']))
        for t in segments_of(r['Description']):
            add(t)
    json.dump(out, open(dst, 'w', encoding='utf-8'), ensure_ascii=False, indent=0)
    print(len(out), 'segments', sum(map(len, out)), 'chars')


if __name__ == '__main__':
    main()
