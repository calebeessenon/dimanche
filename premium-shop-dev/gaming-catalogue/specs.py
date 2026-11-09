"""Technical data of the gaming catalogue: brand, shipping weight and
package dimensions, attributes, internal GTIN.

Weights and dimensions are those of the shipped package (approximate,
to be checked against the supplier's data sheet).

GTIN: EAN-13 codes in the GS1 "restricted circulation" range (prefix 20),
valid for the shop's own use (stock, till, labels). Replace them with the
manufacturer's EAN printed on the box before listing on Google Shopping or
marketplaces.
"""

# Attribute labels (shop language German first).
ATTR = {
    'platform': {'de': 'Plattform', 'fr': 'Plateforme', 'en': 'Platform', 'es': 'Plataforma'},
    'colour': {'de': 'Farbe', 'fr': 'Couleur', 'en': 'Colour', 'es': 'Color'},
    'storage': {'de': 'Speicher', 'fr': 'Stockage', 'en': 'Storage', 'es': 'Almacenamiento'},
    'display': {'de': 'Bildschirm', 'fr': 'Écran', 'en': 'Display', 'es': 'Pantalla'},
    'resolution': {'de': 'Auflösung', 'fr': 'Résolution', 'en': 'Resolution', 'es': 'Resolución'},
    'connectivity': {'de': 'Konnektivität', 'fr': 'Connectivité', 'en': 'Connectivity', 'es': 'Conectividad'},
    'battery': {'de': 'Akkulaufzeit', 'fr': 'Autonomie', 'en': 'Battery life', 'es': 'Autonomía'},
    'protection': {'de': 'Schutzklasse', 'fr': 'Indice de protection', 'en': 'Protection rating', 'es': 'Protección'},
    'memory': {'de': 'Grafikspeicher', 'fr': 'Mémoire graphique', 'en': 'Video memory', 'es': 'Memoria gráfica'},
    'interface': {'de': 'Schnittstelle', 'fr': 'Interface', 'en': 'Interface', 'es': 'Interfaz'},
    'outputs': {'de': 'Anschlüsse', 'fr': 'Sorties', 'en': 'Outputs', 'es': 'Salidas'},
    'psu': {'de': 'Empfohlenes Netzteil', 'fr': 'Alimentation recommandée', 'en': 'Recommended PSU', 'es': 'Fuente recomendada'},
    'players': {'de': 'Spieler', 'fr': 'Joueurs', 'en': 'Players', 'es': 'Jugadores'},
    'age': {'de': 'Altersfreigabe', 'fr': 'Âge minimum', 'en': 'Age rating', 'es': 'Edad mínima'},
    'value': {'de': 'Guthaben', 'fr': 'Valeur', 'en': 'Value', 'es': 'Valor'},
    'region': {'de': 'Region', 'fr': 'Région', 'en': 'Region', 'es': 'Región'},
    'in_box': {'de': 'Lieferumfang', 'fr': 'Contenu de la boîte', 'en': 'In the box', 'es': 'Contenido de la caja'},
    'warranty': {'de': 'Garantie', 'fr': 'Garantie', 'en': 'Warranty', 'es': 'Garantía'},
}

# Labels of the technical data table in the description.
SPEC_LABELS = {
    'title': {'de': 'Technische Daten', 'fr': 'Caractéristiques techniques', 'en': 'Technical specifications', 'es': 'Especificaciones técnicas'},
    'brand': {'de': 'Marke', 'fr': 'Marque', 'en': 'Brand', 'es': 'Marca'},
    'weight': {'de': 'Versandgewicht', 'fr': 'Poids (colis)', 'en': 'Shipping weight', 'es': 'Peso (paquete)'},
    'dims': {'de': 'Verpackung (L × B × H)', 'fr': 'Emballage (L × l × H)', 'en': 'Package (L × W × H)', 'es': 'Embalaje (L × An × Al)'},
    'gtin': {'de': 'GTIN / EAN', 'fr': 'GTIN / EAN', 'en': 'GTIN / EAN', 'es': 'GTIN / EAN'},
    'sku': {'de': 'Artikelnummer', 'fr': 'Référence', 'en': 'SKU', 'es': 'Referencia'},
}

W2 = {'de': '2 Jahre', 'fr': '2 ans', 'en': '2 years', 'es': '2 años'}


def L(de, fr, en, es):
    return {'de': de, 'fr': fr, 'en': en, 'es': es}


WHITE = L('Weiss', 'Blanc', 'White', 'Blanco')
BLACK = L('Schwarz', 'Noir', 'Black', 'Negro')
RED = L('Rot', 'Rouge', 'Red', 'Rojo')
BLUE = L('Blau', 'Bleu', 'Blue', 'Azul')

PS5_CONN = 'Wi-Fi 6, Bluetooth 5.1, HDMI 2.1, USB-C, LAN'
CONSOLE_BOX = L('Konsole, DualSense-Controller, HDMI-Kabel, Netzkabel, USB-Kabel',
                'Console, manette DualSense, câble HDMI, câble d’alimentation, câble USB',
                'Console, DualSense controller, HDMI cable, power cable, USB cable',
                'Consola, mando DualSense, cable HDMI, cable de alimentación, cable USB')
PAD_BOX = L('Controller, USB-C-Kabel', 'Manette, câble USB-C', 'Controller, USB-C cable', 'Mando, cable USB-C')
FLIP = [('connectivity', 'Bluetooth 5.1'), ('battery', L('Bis zu 12 Stunden', 'Jusqu’à 12 heures', 'Up to 12 hours', 'Hasta 12 horas')),
        ('protection', 'IP67'), ('in_box', L('Lautsprecher, USB-C-Kabel', 'Enceinte, câble USB-C', 'Speaker, USB-C cable', 'Altavoz, cable USB-C'))]

# sku: brand, weight kg, (length, width, height) cm, attributes [(key, value)].
SPECS = {
    'GM-PS5-SLIM-STD': ('Sony', 4.6, (44, 36, 12), [('platform', 'PlayStation 5'), ('colour', WHITE), ('storage', '1 TB SSD'),
                        ('resolution', '4K, 120 Hz'), ('connectivity', PS5_CONN), ('in_box', CONSOLE_BOX)]),
    'GM-PS5-SLIM-DIG': ('Sony', 4.2, (44, 36, 12), [('platform', 'PlayStation 5'), ('colour', WHITE), ('storage', '1 TB SSD'),
                        ('resolution', '4K, 120 Hz'), ('connectivity', PS5_CONN), ('in_box', CONSOLE_BOX)]),
    'GM-PS5-PRO': ('Sony', 5.0, (46, 38, 13), [('platform', 'PlayStation 5'), ('colour', WHITE), ('storage', '2 TB SSD'),
                   ('resolution', '4K, 120 Hz'), ('connectivity', 'Wi-Fi 7, Bluetooth 5.1, HDMI 2.1, USB-C, LAN'), ('in_box', CONSOLE_BOX)]),
    'GM-PS-PORTAL': ('Sony', 0.9, (33, 18, 9), [('platform', 'PlayStation 5'), ('colour', WHITE), ('display', '8" LCD'),
                     ('resolution', '1080p, 60 FPS'), ('connectivity', 'Wi-Fi 5, USB-C'),
                     ('in_box', L('Remote Player, USB-C-Kabel', 'Remote Player, câble USB-C', 'Remote Player, USB-C cable', 'Remote Player, cable USB-C'))]),
    'GM-DS-WHITE': ('Sony', 0.45, (18, 16, 7), [('platform', 'PlayStation 5, PC'), ('colour', WHITE), ('connectivity', 'Bluetooth, USB-C'),
                    ('battery', L('3–6 Stunden', '3 à 6 heures', '3–6 hours', '3–6 horas')), ('in_box', PAD_BOX)]),
    'GM-DS-RED': ('Sony', 0.45, (18, 16, 7), [('platform', 'PlayStation 5, PC'), ('colour', L('Volcanic Red', 'Rouge volcanique', 'Volcanic Red', 'Rojo volcánico')),
                  ('connectivity', 'Bluetooth, USB-C'), ('battery', L('3–6 Stunden', '3 à 6 heures', '3–6 hours', '3–6 horas')), ('in_box', PAD_BOX)]),
    'GM-DS-BLUE': ('Sony', 0.45, (18, 16, 7), [('platform', 'PlayStation 5, PC'), ('colour', L('Starlight Blue', 'Bleu étoilé', 'Starlight Blue', 'Azul estelar')),
                   ('connectivity', 'Bluetooth, USB-C'), ('battery', L('3–6 Stunden', '3 à 6 heures', '3–6 hours', '3–6 horas')), ('in_box', PAD_BOX)]),
    'GM-DS-EDGE': ('Sony', 1.2, (30, 22, 9), [('platform', 'PlayStation 5, PC'), ('colour', WHITE), ('connectivity', 'Bluetooth, USB-C'),
                   ('in_box', L('Controller, Etui, geflochtenes USB-C-Kabel, Stick-Kappen, Rücktasten', 'Manette, étui, câble USB-C tressé, capuchons de sticks, boutons arrière',
                                'Controller, carrying case, braided USB-C cable, stick caps, back buttons', 'Mando, estuche, cable USB-C trenzado, capuchones, botones traseros'))]),
    'GM-NSW2': ('Nintendo', 2.2, (34, 20, 11), [('platform', 'Nintendo Switch 2'), ('colour', BLACK), ('display', '7.9" LCD, 120 Hz'),
                ('storage', '256 GB'), ('resolution', L('1080p (Handheld), bis 4K (TV)', '1080p (portable), jusqu’à 4K (TV)', '1080p (handheld), up to 4K (TV)', '1080p (portátil), hasta 4K (TV)')),
                ('connectivity', 'Wi-Fi 6, Bluetooth, USB-C, HDMI'),
                ('in_box', L('Konsole, Joy-Con 2 (L/R), Dock, Joy-Con-2-Halterung, HDMI-Kabel, Netzteil', 'Console, Joy-Con 2 (G/D), station d’accueil, support Joy-Con 2, câble HDMI, adaptateur secteur',
                             'Console, Joy-Con 2 (L/R), dock, Joy-Con 2 grip, HDMI cable, AC adapter', 'Consola, Joy-Con 2 (I/D), base, soporte Joy-Con 2, cable HDMI, adaptador de corriente'))]),
    'GM-NSW2-MKW': ('Nintendo', 2.4, (34, 20, 12), [('platform', 'Nintendo Switch 2'), ('colour', BLACK), ('display', '7.9" LCD, 120 Hz'),
                    ('storage', '256 GB'), ('connectivity', 'Wi-Fi 6, Bluetooth, USB-C, HDMI'),
                    ('in_box', L('Konsole, Joy-Con 2, Dock, HDMI-Kabel, Netzteil, Download-Code Mario Kart World', 'Console, Joy-Con 2, station d’accueil, câble HDMI, adaptateur, code Mario Kart World',
                                 'Console, Joy-Con 2, dock, HDMI cable, AC adapter, Mario Kart World download code', 'Consola, Joy-Con 2, base, cable HDMI, adaptador, código de Mario Kart World'))]),
    'GM-NSW-OLED': ('Nintendo', 1.9, (33, 18, 11), [('platform', 'Nintendo Switch'), ('colour', WHITE), ('display', '7" OLED'),
                    ('storage', '64 GB'), ('connectivity', 'Wi-Fi, Bluetooth, USB-C, HDMI, LAN'),
                    ('in_box', L('Konsole, Joy-Con (L/R), Dock mit LAN-Anschluss, Joy-Con-Halterung, HDMI-Kabel, Netzteil', 'Console, Joy-Con (G/D), station avec port LAN, support Joy-Con, câble HDMI, adaptateur',
                                 'Console, Joy-Con (L/R), dock with LAN port, Joy-Con grip, HDMI cable, AC adapter', 'Consola, Joy-Con (I/D), base con LAN, soporte Joy-Con, cable HDMI, adaptador'))]),
    'GM-NSW-LITE': ('Nintendo', 0.6, (21, 13, 7), [('platform', 'Nintendo Switch'), ('colour', L('Gelb', 'Jaune', 'Yellow', 'Amarillo')), ('display', '5.5" LCD'),
                    ('storage', '32 GB'), ('battery', L('3–7 Stunden', '3 à 7 heures', '3–7 hours', '3–7 horas')),
                    ('in_box', L('Konsole, Netzteil', 'Console, adaptateur secteur', 'Console, AC adapter', 'Consola, adaptador de corriente'))]),
    'GM-NSW2-PRO': ('Nintendo', 0.5, (17, 15, 7), [('platform', 'Nintendo Switch 2, Nintendo Switch'), ('colour', BLACK), ('connectivity', 'Bluetooth, USB-C'),
                    ('battery', L('Bis zu 40 Stunden', 'Jusqu’à 40 heures', 'Up to 40 hours', 'Hasta 40 horas')), ('in_box', PAD_BOX)]),
    'GM-JOYCON2': ('Nintendo', 0.25, (15, 13, 5), [('platform', 'Nintendo Switch 2'), ('colour', L('Blau/Rot', 'Bleu/Rouge', 'Blue/Red', 'Azul/Rojo')),
                   ('connectivity', 'Bluetooth'), ('battery', L('Bis zu 20 Stunden', 'Jusqu’à 20 heures', 'Up to 20 hours', 'Hasta 20 horas')),
                   ('in_box', L('Joy-Con 2 (L) und (R), 2 Handgelenkschlaufen', 'Joy-Con 2 (G) et (D), 2 dragonnes', 'Joy-Con 2 (L) and (R), 2 wrist straps', 'Joy-Con 2 (I) y (D), 2 correas'))]),
    'GM-JBL-FLIP-BK': ('JBL', 0.8, (22, 11, 10), [('colour', BLACK)] + FLIP),
    'GM-JBL-FLIP-BL': ('JBL', 0.8, (22, 11, 10), [('colour', BLUE)] + FLIP),
    'GM-JBL-FLIP-RD': ('JBL', 0.8, (22, 11, 10), [('colour', RED)] + FLIP),
    'GM-JBL-GO': ('JBL', 0.3, (13, 11, 6), [('colour', RED), ('connectivity', 'Bluetooth 5.3'), ('battery', L('Bis zu 7 Stunden', 'Jusqu’à 7 heures', 'Up to 7 hours', 'Hasta 7 horas')),
                  ('protection', 'IP67'), ('in_box', L('Lautsprecher, USB-C-Kabel', 'Enceinte, câble USB-C', 'Speaker, USB-C cable', 'Altavoz, cable USB-C'))]),
    'GM-HEADSET': ('NeoPlay Select', 0.6, (23, 20, 10), [('platform', 'PlayStation 5, Nintendo Switch, PC'), ('colour', BLACK),
                   ('connectivity', L('2,4 GHz kabellos, Bluetooth, 3,5-mm-Klinke', 'Sans fil 2,4 GHz, Bluetooth, jack 3,5 mm', '2.4 GHz wireless, Bluetooth, 3.5 mm jack', 'Inalámbrico 2,4 GHz, Bluetooth, jack 3,5 mm')),
                   ('battery', L('Bis zu 30 Stunden', 'Jusqu’à 30 heures', 'Up to 30 hours', 'Hasta 30 horas')),
                   ('in_box', L('Headset, abnehmbares Mikrofon, USB-Dongle, USB-C-Kabel', 'Casque, micro amovible, dongle USB, câble USB-C', 'Headset, detachable microphone, USB dongle, USB-C cable', 'Auriculares, micrófono extraíble, dongle USB, cable USB-C'))]),
    'GM-GC-PSN50': ('Sony', 0.02, (15, 10, 1), [('platform', 'PlayStation Store'), ('value', 'CHF 50'), ('region', L('Schweiz', 'Suisse', 'Switzerland', 'Suiza'))]),
    'GM-GC-ESHOP50': ('Nintendo', 0.02, (15, 10, 1), [('platform', 'Nintendo eShop'), ('value', 'CHF 50'), ('region', L('Schweiz', 'Suisse', 'Switzerland', 'Suiza'))]),
    'GM-GAME-MKW': ('Nintendo', 0.1, (19, 12, 2), [('platform', 'Nintendo Switch 2'), ('players', L('1–4 lokal, bis 24 online', '1 à 4 en local, jusqu’à 24 en ligne', '1–4 local, up to 24 online', '1–4 local, hasta 24 en línea')), ('age', 'PEGI 3')]),
    'GM-GAME-FC': ('EA Sports', 0.1, (17, 14, 2), [('platform', 'PlayStation 5'), ('players', L('1–4 lokal, online', '1 à 4 en local, en ligne', '1–4 local, online', '1–4 local, en línea')), ('age', 'PEGI 3')]),
    'GM-RTX5070': ('NVIDIA GeForce', 1.6, (38, 24, 10), [('memory', '12 GB GDDR7'), ('interface', 'PCIe 5.0 x16'), ('outputs', '3 × DisplayPort 2.1, 1 × HDMI 2.1'), ('psu', '650 W')]),
    'GM-RTX5080': ('NVIDIA GeForce', 2.4, (42, 26, 11), [('memory', '16 GB GDDR7'), ('interface', 'PCIe 5.0 x16'), ('outputs', '3 × DisplayPort 2.1, 1 × HDMI 2.1'), ('psu', '850 W')]),
    'GM-RX9070XT': ('AMD Radeon', 2.0, (40, 25, 10), [('memory', '16 GB GDDR6'), ('interface', 'PCIe 5.0 x16'), ('outputs', '2 × DisplayPort 2.1, 1 × HDMI 2.1'), ('psu', '750 W')]),
}


def ean13(body12):
    """Add the EAN-13 check digit."""
    s = sum(int(d) * (3 if i % 2 else 1) for i, d in enumerate(body12))
    return body12 + str((10 - s % 10) % 10)


def gtin(index):
    """Internal GTIN (restricted circulation prefix 20)."""
    return ean13(f'20{760000 + index:06d}0000'[:12])


def value(v, lang):
    return v[lang] if isinstance(v, dict) else v
