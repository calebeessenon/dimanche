# Audit de préproduction — Premium Shop 2.3.1

Date : 5 novembre 2026 · Branche : `ccr-761b2ee0-8az5vc`

## 1. Environnement de test

- **Pile** : MariaDB 10.11 + serveur PHP intégré 8.3 (`PHP_CLI_SERVER_WORKERS=4`) + **WordPress 7.1.4** + **WooCommerce 11.2.2**, `WP_DEBUG` et `WP_DEBUG_LOG` activés, un mu-plugin qui capture tous les `wp_mail`.
- **Packs de langue** WordPress + WooCommerce installés depuis wordpress.org : de_DE, fr_FR, es_ES.
- wp-cli n'était pas exécutable dans l'environnement. WordPress a donc été piloté par un petit script PHP d'amorçage (`wp-load.php`).
- **Site A « Warmeholz »** : `dist/premium-shop.zip` (preset bois de chauffage), langue de_DE, CHF, CH:VD. Catalogue d'exemple `premium_shop_fw_create_catalogue(true)`, `premium_shop_setup_pages()`, `premium_shop_setup_menus()`.
- **Site B « NeoPlay »** : `dist/premium-shop-gaming.zip` (preset gaming, mode fêtes actif), de_DE, CHF. Import de `dist/gaming-produits-import.csv` par l'importeur réel de WooCommerce (`WC_Product_CSV_Importer`, mapping automatique du contrôleur).
- **Copie vierge C** du site B : test de l'étape « gaming » de l'assistant (`premium_shop_gaming_create_catalogue`).
- **Copie vierge D** du site B : nouvel import CSV avec le code corrigé.
- Les pages juridiques Impressum / AGB / Widerruf sont créées en brouillon (c'est voulu) et ont été publiées pour l'audit.
- Le site en ligne warmeholz.ch n'a pas été touché.

## 2. Matrice des tests

| Domaine | Site A | Site B | Résultat |
|---|---|---|---|
| 19 pages (accueil, boutique, catégorie, produit simple, produit variable/2ᵉ produit, recherche, panier, checkout en blocs, mon compte, suivi de commande, contact, à propos, livraison, Impressum, AGB, rétractation, confidentialité, liste d'envies, 404) × DE/FR/ES/EN × desktop 1366×900 / mobile 390×844 | 152 pages | 152 pages | ✅ 0 erreur JS, 0 requête en échec, 0 image cassée, 0 débordement horizontal, `<html lang>` correct partout |
| Notices / warnings / erreurs fatales PHP (debug.log) | ✅ vide | ✅ vide | après corrections |
| Ajout au panier AJAX (compteur 0 → 1), 8 contextes par site | ✅ | ✅ | |
| Textes dans la mauvaise langue (comparaison automatique des textes entre langues + relecture des captures) | ✅ | ✅ | restent seulement des noms propres (Zürich, Graubünden, « Isle of Man » dans la liste de pays WooCommerce) |
| Import CSV gaming : 26 produits, 26 images en médiathèque, prix normaux et soldés, 6 catégories (+ traductions), méta `_ps_name/_short/_desc` fr/en/es | — | ✅ | 26 importés, 0 échec, 0 notice (après correctif) |
| Assistant, étape « gaming » sur copie vierge : 26 brouillons avec images, catégories traduites, relance sans doublon | — | ✅ | |
| Catalogue bois + pages + menus | ✅ | — | après correctifs (voir §3) |
| Commande réelle en FR (checkout en blocs, virement bancaire) | ✅ | — | page de remerciement en FR, commande n° 253 « en attente », `_ps_language=fr`, champ camion enregistré |
| E-mail client = langue de la commande | ✅ FR | — | |
| E-mail admin en allemand | ✅ | — | entièrement en allemand après correctif |
| Langue du navigateur à la 1ʳᵉ visite : fr-FR, es-ES, en-US, de-CH, it-IT (→ en) | ✅ | ✅ (copie C) | 1 redirection maximum, aucune boucle, choix mémorisé |
| Le sélecteur de langue (`?lang=` et clic) a toujours la priorité, sans être écrasé ensuite | ✅ | ✅ | |
| Robots (« Googlebot ») non redirigés | ✅ | ✅ | |
| Page servie depuis un cache (HTML allemand rejoué via `route.fulfill` pour fr-FR) | ✅ | ✅ | 1ʳᵉ visite **et** pages suivantes (après correctif) |
| Mode fêtes : guirlande (36 ampoules), neige + bouton mémorisé après rechargement, compte à rebours de Noël qui défile, confettis à l'ajout au panier (46 pièces) | — | ✅ | |
| Guide cadeaux : liens `min_price`/`max_price` → 12 / 5 / 6 / 3 produits, tous dans la bonne fourchette (= 26) | — | ✅ | |
| Texte tapé du hero, compteurs (24 / 100 / 2 / 14), bandeau défilant animé | — | ✅ | |
| `prefers-reduced-motion` : aucune animation infinie, pas de neige, bouton neige masqué, texte du hero figé | — | ✅ | |
| Pas de défilement horizontal (`scrollWidth ≤ innerWidth`), desktop et mobile | ✅ | ✅ | après correctif |

## 3. Problèmes trouvés et corrigés (version 2.3.1)

1. **Langue du navigateur derrière un cache de pages** (`inc/i18n.php`)
   - **Problème** : la 1ʳᵉ visite fonctionnait. Mais quand un cache servait le même HTML allemand à tout le monde, le visiteur français revenait en allemand dès la page suivante (le cookie `ps_lang` est HttpOnly et le script s'arrêtait sur `ps_lc=1`).
   - **Correction** : `ps_lc` contient maintenant le code de la langue choisie. Le serveur le met à jour à chaque `?lang=`, et le script aussi, même quand la page `?lang=` vient d'un cache qui retire le `Set-Cookie`.
   - **Effet** : une page en cache dans une autre langue est rechargée une seule fois dans la langue choisie. Pas de boucle possible, car une URL `?lang=` ne redirige jamais. Le sélecteur garde la priorité, et les anciens cookies `ps_lc=1` restent acceptés.
2. **E-mail admin « Neue Bestellung » en partie en français** pour une commande passée en FR (`inc/woocommerce/order-language.php`, `product-translations.php`, `inc/firewood/delivery.php`)
   - **Problème** : nom du produit, moyen de paiement, question « camion » et réponse, texte de fin « Bravo pour cette vente ! » étaient en français.
   - **Correction** : la traduction des produits suit la langue de l'e-mail. Les champs par défaut des e-mails WooCommerce sont reconstruits dans cette langue. Le champ de checkout en blocs est réenregistré dans la bonne langue (nouvelle action `premium_shop_language_switched`). Le nom de l'article et le titre du moyen de paiement enregistrés sur la commande sont traduits pour l'e-mail.
3. **Dates** : « novembre 5, 2026 » s'affichait en français. La date suit maintenant la langue du visiteur ou de l'e-mail quand le site utilise un format standard de WordPress : « 5 novembre 2026 », « 5 de noviembre de 2026 », « 5. November 2026 » (`inc/i18n-content.php`).
4. **Catalogue d'exemple bois de chauffage uniquement en allemand** en FR/ES/EN (nom, descriptions, quantités « 1 RM », filtres « Menge / Scheitlänge ») (`inc/firewood/demo-catalog.php`, `product-data.php`, `product-translations.php`)
   - Les noms et textes sont maintenant créés dans toutes les langues (« Bois de chauffage en hêtre… », « Leña de haya… »).
   - Les termes de quantité sont traduits (« 1 stère », « 1 stacked m³ », « 1 m³ apilado »).
   - Les libellés d'attribut « Longueur des bûches / Quantité » sont traduits à l'affichage.
5. **Menu « Juridique » du pied de page** (`inc/admin/onboarding.php`, `inc/legal.php`)
   - **Problème** : après l'assistant, la page Nutzungsbedingungen manquait, et Impressum / AGB / Widerruf publiés plus tard n'apparaissaient qu'à la mise à jour suivante du thème.
   - **Correction** : synchronisation à la fin de l'assistant et à la publication d'une page juridique.
6. **Notice PHP pendant l'import CSV** : « Creation of dynamic property WC_Product_Simple::$ID », 2 par produit. Le hook d'import passait un objet produit à `get_post_type()` (`inc/page-hero.php`).
7. **Défilement horizontal de 5 px sur l'accueil gaming** en desktop, dû au bandeau défilant incliné (`assets/css/gaming.css`).
8. **Boutons « Ajouter au panier / Acheter maintenant » coupés sur deux lignes** en FR/ES sur la fiche produit (`assets/css/woocommerce.css`).

Ensuite : nouveau build (`build.sh` : 945 chaînes, aucune traduction manquante, lint PHP OK), nouveau test de tout ce qui précède, version passée en **2.3.1** (`functions.php`, `style.css`, `readme.txt` + changelog). Aucune nouvelle chaîne à traduire n'a été nécessaire : les textes ajoutés existaient déjà dans les catalogues.

## 4. Ce qui reste (non bloquant)

- **« 2 stère / 3 stère »** dans la liste de quantités du catalogue d'exemple : l'unité est au singulier. C'est purement cosmétique et ne concerne que les produits d'exemple. Les produits réels de warmeholz.ch viennent de leur propre CSV.
- **Bouton neige (mode fêtes) sur mobile** : bouton flottant en bas à gauche. Il peut masquer brièvement un prix pendant le défilement. Laissé tel quel (choix de design). Il disparaît si le mode fêtes est désactivé dans l'outil de personnalisation.
- **Virement bancaire** : aucun IBAN n'est configuré sur les sites de test. Pensez à renseigner vos coordonnées bancaires dans WooCommerce → Réglages → Paiements → Virement bancaire.
- **Pages juridiques Impressum / AGB / Widerruf** : toujours créées en brouillon par l'assistant (volontaire). Complétez-les et publiez-les : elles s'ajoutent maintenant seules au pied de page.
- **Captures « panier » et « checkout »** pleine page : les blocs WooCommerce montrent parfois leur squelette de chargement au moment de la capture. Sur les pages finales (voir `audit/12-…`, `audit/13-…`), le contenu est complet.
- **Noms de pays/cantons** non traduits dans les listes (Zürich, Graubünden…) : ce sont des données WooCommerce et des noms propres.

## 5. Fichiers à installer

| Boutique | Fichiers |
|---|---|
| **warmeholz.ch** | `dist/premium-shop.zip` (= `dist/premium-shop-2.3.1.zip`) → Apparence → Thèmes → Ajouter → Téléverser → « Remplacer l'actuel » |
| **Boutique gaming** | `dist/premium-shop-gaming.zip` (thème), puis `dist/gaming-produits-import.csv` via Produits → Importer (mapping automatique, les images sont prises dans le thème) |

Captures de référence : `dist/audit/` (13 PNG).
