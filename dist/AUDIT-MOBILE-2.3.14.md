# Audit mobile — Premium Shop Gaming 2.3.14 (corrigé en 2.3.15)

Date : 8 novembre 2026 · Branche : `ccr-761b2ee0-8az5vc`

**Problème signalé** : sur un téléphone Android (~390 px, français, style gaming, mode fêtes), le panier en blocs coupait la colonne de droite. Le total de ligne « CHF 194.70 » et le badge « Économisez CHF … » sortaient de l'écran. Les audits précédents ne mesuraient que `document.scrollWidth`. Ils ne voyaient donc pas le débordement, masqué par un parent en `overflow: hidden`. La correction de la 2.3.14 (fin de `woocommerce.css`) devait être vérifiée sur le vrai balisage WooCommerce.

**Résultat** : la correction 2.3.14 **ne s'appliquait pas**. Les règles mobiles de WooCommerce (`.is-mobile table.wc-block-cart-items .wc-block-cart-items__row .wc-block-cart-item__total { grid-row-start: 1 }`) sont plus spécifiques. Le total restait donc en ligne 1, **par-dessus le nom du produit**, et le badge recouvrait la description. L'audit a trouvé **24 autres défauts**, tous corrigés en 2.3.15.

## 1. Environnement

- **Pile** : MariaDB 10.11, PHP 8.3 (`php -S`, 8 workers), **WordPress 7.1.4**, **WooCommerce 11.2.2**.
- **Langues** : packs fr_FR, de_DE et es_ES (cœur et WooCommerce) depuis wordpress.org. Langue du site : de_DE.
- **Pilotage** : scripts PHP qui chargent `wp-load.php`.
- **Thème** : `dist/premium-shop-gaming.zip` reconstruit par `premium-shop-dev/build.sh`. Style gaming, **mode fêtes actif** (réglage par défaut : guirlande, neige, bouton ❄).
- **Assistant** : pages, menus et format d'images.
- **Catalogue** : import CSV de `dist/gaming-produits-import.csv` par l'importeur WooCommerce, avec le mapping automatique. 26 produits, 4 images chacun.
- **Boutique** :
  - CHF, Suisse.
  - Zone CH : PostPac Priority CHF 9, Express CHF 19, livraison gratuite dès CHF 150.
  - Paiements : virement (BACS) et paiement à la livraison (COD).
  - Commande invité autorisée.
- **Pages** :
  - panier et commande **en blocs**, tels que WooCommerce les crée (`/cart/`, `/checkout/`) ;
  - panier et commande **classiques** (`[woocommerce_cart]`, `[woocommerce_checkout]`) ;
  - pages juridiques publiées.
- **Panier de test** (comme la cliente) : DualSense Blanche ×3 (CHF 194.70, « Économisez CHF 30.00 »), DualSense Edge ×4 (CHF 796.00), JBL Flip 6 Noir ×3 (CHF 297.00).
- **Clients connectés** : un client par appareil, avec adresse et une commande « en attente ». Ces commandes servent aux pages « commande reçue » et « voir la commande ».

## 2. Méthode

Le test utilise Playwright avec Chromium et l'émulation réelle des appareils : viewport, `deviceScaleFactor`, user-agent, tactile.

| Appareil | Largeur CSS | DPR |
|---|---|---|
| Galaxy S9+ (Playwright) | 320 | 4,5 |
| Android 360 (personnalisé) | 360 | 3 |
| iPhone SE 3ᵉ gén. | 375 | 2 |
| Android 390 (personnalisé, comme la cliente) | 390 | 2,75 |
| Pixel 7 | 412 | 2,625 |
| iPhone 14 Pro Max | 430 | 3 |
| Tablette (personnalisé) | 768 | 2 |
| Bureau (non-régression) | 1280 | 1 |

**Langues** : FR et DE, avec les cookies `ps_lang` / `ps_lc`.

**33 pages ou états par appareil et par langue** :

- accueil, boutique, catégorie ;
- 3 fiches produit : PS5 Slim, JBL Flip 6, RTX 5070 (galerie, onglets, tableau technique, liens de politique, barre d'achat collante) ;
- aperçu rapide, mini-panier, filtres ;
- panier blocs, panier classique ;
- commande blocs (invité et connecté), commande classique ;
- commande reçue ;
- Mon compte : connexion, tableau de bord, commandes, voir la commande, adresses, modifier l'adresse ;
- livraison, retours, CGV, mentions légales, confidentialité, rétractation ;
- contact, suivi de commande, recherche, 404 ;
- menu mobile ouvert, sélecteur de langue ouvert, pied de page.

Cela fait **≈ 520 pages analysées** par passe (8 appareils × 33 pages × 2 langues).

**Détection automatique** sur chaque élément visible :

1. **Hors écran** : boîte au-delà de la fenêtre (gauche < 0 ou droite > largeur + 1).
2. **Contenu rogné** : un parent en `overflow` hidden, clip ou auto a `scrollWidth > clientWidth + 1`, et un enfant visible avec du texte ou un contrôle dépasse. Sont exclus les défilements voulus : `.ps-gmarquee`, rails à scroll-snap, puces de catégories défilantes, galerie flexslider, décors du héros, neige, guirlande, braises.
3. **Texte tronqué** : `text-overflow: ellipsis` ou `line-clamp` qui coupe.
4. **Contrôles recouverts** :
   - boutons principaux (« Ajouter au panier », « Valider la commande », « Commander », connexion, envoi…) amenés en bas, au milieu et en haut de l'écran, puis testés en 5 points avec `elementFromPoint` ;
   - barres fixes qui se recouvrent entre elles ;
   - liens ou boutons recouverts en fin de page par un élément fixe.
5. **Cibles tactiles** < 40 × 40 px pour les contrôles principaux.
6. **Polices** < 12 px.
7. **Images déformées** : rapport d'aspect différent de plus de 4 % avec `object-fit: fill`.
8. **Chevauchements dans l'en-tête** (logo, boutons, sélecteur de langue).

**Vérification visuelle** : une capture pleine page par page et par appareil en FR (JPEG qualité 50, DPR de l'appareil), soit 285 captures. Je les ai **regardées moi-même** sous forme de planches. Les défauts 4 (bureau), 16, 23 et 25 ont été trouvés à l'œil, le script ne les voyait pas.

## 3. Défauts trouvés et corrections

| # | Page | Appareils | Défaut | Correction (fichier) |
|---|---|---|---|---|
| 1 | **Panier blocs** | 320 → 430 | **Défaut signalé.** Le total de ligne s'affichait par-dessus le nom du produit et le badge « Économisez » par-dessus la description. La correction 2.3.14 était écrasée par les règles `.is-mobile table…` de WooCommerce. Sans elle, le total sortait de l'écran à droite. | Grille réécrite sur les classes réelles `.wc-block-cart.is-mobile/.is-small table.wc-block-cart-items tbody …`, plus spécifiques que celles de WooCommerce. Image à gauche ; nom, prix, description et quantité à droite ; total et badge sur leur propre ligne en dessous, séparés par un trait pointillé (`woocommerce.css`). |
| 2 | Commande blocs, résumé | 320 → 430 | Le total et le badge prenaient la moitié de la colonne. Le nom du produit s'affichait un mot par ligne, la description était coupée en plein mot (« personnalisable s »). À 320 px, le résumé sortait de l'écran. | Total et badge sur leur propre ligne sous le produit (`.wc-block-components-order-summary-item`, `woocommerce.css`). |
| 3 | **Mon compte** (tableau de bord, commandes, commande, adresses, modifier l'adresse) | 320 → 768 | Contenu large d'**environ 1040 px**, coupé à droite. La grille sans colonne définie prenait la largeur du menu du compte sur une ligne. | `grid-template-columns: minmax(0, 1fr)`. Sous 1024 px, les entrées du menu passent à la ligne (`woocommerce.css`). |
| 4 | Panier classique | 320 → 768, **1280** | Formulaire et totaux plus larges que l'écran (406 px pour 379 disponibles). La cellule « actions » (code promo, « Mettre à jour le panier ») était en `display: flex` et perdait son `colspan`. À 768 px, le tableau faisait donc 828 px. À 1280 px, les colonnes étaient écrasées : noms sur 4 lignes, bouton « Mettre à jour » sur 4 lignes dans une case de 70 px. | Grille `minmax(0, 1fr)` et tableau responsive en `display: block` sur téléphone. La cellule « actions » reste une cellule de tableau : code promo à gauche en `inline-flex`, bouton à droite. Ligne livraison empilée (`woocommerce.css`). |
| 5 | Panier classique, totaux | 320 → 430 | Chaque total affichait son libellé deux fois (« Sous-total » / « SOUS-TOTAL »). Le lien « Calculer les frais d'expédition » sortait de l'écran. | Totaux en deux colonnes libellé / montant, libellé en double masqué, méthodes et calculateur sur toute la largeur. |
| 6 | Bouton neige ❄ | tous < 1520 | Bouton flottant (z-index 1002) par-dessus : le titre de l'aperçu rapide, le numéro de téléphone du menu mobile, les champs de la commande, la barre d'achat collante (fiches produit 320 → 768), le bouton « Commander » (commande classique, 768), « Se connecter » (412, 390), le texte du pied de page et les champs de la commande à 1280. | Sous 1520 px (là où la marge gauche est plus étroite que le bouton), le bouton flottant est masqué. Un bouton « ❄ Neige activée / désactivée » est ajouté dans la barre du bas du pied de page, y compris le pied de page de la commande. Le JS pilote les deux boutons (`gaming.css`, `gaming.js`, `inc/gaming/gaming.php`, nouveau hook `premium_shop_footer_bottom` dans `template-parts/footer/*.php`). |
| 7 | Guirlande | tous | `position: fixed` avec z-index 1001. En défilant, elle couvrait le haut de l'en-tête collant (menu, logo, pastille du panier). Elle passait aussi par-dessus le menu, le mini-panier et l'aperçu rapide ouverts. | `position: absolute` (elle défile avec la page) et z-index 999, sous les tiroirs (`gaming.css`). |
| 8 | Toast « ajouté au panier » | tous | Une fois caché (opacité 0), il **interceptait encore les clics** en bas de l'écran : « Ajouter au panier » (360), « S'inscrire » / « Se connecter » (320 → 430), « Envoyer le message » (360), « Commander » (768). | `pointer-events: none` tant qu'il est caché (`main.css`). |
| 9 | Bouton « retour en haut » | 320 → 430, panier et commande à 768 | Il couvrait le bout des boutons pleine largeur (« Envoyer le message » 360/430, « S'inscrire », « Se connecter ») quand ils arrivaient en bas de l'écran. Sur tablette, il passait sous la barre « Valider la commande » de WooCommerce. | Masqué sous 768 px (l'en-tête collant reste) et sur panier / commande sous 1024 px (`main.css`). |
| 10 | Fiches produit, onglets | 320 → 430 (FR), 768 (DE) | « Avis », « Livraison & retours » (DE « Zusätzliche Informationen », « Versand & Rückgabe ») hors écran, dans un défilement sans barre. | Sous 1024 px, les onglets passent à la ligne en pastilles de 44 px, onglet actif en couleur (`woocommerce.css`). |
| 11 | Aperçu rapide | 375 | Libellé du bouton coupé (« Ajouter au pan… ») : le formulaire était en `nowrap`, et la règle `form.cart` plus spécifique annulait la règle de l'aperçu. | Le formulaire passe à la ligne, le bouton garde sa largeur de contenu (`form.cart.ps-qv__form …`). |
| 12 | Cartes produit | 320 → 430 | Bouton « Ajouter a… » tronqué. | Libellé sur deux lignes. Sous 375 px, l'icône « + » est masquée pour garder deux lignes. |
| 13 | Cartes produit | 320 → 430, 768 | Noms coupés après 2 lignes (« Mario Kart World (Nintendo Swit… », « Carte graphique GeForce RTX… »). | Sur téléphone, le nom s'affiche en entier. Dès 768 px, limite portée à 3 lignes. |
| 14 | Cartes produit (mode fêtes) | 320 → 430 | La boule de Noël suspendue cachait le badge « NOUVEAU ». | Boule masquée sous 600 px, faute de place (`gaming.css`). |
| 15 | Mon compte, adresses (DE) | 320 → 390 | « Rechnungsadresse » et « Rechnungsadresse bearbeiten » côte à côte : cartes de 378 px, hors écran. | Titre qui passe à la ligne ; grilles d'adresses en `minmax(0, 1fr)`, aussi sur commande reçue et commande. |
| 16 | **En-tête** | 320 | Le sélecteur de langue chevauchait le logo (« NeoP[FR] »). Le logo de 124 px débordait de sa colonne de 83 px. | Sous 480 px, le logo se réduit à sa colonne. Sous 360 px, le chevron du sélecteur est masqué (`main.css`). |
| 17 | Commande classique (DE) | 320 | Formulaire et récapitulatif plus larges que l'écran (noms allemands longs). | Grille `minmax(0, 1fr)` et coupure des mots longs dans le récapitulatif. |
| 18 | Fiches produit | 430 et 768 (DE) | La barre d'achat collante couvrait les derniers boutons et liens de la page (liens juridiques du pied de page). | Marge basse de 100 px sur les fiches produit sous 1024 px. |
| 19 | Panier blocs | tous | Boutons − / + de 30 px de large, « Retirer » de 24 × 24 px. | 40 px minimum (sélecteurs plus spécifiques que WooCommerce). |
| 20 | Tous | 320 → 768 | Cibles tactiles trop petites : fermeture du bandeau (32), sélecteur de langue (34 de haut), « × » du panier classique (32) et du mini-panier (28), bouton « Voir » des commandes (38), boutons de vue grille / liste (34). | Toutes à 40 px minimum. Le chevron « Catégories » du menu bureau passe aussi à 40 px, avec des marges négatives. |
| 21 | Tous | tous | Textes de 9,9 à 11,8 px : surtitres, catégories des cartes, badges « NOUVEAU » / « −10 % », titres du pied de page, codes langue, en-têtes de tableaux, compte à rebours, stats du héros, étiquettes contact. Côté blocs : description, ancien prix, badge d'économie, « PostPac Priority », quantité du résumé. | 12 px minimum (`.75rem`). Blocs : `.875rem`, ou `max(12px, 1em)` (`main.css`, `woocommerce.css`, `gaming.css`). |
| 22 | Boutique (bureau) | 1280 | La barre de filtres collante défilait de 4 px à l'horizontale : le fond de survol des catégories était coupé. | Marge intérieure de 10 px des deux côtés. |
| 23 | Commande (mode fêtes) | tous | Sans bandeau d'annonce (commande, bandeau fermé), les ampoules de la guirlande pendaient sur le logo. | L'en-tête descend de 22 px dans ce cas (`gaming.css`). |
| 24 | Accueil | 375, 390 | L'étiquette « Soldes des fêtes » du héros touchait le bord gauche (−1 px). | `left: 0` sur téléphone. |
| 25 | Fiches produit, barre d'achat collante | 320 → 430 | Nom réduit à « Play… » à 320 px. | Sous 480 px : nom sur deux lignes, ancien prix barré masqué dans la barre (il reste sur la fiche), bouton moins large. |

**Vérifiés sans défaut** :

- images : aucune image déformée ;
- tableaux livraison, retours et CGV ;
- page de contact et suivi de commande ;
- commande reçue ;
- 404 et recherche ;
- sélecteur de langue ouvert ;
- moyens de livraison et de paiement visibles en commande blocs, en invité comme connecté ;
- « Valider la commande » et « Commander » jamais recouverts après correction.

**Gardés tels quels (choix de conception)** :

- Dans la **barre d'achat collante**, les noms longs restent coupés par « … » après deux lignes. C'est un rappel compact, le nom complet est affiché sur la fiche.
- Défilement horizontal voulu : bandeau défilant, rails de produits, puces de catégories et galerie. Ils ont tous un défilement tactile ou des flèches.

## 4. Résultat final (2.3.15)

Dernière passe complète sur le thème reconstruit : 8 appareils × 33 pages × FR et DE.

Chaque défaut compte une fois par page, par type et par élément. Avant correction, il s'agit surtout des textes < 12 px et des cibles tactiles, répétés sur chaque page (en-tête, cartes, pied de page). Les défilements voulus et les décors listés en §2 sont exclus.

| Appareil | Pages FR | Pages DE | Défauts FR avant (2.3.14) | Défauts FR après | Défauts DE après |
|---|---|---|---|---|---|
| Galaxy S9+ (320) | 33 | 33 | 305 | **0** | **0** |
| Android 360 | 33 | 33 | 306 | **0** | **0** |
| iPhone SE 3ᵉ gén. (375) | 33 | 33 | 307 | **0** | **0** |
| Android 390 | 33 | 33 | 302 | **0** | **0** |
| Pixel 7 (412) | 33 | 33 | 294 | **0** | **0** |
| iPhone 14 Pro Max (430) | 33 | 33 | 286 | **0** | **0** |
| Tablette 768 | 33 | 33 | 277 | **0** | **0** |
| Bureau 1280 (menu mobile, sélecteur compact et bouton Filtres n'existent pas sur bureau) | 30 | 30 | 183 | **0** | **0** |

- **522 pages** analysées sur la dernière passe : 0 débordement, 0 contenu rogné, 0 bouton recouvert, 0 cible < 40 px, 0 texte < 12 px, 0 image déformée, 0 chevauchement dans l'en-tête.
- Aucune erreur JavaScript de page. Seules réponses non-200 : la page 404 testée, qui répond 404.
- Le tiroir du menu a parfois été mesuré pendant son animation d'ouverture (0,5 s), sous la charge de 8 navigateurs en parallèle. Mesuré seul, il est en place après 0,8 s, sans débordement.
- Les fiches produit ont été revérifiées en FR et DE (8 appareils) après le dernier ajustement de la marge basse.

Desktop 1280 px : les pages modifiées ont été revues (panier blocs et classique, commande, Mon compte, boutique, fiche produit, pied de page). Aucune régression. Le bouton ❄ est dans le pied de page sous 1520 px.

## 5. Captures avant / après

Dossier `dist/audit-mobile/` (JPEG qualité 60, 1,5 × la largeur CSS, 2,7 Mo au total) :

| Fichier | Contenu |
|---|---|
| `panier-blocs-iphone-se-avant.jpg` / `-apres.jpg` | Panier blocs, iPhone SE 375 px, FR |
| `panier-blocs-pixel-7-avant.jpg` / `-apres.jpg` | Panier blocs, Pixel 7 412 px, FR |
| `commande-blocs-iphone-se-avant.jpg` / `-apres.jpg` | Commande blocs (invité), iPhone SE, FR |
| `commande-blocs-pixel-7-avant.jpg` / `-apres.jpg` | Commande blocs (invité), Pixel 7, FR |
| `produit-ps5-pixel-7-avant.jpg` / `-apres.jpg` | Fiche PS5 Slim, Pixel 7, FR |
| `menu-mobile-pixel-7-avant.jpg` / `-apres.jpg` | Menu mobile ouvert, Pixel 7, FR |

Sur les captures pleine page, les éléments fixes (barre « Valider la commande » de WooCommerce, en-tête) apparaissent à leur position dans le premier écran. C'est un effet de la capture.

## 6. Livrables

- Thème **2.3.15** : `PREMIUM_SHOP_VERSION`, `style.css`, `readme.txt` (Stable tag et journal des modifications).
- `dist/premium-shop-gaming.zip` et `dist/premium-shop-gaming-2.3.15.zip` (édition gaming).
- `dist/premium-shop.zip` et `dist/premium-shop-2.3.15.zip`.
- **Mise à jour** : Apparence › Thèmes › Ajouter › Téléverser, puis remplacer la version installée. Aucun réglage à changer.
