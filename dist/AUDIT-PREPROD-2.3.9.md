# Audit de préproduction — Premium Shop Gaming 2.3.9 (corrigé en 2.3.10)

Date : 8 novembre 2026 · Branche : `ccr-761b2ee0-8az5vc`

## 1. Environnement de test

- **Pile** : MariaDB 10.11, serveur PHP intégré 8.3 (`php -S`, 6 workers), **WordPress 7.1.4**, **WooCommerce 11.2.2**.
- **Débogage** : `WP_DEBUG`, `WP_DEBUG_LOG` et `WP_DEBUG_DISPLAY` activés, `error_reporting(E_ALL)` (wp-config + mu-plugin).
- **Capture des e-mails** : un mu-plugin capture tous les `wp_mail` via `pre_wp_mail`.
- **Packs de langue** WordPress + WooCommerce de_DE, fr_FR et es_ES, téléchargés depuis wordpress.org. Langue du site : de_DE.
- **WP-CLI** : GitHub n'était pas joignable (504). WordPress a donc été piloté par des scripts PHP qui chargent `wp-load.php`.
- **Boutique** : CHF, kg, cm, pays CH (Zurich).
  - Zone « Schweiz » (CH) : Forfait « PostPac Priority » CHF 9, Forfait « Express » CHF 19, Livraison gratuite dès CHF 150.
  - Paiements : virement bancaire (BACS) et paiement à la livraison (COD).
- **Thème** : installé depuis `dist/premium-shop-gaming.zip` (reconstruit avec `build.sh`), puis assistant : pages, menus, images. Les brouillons juridiques ont été publiés pour l'audit.
- **Base vierge séparée** (`wp_clean`, port 8081) : activation à neuf, assistant, import CSV.

## 2. Résultats

| # | Test | Résultat |
|---|---|---|
| a | Activation du thème gaming (base existante + base vierge) | ✅ OK, 0 notice |
| a | Routines de mise à jour : `premium_shop_version` = 2.3.0 → rechargement → 2.3.9, puis 2.3.0 → 2.3.10 | ✅ OK, 0 notice |
| b | Import CSV `gaming-produits-import.csv` (importeur WooCommerce réel, mapping automatique du contrôleur) : 26 produits | 🔧 **corrigé** (GTIN et marque perdus, voir bug 1) |
| b | Par produit : SKU, prix normal/soldé, GTIN, poids, L×l×H, marque (`product_brand`), attributs locaux (noms + valeurs, « 2,4 GHz » en une seule valeur), 1 image + 3 en galerie résolues depuis le thème, métas `_ps_name/_short/_desc_fr/en/es`, tableau technique | ✅ 26/26 après correction |
| b | 2ᵉ import avec `update_existing` : 26 mis à jour, 0 doublon (produits et images). 3ᵉ import sans mise à jour : 26 ignorés | ✅ OK |
| c | Assistant « catalogue gaming » (`premium_shop_gaming_create_catalogue()`) sur table produits vide : mêmes vérifications. Relance : 0 doublon. 6 catégories avec image et traductions | ✅ OK |
| d | Front-end : 15 pages × DE/FR/EN/ES × 390 px / 1280 px = **120 contrôles** | ✅ 0 erreur après corrections |
| d | Pages : accueil, boutique, catégorie, PS5 Slim, JBL Flip, RTX 5070, panier, checkout, mon compte, contact, livraison, retours, confidentialité, conditions d'utilisation, suivi de commande | |
| d | Ces pages : HTTP 200, aucune notice dans la page ni dans debug.log, 0 erreur JS, 0 débordement horizontal | ✅ |
| d | Galerie produit : 4 images | ✅ |
| d | Onglet « Informations complémentaires » : poids, dimensions et attributs traduits, sans aucun nom ni valeur en allemand en FR/EN/ES | ✅ |
| d | Tableau des caractéristiques techniques présent et traduit | ✅ |
| d | Bouton « In den Warenkorb / Ajouter au panier / Añadir al carrito / Add to cart » | ✅ |
| d | Liens « Lieferung: Fristen & Versandkosten » et « Rückgabe & Erstattung » (traduits) sur fiche produit, panier et checkout | ✅ |
| d | Page livraison : tableau des frais avec PostPac Priority CHF 9, Express CHF 19, gratuit dès CHF 150 | ✅ |
| d | Aucune trace « bois de chauffage » dans l'édition gaming | 🔧 **corrigé** (bugs 2, 3, 4) |
| d | Pas d'onglet Brennholz dans l'édition du produit, pas de menu « Brennholz-Preise », pas de question « camion » au checkout | ✅ |
| d | Fiche produit en desktop : colonne résumé collante | 🔧 **corrigé** (bug 6) |
| d | Aperçu rapide (quick view) et ajout au panier AJAX (compteur 0 → 1), DE/FR | ✅ |
| e | Commandes via le checkout en blocs : FR (virement), DE (virement), ES (paiement à la livraison) | ✅ |
| e | Ces commandes : page de remerciement dans la bonne langue, `_ps_language` enregistré, aucun champ camion | ✅ |
| e | E-mails client (FR, DE, ES) et e-mail admin (DE) générés sans erreur, chacun dans la bonne langue | 🔧 **corrigé** (date, bug 5) |
| f | Admin : tableau de bord, édition produit, nouveau produit, liste produits, catégories, importeur, commandes + édition commande, réglages livraison, pages, thèmes, Personnaliser (avec aperçu), Apparence › Premium Shop | ✅ 0 notice, 0 erreur JS du thème |
| f | Aperçu du Personnaliser dans la langue du navigateur au lieu de celle de la boutique | 🔧 **corrigé** (bug 7) |
| — | Passage gaming → bois (`premium-shop.zip`) → gaming, sur la même base : pages, admin, Personnaliser | ✅ aucune erreur fatale ni notice |

## 3. Bugs trouvés et corrections (version 2.3.10)

### 1. GTIN et marque perdus à l'import CSV
- **Fichier** : `inc/woocommerce/import-mapping.php`.
- **Problème** : WooCommerce 11.2 ne fait pas correspondre automatiquement la colonne « GTIN, UPC, EAN, or ISBN ». Il ne reconnaît « Brands » que dans la langue de l'admin (« Marken » en allemand). Avec le mapping par défaut de l'écran d'import, les 26 produits étaient créés **sans GTIN ni marque**.
- **Correction** : ces deux colonnes s'ajoutent au mapping localisé du thème, en EN, DE, FR et ES. La colonne marque n'est ajoutée que si la taxonomie `product_brand` existe.
- **Vérifié** : les 26 produits ont GTIN et marque, à l'import comme à la mise à jour.

### 2. Panier : « la chaleur est en route »
- **Fichier** : `inc/page-hero.php`.
- **Problème** : le sous-titre du panier rempli venait du thème bois (« …und die Wärme ist unterwegs », « …la chaleur est en route »).
- **Correction** : ce texte reste réservé au preset bois. Les autres presets affichent « …et votre commande sera bientôt en route ». La nouvelle chaîne est traduite en DE, FR et ES (`premium-shop-dev/translations_firewood.py`).

### 3. Conditions d'utilisation : « aspect du bois, produit naturel »
- **Fichiers** : `inc/legal/terms-*.html`, `inc/legal.php`, `inc/upgrade.php`.
- **Correction** : l'exemple est devenu une variable `{variation_example}` qui dépend du type de boutique.
  - Gaming : « couleur, emballage ou accessoires selon la série de livraison du fabricant ».
  - Bois : texte inchangé.
- **Pages déjà créées** : elles sont réécrites par une nouvelle étape de mise à jour `premium_shop_upgrade_2_3_10`. Le texte allemand et les traductions `_ps_desc_fr/en/es` sont réécrits, mais seulement si la page n'a pas été modifiée à la main (logique existante).
- **Vérifié** : migration 2.3.9 → 2.3.10 dans les 4 langues.

### 4. Apparence › Thèmes : description « firewood shop module »
- **Fichier** : `premium-shop-dev/build.sh`.
- **Problème** : l'édition gaming reprenait la description de `style.css`, qui présente le module bois de chauffage.
- **Correction** : le build écrit une description gaming dans `premium-shop-gaming/style.css`. Il échoue si le mot « firewood » reste dans ce fichier.

### 5. Dates en anglais en allemand
- **Fichier** : `inc/i18n-content.php`.
- **Problème** : sur un site installé en anglais puis passé en allemand (c'est ce que fait l'assistant « Langues »), le format reste `F j, Y`. Les pages DE et l'e-mail admin affichaient donc « November 8, 2026 ».
- **Correction** : le format standard est aussi adapté à la langue de la boutique. On obtient « 8. November 2026 ». Le FR, l'ES et l'EN étaient déjà corrects.

### 6. Fiche produit (desktop) : le résumé collant recouvrait les onglets
- **Fichiers** : `inc/woocommerce/single-product.php`, `assets/css/woocommerce.css`.
- **Problème** : quand le résumé (prix, boutons, réassurance) est plus haut que la galerie (PS5 Slim, JBL…), la colonne `position: sticky` glissait par-dessus l'onglet « Informations complémentaires » et les produits similaires pendant le défilement. Elle était limitée par toute la grille produit, pas par sa ligne.
- **Correction** : la galerie et le résumé sont maintenant dans un conteneur `.ps-product-top`, qui porte la grille à 2 colonnes. La partie collante reste dans ce bloc.
- **Vérifié** : plus aucun recouvrement, quick view intact.

### 7. Aperçu du Personnaliser rechargé dans la langue du navigateur
- **Fichier** : `inc/i18n.php`.
- **Problème** : avec un navigateur en anglais, le script de détection rechargeait l'aperçu avec `?lang=en`.
- **Correction** : le script est désactivé dans l'aperçu (`is_customize_preview()`).

### Remarque de configuration (pas un bug du thème)

Un texte de moyen de paiement saisi à la main (par exemple « Bitte überweisen Sie… ») ne peut pas être traduit automatiquement. Seuls les textes par défaut de WooCommerce sont traduits par le thème : avec eux, FR, ES et DE étaient corrects.

## 4. Reste à faire (non bloquant)

- ~~Tableaux des pages livraison et retours trop larges sur mobile (390 px)~~ → **corrigé après l’audit** : sur téléphone, chaque ligne devient une carte avec le titre de colonne au-dessus de chaque valeur (largeur 358 px, plus aucun défilement).
- **Poids affiché « 4.6 kg »** (point décimal) : il suit le séparateur décimal des prix de WooCommerce (« . » en Suisse). Le tableau technique écrit « 4,6 kg ». C'est cohérent avec les réglages CHF, à harmoniser seulement si vous le souhaitez.
- **GTIN** : ce sont des codes internes GS1 « 20… ». Remplacez-les par les EAN du fabricant avant Google Shopping ou une place de marché.
- **Coordonnées bancaires (IBAN)** à renseigner dans WooCommerce → Réglages → Paiements → Virement.

## 5. Fichiers livrés

| Élément | Fichier |
|---|---|
| Thème gaming | `dist/premium-shop-gaming.zip` (= `dist/premium-shop-gaming-2.3.10.zip`) |
| Thème bois | `dist/premium-shop.zip` (= `dist/premium-shop-2.3.10.zip`) |
| Catalogue | `dist/gaming-produits-import.csv`, inchangé : les données étaient justes, `build.py` régénère un fichier identique |

**Installation** :
1. Apparence → Thèmes → Ajouter → Téléverser → « Remplacer l'actuel ». Les pages juridiques gaming sont mises à jour au premier chargement.
2. Produits → Importer avec le mapping automatique. GTIN et marque sont maintenant reconnus.

## 6. Captures (`dist/audit-2.3.9/`, JPEG, 390 px et 1280 px)

- `produit-ps5-fr-*.jpg`, `produit-ps5-de-*.jpg` : fiche produit avec galerie de 4 images.
- `infos-complementaires-fr-*.jpg` : onglet « Informations complémentaires » traduit, sans recouvrement.
- `livraison-de-*.jpg` : page « Versand & Zahlung » avec le tableau des frais (CHF 9 / CHF 19 / gratuit dès CHF 150).
- `retours-fr-*.jpg` : page « Retours & remboursements ».
- `checkout-fr-*.jpg` : checkout en blocs avec les 2 modes d'expédition, virement / paiement à la livraison et les liens livraison/retours.
