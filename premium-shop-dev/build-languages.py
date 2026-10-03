#!/usr/bin/env python3
"""Build languages/: premium-shop.pot + {de_DE,fr_FR,es_ES}.po/.mo/.l10n.php."""
import os, struct, importlib.util
HERE = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('mp', os.path.join(HERE, 'make-pot.py'))
mp = importlib.util.module_from_spec(spec); spec.loader.exec_module(mp)
from translations import T, TC

LANGS = {'de_DE': 1, 'fr_FR': 2, 'es_ES': 3}
PLURAL = {'de_DE': 'nplurals=2; plural=(n != 1);', 'fr_FR': 'nplurals=2; plural=(n > 1);', 'es_ES': 'nplurals=2; plural=(n != 1);'}
OUT = os.path.join(mp.THEME, 'languages')

def write_mo(path, messages):
    """messages: dict original(bytes-able str) -> translation str."""
    keys = sorted(messages.keys())
    ids = b''; strs = b''; offsets = []
    for k in keys:
        kb = k.encode('utf-8'); vb = messages[k].encode('utf-8')
        offsets.append((len(ids), len(kb), len(strs), len(vb)))
        ids += kb + b'\0'; strs += vb + b'\0'
    n = len(keys)
    keystart = 7 * 4 + 16 * n
    valuestart = keystart + len(ids)
    koffsets = []; voffsets = []
    for o1, l1, o2, l2 in offsets:
        koffsets += [l1, o1 + keystart]; voffsets += [l2, o2 + valuestart]
    data = struct.pack('<Iiiiiii', 0x950412de, 0, n, 7 * 4, 7 * 4 + n * 8, 0, 7 * 4 + n * 16)
    data += struct.pack('<%di' % len(koffsets), *koffsets) + struct.pack('<%di' % len(voffsets), *voffsets) + ids + strs
    open(path, 'wb').write(data)

def php_str(s):
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"

def main():
    entries = mp.collect()
    mp.write_po(os.path.join(OUT, 'premium-shop.pot'), entries)
    table = {row[0]: row for row in T}
    missing = set()
    for locale, col in LANGS.items():
        tr = {}; messages = {}
        header = f'Project-Id-Version: Premium Shop 1.0.0\nLanguage: {locale}\nMIME-Version: 1.0\nContent-Type: text/plain; charset=UTF-8\nContent-Transfer-Encoding: 8bit\nPlural-Forms: {PLURAL[locale]}\nX-Domain: premium-shop\n'
        messages[''] = header
        l10n = {}
        for (msgid, ctx, plural) in entries:
            if ctx:
                t = TC.get((msgid, ctx))
                if not t: missing.add(msgid + ' [' + ctx + ']'); continue
                messages[ctx + '\x04' + msgid] = t[col - 1]
                l10n[ctx + '\x04' + msgid] = t[col - 1]
                continue
            row = table.get(msgid)
            if not row: missing.add(msgid); continue
            val = row[col]
            tr[msgid] = val
            if plural:
                messages[msgid + '\0' + plural] = '\0'.join(val)
                l10n[msgid] = '\0'.join(val)
            else:
                messages[msgid] = val
                l10n[msgid] = val
        mp.write_po(os.path.join(OUT, locale + '.po'), entries, tr, locale)
        write_mo(os.path.join(OUT, locale + '.mo'), messages)
        php = ["<?php", "return array(", "\t'project-id-version' => 'Premium Shop 1.0.0',", f"\t'language' => '{locale}',", f"\t'plural-forms' => '{PLURAL[locale]}',", "\t'domain' => 'premium-shop',", "\t'messages' => array("]
        for k in sorted(l10n):
            php.append('\t\t' + php_str(k).replace('\x04', "' . \"\\4\" . '").replace('\0', "' . \"\\0\" . '") + ' => ' + php_str(l10n[k]).replace('\0', "' . \"\\0\" . '") + ',')
        php += ['\t),', ');', '']
        open(os.path.join(OUT, locale + '.l10n.php'), 'w', encoding='utf-8').write('\n'.join(php))
    unused = set(table) - {k[0] for k in entries}
    print(len(entries), 'strings;', 'missing:', sorted(missing) or 'none', '; unused:', sorted(unused) or 'none')

main()
