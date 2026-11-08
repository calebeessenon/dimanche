# Audit des langues — Premium Shop Gaming 2.3.12 (corrigé en 2.3.13)

Date : 8 novembre 2026 · Branche : `ccr-761b2ee0-8az5vc`

**Problème signalé** : sur la boutique allemande, le titre du checkout était « Validation de la commande ». WordPress avait été **installé en français**. WooCommerce a donc créé ses pages, ses réglages et ses catégories en français, puis la langue du site est passée à l'allemand. La version 2.3.12 traduisait déjà les titres des pages système. Cet audit vérifie **tous les autres textes**.

## 1. Environnement reproduit

- **Pile** : MariaDB 10.11, PHP 8.3 (`php -S`, 6 workers), **WordPress 7.1.4**, **WooCommerce 11.2.2**.
- **Débogage** : `WP_DEBUG`, `WP_DEBUG_LOG` et `WP_DEBUG_DISPLAY` activés.
- **E-mails** : capturés par un mu-plugin `pre_wp_mail`.
- **Pilotage** : scripts PHP qui chargent `wp-load.php`. WP-CLI n'a pas été utilisé.
- **Installation en `fr_FR` dès le départ** (`wp_install(..., 'fr_FR')`).
  - Packs de langue fr_FR, de_DE et es_ES pour le cœur et WooCommerce, téléchargés depuis downloads.wordpress.org.
  - WordPress a créé « Bonjour tout le monde ! », « Page d’exemple », la catégorie « Non classé » et le brouillon « Politique de confidentialité ».
- **WooCommerce activé pendant que le site est en français** :
  - Pages créées :

    | Page | Adresse |
    |---|---|
    | Boutique | `/boutique/` |
    | Panier | `/panier/` |
    | **Validation de la commande** | `/commander/` |
    | Mon compte | `/mon-compte/` |
    | **Politique en matière de remboursements et de retours** | `/remboursements_retours/` |

  - Catégorie produits : « Non classé ».
  - Bases d'URL : `produit`, `categorie-produit`.
- **Boutique** : CHF, pays CH (Zurich).
  - Zone CH : Forfait « PostPac Priority » CHF 9, Forfait « Express » CHF 19, Livraison gratuite dès CHF 150.
  - Paiements : BACS et COD.
  - Ces réglages ont été enregistrés en français : « Virement bancaire », « Paiement à la livraison », « Livraison gratuite » et les textes de confidentialité.
- **Scénario du site réel** :
  1. Installation du thème gaming **2.3.11** (`dist/premium-shop-gaming-2.3.11.zip`).
  2. Assistant : `premium_shop_setup_languages(true)` (site passé en `de_DE`), puis pages, menus et images.
  3. Import de `dist/gaming-produits-import.csv` avec `WC_Product_CSV_Importer` et le mapping automatique de l'écran d'import : 26 produits.
  4. Publication des brouillons juridiques (Impressum, AGB, Widerrufsbelehrung).
  5. Pour les tests : une page de panier classique (`[woocommerce_cart]`), une page de checkout classique (`[woocommerce_checkout]`), l'inscription activée dans « Mon compte » et un client test.
  6. Sauvegarde de cette base « 2.3.11 ».
- **À chaque campagne** :
  1. Restauration de la base 2.3.11.
  2. Installation du zip construit (`build.sh`), avec `premium_shop_version` = 2.3.11.
  3. Chargement d'une page : les routines de mise à jour s'exécutent.
  4. Contrôles automatiques.

### Méthode de contrôle

- **Playwright (Chromium)**. La langue est choisie par les cookies `ps_lang` et `ps_lc`. **42 pages × 4 langues (DE, FR, EN, ES)** :
  - catalogue : accueil, boutique, catégorie, 3 fiches produit (PS5, JBL, RTX) ;
  - informations et pages légales : contact, livraison, retours, Impressum, AGB, rétractation, confidentialité, conditions d'utilisation, à propos, liste d'envies, suivi de commande, 404 ;
  - recherche : produits et recherche générale ;
  - contenu d'exemple WordPress (page, article) ;
  - compte : « Mon compte » invité (connexion et inscription), mot de passe perdu ;
  - panier : vide, en blocs, classique (plein et > CHF 150), mini-panier ouvert ;
  - checkout : en blocs (invité, invité > CHF 150, client) et classique ;
  - commande passée par le **checkout en blocs** (virement en DE/FR, paiement à la livraison en EN/ES), puis page « merci » ;
  - « Mon compte » connecté : tableau de bord, commandes, détail de commande, adresses, édition facturation et livraison, détails du compte.
- **Éléments relevés par page** :
  - `innerText` visible, `document.title`, meta description et `og:title` ;
  - attributs `aria-label`, `placeholder`, `title` ;
  - valeurs des champs, textes des boutons et des options ;
  - notices PHP dans le HTML, erreurs JS (`pageerror` et console).
  - Le sélecteur de langue est exclu.
- **Analyse** :
  - Listes de mots étrangers FR, DE, EN et ES demandées, complétées par quelques termes (« Bonjour », « Virement », « Allgemein »…).
  - Comparaison ligne à ligne de chaque page FR/EN/ES avec sa version allemande, puis de chaque page DE/EN/ES avec sa version française. Une ligne identique signale un texte non traduit.
  - Relecture manuelle de chaque résultat. Les noms de produits, marques, adresses et cantons ont été écartés.
- **E-mails** : client et administrateur pour chaque commande (DE, FR, EN, ES), plus l'e-mail de création de compte.
- **Admin (de_DE)** :
  - liste des Pages ;
  - WooCommerce › Réglages › Avancé et Comptes ;
  - menus ;
  - catégories de produits et d'articles ;
  - articles.

## 2. Textes dans la mauvaise langue trouvés en 2.3.12, et corrections

Point de départ : 2.3.12, mise à jour depuis 2.3.11 sur la base installée en français.

| # | Où | Langue(s) | Texte trouvé | Correction (2.3.13) |
|---|---|---|---|---|
| 1 | Admin › Pages | DE (admin) | Brouillon **« Politique en matière de remboursements et de retours »**. Le titre réel de WooCommerce n'était pas reconnu par 2.3.12 (ni « Richtlinie für Erstattungen und Rückgaben », ni « Política de devoluciones y reembolsos »). | Les titres par défaut sont désormais lus dans les **packs de langue installés** (WooCommerce et WordPress, toutes langues du registre), avec mise en cache. Le brouillon orphelin est retrouvé par son titre et renommé « Rückerstattung und Rückgaben », avec ses traductions FR/EN/ES. L'adresse ne change pas. |
| 2 | Checkout classique | DE, EN, ES | « Vos données personnelles seront utilisées pour le traitement de votre commande… » | L'option `woocommerce_checkout_privacy_policy_text` est **réécrite dans la langue de la boutique** lors de la mise à jour, et **traduite pour le visiteur** à l'affichage. Le marqueur `[privacy_policy]` correspond au `%s` des catalogues WooCommerce. |
| 3 | « Mon compte » (formulaire d'inscription) | DE, EN, ES | « Vos données personnelles seront utilisées pour vous accompagner… » | Même traitement pour `woocommerce_registration_privacy_policy_text`. |
| 4 | Admin › WooCommerce › Réglages › Comptes | DE (admin) | Les deux textes ci-dessus en français | Réécrits en allemand (« Wir verwenden deine personenbezogenen Daten… »). |
| 5 | Réglages des paiements (base, admin, e-mails envoyés depuis l'admin) | DE | « Virement bancaire », « Effectuez le paiement directement… », « Paiement à la livraison », « Payer en argent comptant… », description PayPal | Titre, description et instructions de tous les réglages `woocommerce_*_settings` réécrits dans la langue de la boutique quand ce sont des textes connus de WooCommerce ou du thème : « Banküberweisung », « Nachnahme », etc. Les textes saisis à la main sont conservés. Le front était déjà traduit à l'affichage. |
| 6 | Livraison gratuite (base, admin) | DE | « Livraison gratuite » | Réécrit en « Kostenloser Versand ». Vérifié au checkout > CHF 150 : « Kostenloser Versand », « Livraison gratuite », « Free shipping », « Envío gratuito ». |
| 7 | Admin › Produits › Catégories | DE (admin) | « Non classé » | Renommée « Unkategorisiert » (adresse `non-classe` conservée). Côté visiteur, la catégorie reste traduite dans sa langue. |
| 8 | Article « Bonjour tout le monde ! » (fil d'Ariane, méta, catégorie) | toutes | Catégorie d'articles « Non classé » / « NON CLASSÉ » | Renommée « Allgemein » (adresse conservée). Le filtre visiteur couvre maintenant aussi la catégorie d'articles par défaut (`default_category`). |
| 9 | `/bonjour-tout-le-monde/` (publié, présent dans la recherche et les flux) | toutes | « Bonjour tout le monde ! », « Bienvenue sur WordPress… », commentaire « Bonjour, ceci est un commentaire. » ; `title` du flux « Kommentar-Feed zu Bonjour tout le monde ! » | Article d'exemple WordPress **jamais modifié** : mis hors ligne (brouillon) avec un titre allemand (« Hallo Welt! »). Une version modifiée ou republiée plus tard n'est pas touchée. |
| 10 | `/page-d-exemple/` (publiée) | toutes | « Page d’exemple », « Bonjour ! Je suis un mécanicien qui aspire à devenir acteur… », « En tant que nouvel utilisateur… » | Même traitement : brouillon, titre « Beispielseite ». |
| 11 | Panier : meta description | toutes | « Vous serez peut-être intéressé par… » | Ce titre est enregistré par WooCommerce dans le bloc panier. Il est maintenant traduit aussi dans la langue de la boutique, plus seulement dans les autres langues. Pas de meta description sur panier, checkout et compte : ces pages ne contiennent que des blocs. |
| 12 | Meta description et `og:description` : à propos, contact, suivi de commande, confidentialité, Impressum, fiches produit (PS5, RTX…) | FR, EN, ES | Texte allemand (« Sorgfältig ausgewählte Produkte… », « Wir freuen uns auf Ihre Nachricht… », « Der Weihnachtsklassiker… ») | La description utilise maintenant les traductions de la page ou du produit (`_ps_short_*`, `_ps_desc_*`). |
| 13 | Fil d'Ariane de « Mot de passe perdu » (et de toutes les sous-pages du compte) | FR, EN, ES | « Accueil / **Mein Konto** / Mot de passe perdu ». WooCommerce affiche le titre enregistré de la page compte sur ses sous-pages ; c'était « Mon compte » en DE avant 2.3.12. | Filtre `woocommerce_get_breadcrumb` : un élément qui reprend le titre enregistré d'une page reçoit le titre traduit (« Mon compte », « My account », « Mi cuenta »). |
| 14 | Galerie des fiches produit (flèches) | DE, FR, ES | « Previous » / « Next » (texte de Flexslider) | `prevText` / `nextText` traduits : « Vorheriges Bild » / « Nächstes Bild », « Image précédente », « Imagen anterior »… Nouvelles chaînes dans `translations_firewood.py`. |
| 15 | Formulaire de contact (champ piège anti-spam, masqué) | DE, FR, ES | « Website » | Libellé traduisible (« Site web », « Sitio web »). |

### Déjà correct en 2.3.12 (vérifié)

- **Titres des pages système** : « Kasse », « Commande », « Checkout » et « Finalizar compra » à la place de « Validation de la commande ». Même chose pour le panier, la boutique, le compte et la confidentialité, dans les menus et dans WooCommerce › Réglages › Avancé (« Warenkorb (ID: 6) », « Kasse (ID: 7) », « Mein Konto (ID: 8) »). Les adresses `/commander/`, `/panier/`, `/boutique/` et `/mon-compte/` sont conservées.
- **Checkout en blocs** dans les 4 langues : « Kontaktinformationen », « Rechnungsadresse », « Bestellübersicht » / « Coordonnées », « Résumé de la commande »…
- **Moyens de paiement et instructions** (paiement à la livraison) au checkout et dans les e-mails.
- **Pages « merci »** et **8 e-mails** : 4 e-mails client (création de compte en DE ; commandes en FR, EN et ES), 4 e-mails admin en DE. **0 texte étranger**.

### Corrections d'outillage

- Les caches de traduction `ps_wct_*` de la version précédente sont vidés à la mise à jour. Sinon, un texte non traduit pouvait rester mis en cache une semaine.
- Les réglages sont lus et écrits **bruts** en base. La mise à jour peut s'exécuter pendant la visite d'un client francophone, et `update_option()` aurait pu comparer avec la valeur déjà traduite.

### Remarques (pas des erreurs, rien de modifié)

- **Adresse de la boutique dans le pied de page** : « 9 route du Beauregard, 1180 Rolle, Suisse ». Ce sont les coordonnées saisies pour la boutique (Personnaliser › Contact). Pour un pied de page allemand, écrivez « Schweiz » vous-même si vous le souhaitez.
- **Noms des cantons** (« Graubünden », « Zürich » en FR) : ils viennent du pack WooCommerce lui-même.
- **Tutoiement et vouvoiement en allemand** : le pack WooCommerce `de_DE` tutoie (« Deine Bestellung… »), le thème vouvoie (« Ihre Bestellung »). C'est bien de l'allemand. Pour tout vouvoyer, choisissez « Deutsch (Sie) » (`de_DE_formal`) dans Réglages › Général.
- **Brouillon « Rückerstattung und Rückgaben »** : seuls son titre et ses traductions ont été corrigés. Il n'est pas publié, la page « Rückgabe & Erstattung » du thème le remplace, et son texte reste celui écrit par WooCommerce en français. Vous pouvez le supprimer.
- **Erreurs console « 404 »** : seulement sur la page 404 de test et sur les anciennes adresses de la page et de l'article d'exemple, passés en brouillon. C'est le comportement attendu.

## 3. Fichiers modifiés

| Fichier | Changement |
|---|---|
| `inc/i18n-content.php` | Titres et textes par défaut lus dans les packs installés (`premium_shop_installed_default_texts()`, mise en cache).<br>Brouillon de remboursement orphelin.<br>Réécriture des réglages WooCommerce (`premium_shop_fix_wc_settings_language`).<br>Catégories par défaut et contenu d'exemple (`premium_shop_fix_install_language_content`).<br>Fil d'Ariane.<br>Catégorie d'articles par défaut côté visiteur. |
| `inc/woocommerce/order-language.php` | Textes de confidentialité, conditions et notice traduits à l'affichage.<br>Marqueur `[privacy_policy]`.<br>Blocs panier traduits aussi dans la langue de la boutique. |
| `inc/seo.php` | Meta description traduite. Aucune sur panier, checkout et compte. |
| `inc/woocommerce/single-product.php` | Flèches de la galerie traduites. |
| `inc/contact.php` | Libellé « Website » traduisible. |
| `inc/upgrade.php` | Étape unique `premium_shop_upgrade_2_3_13` (aussi exécutée à la première activation). |
| `premium-shop-dev/translations_firewood.py` | 3 chaînes : « Previous image », « Next image », « Website ». |
| `functions.php`, `style.css`, `readme.txt` | Version 2.3.13 et journal des modifications. |

`wc_fallback.py` n'a pas été modifié. Les packs WooCommerce de_DE, fr_FR et es_ES étaient installés, et aucune chaîne WooCommerce n'est apparue en anglais.

## 4. Résultat final

Mise à jour fraîche 2.3.11 → **2.3.13** sur la base installée en français.

| Contrôle | DE | FR | EN | ES |
|---|---|---|---|---|
| Pages vérifiées (texte visible, title, meta, aria, placeholders, boutons) | 42 | 42 | 42 | 42 |
| Textes dans une autre langue (après relecture) | **0** | **0** | **0** | **0** |
| Checkout en blocs + commande + page « merci » | ✅ | ✅ | ✅ | ✅ |
| E-mail client de la commande | ✅ (création de compte DE) | ✅ | ✅ | ✅ |
| E-mail admin (DE) de la commande | ✅ | ✅ | ✅ | ✅ |
| Erreurs JS (hors 404 attendues) | 0 | 0 | 0 | 0 |
| Notices PHP (HTML + `debug.log`) | 0 | 0 | 0 | 0 |

| Admin (de_DE) | Résultat |
|---|---|
| Pages | AGB, Beispielseite (brouillon), Datenschutzerklärung, Impressum, Kasse, Kontakt, Mein Konto, Nutzungsbedingungen, Rückerstattung und Rückgaben (brouillon), Rückgabe & Erstattung, Sendungsverfolgung, Shop, Über uns, Versand & Zahlung, Warenkorb, Widerrufsbelehrung, Wunschliste ✅ |
| WooCommerce › Réglages › Avancé | Warenkorb / Kasse / Mein Konto ✅ |
| WooCommerce › Réglages › Comptes | textes de confidentialité en allemand ✅ |
| Paiements / livraison (base) | Banküberweisung, Nachnahme, Kostenloser Versand ✅ |
| Catégories | Unkategorisiert (produits), Allgemein (articles) ✅ |
| Articles | Hallo Welt! (brouillon) ✅ |

Ce qui reste dans l'analyse automatique n'est que des faux positifs :
- 3 mentions de l'adresse `https://automattic.com/privacy/` dans la politique de confidentialité ;
- les erreurs console « 404 » de la page 404 de test et des deux anciennes adresses du contenu d'exemple.

## 5. Livrables

| Élément | Fichier |
|---|---|
| Thème gaming | `dist/premium-shop-gaming.zip` (= `dist/premium-shop-gaming-2.3.13.zip`) |
| Thème bois | `dist/premium-shop.zip` (= `dist/premium-shop-2.3.13.zip`) |
| Captures | `dist/audit-langues/` (1280 px, JPEG qualité 60) : checkout en blocs `checkout-de/fr/en/es-1280.jpg`, panier `panier-de-1280.jpg` |

**Mise en production** :
1. Apparence → Thèmes → Ajouter → Téléverser `premium-shop-gaming-2.3.13.zip` → « Remplacer l'actuel ».
2. La première page chargée exécute la mise à jour : titres, réglages WooCommerce et catégories passent en allemand, et le contenu d'exemple de WordPress est mis hors ligne s'il n'a jamais été modifié.
3. Les adresses (`/commander/`, `/panier/`…) ne changent pas.
