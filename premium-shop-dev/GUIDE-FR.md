# Premium Shop — Guide complet (FR)

Thème WordPress WooCommerce premium, multilingue (🇩🇪 allemand par défaut, 🇫🇷 🇪🇸 🇬🇧),
prêt à l'emploi. Ce guide couvre l'architecture, la direction artistique, l'installation,
la personnalisation, le multilingue et la maintenance.

---

## 1. Installation (5 minutes)

1. **WordPress → Apparence → Thèmes → Ajouter → Téléverser un thème** → `premium-shop.zip` → *Installer* → *Activer*.
2. **Extensions → Ajouter** → installer et activer **WooCommerce**.
3. **Apparence → Configuration de la boutique** (assistant intégré) — chaque étape en un clic :
   - **Créer les pages** : *Über uns*, *Kontakt*, *Wunschliste*, *Versand & Zahlung* (publiées) +
     *Impressum*, *AGB*, *Widerrufsbelehrung* (en brouillon : à compléter par vos textes juridiques avant publication).
   - **Créer les menus** : menu principal avec méga-menu des catégories, menus du pied de page, menu légal.
   - **Installer les langues DE / FR / ES** : télécharge les traductions WordPress + WooCommerce et règle le site en allemand.
   - **Optimiser les images produits** : vignettes portrait 4:5 (600 px).
4. **Apparence → Personnaliser → Premium Shop — Options du thème** : logo, couleurs, textes, etc.

Ensuite, travail courant **sans code** :
créer une catégorie → ajouter un produit → photos → prix → stock → variations → (traduire) → publier.
WooCommerce reste le système central ; le thème n'impose aucun système propriétaire.

> Le thème fonctionne aussi sans WooCommerce (blog/pages), mais les fonctions boutique nécessitent WooCommerce.

---

## 2. Direction artistique « Maison »

| Élément | Choix |
|---|---|
| Ambiance | Éditoriale, chaleureuse, luxueuse mais sobre — inspirée des grandes maisons de concept-store |
| Fond | Ivoire chaud `#fbf9f5`, sections douces sable `#f2ede5`, cartes blanches |
| Texte / primaire | Encre profonde `#16181d` / `#1b1d22` |
| Accent | Cognac `#8f602b` (contraste WCAG AA garanti, y compris texte blanc sur bouton) |
| Promotion / succès | Rouge `#b3261e` / vert sapin `#2e6b4f` |
| Titres | **Fraunces** (serif variable, élégant) — auto-hébergée |
| Texte | **Inter** (sans-serif variable, très lisible) — auto-hébergée |
| Boutons | Identité forte : remplissage cognac qui « monte » au survol + flèche qui glisse |
| Signature visuelle | Hero avec image en **arche**, orbes flottants, grille « bento » des catégories, bandeau d'annonce rotatif |
| Animations | Apparitions douces au défilement, micro-interactions ; désactivées si l'utilisateur préfère réduire les animations |

Les polices sont **hébergées dans le thème** (aucune requête à Google Fonts → conforme RGPD/DSGVO,
point important en Allemagne). Tout le design est piloté par des **variables CSS** générées depuis le Customizer.

---

## 3. Architecture

```
premium-shop/
├── style.css                  En-tête du thème (métadonnées)
├── functions.php              Amorçage : charge les modules de /inc
├── theme.json                 Palette & tailles pour l'éditeur de blocs
├── wpml-config.xml            Textes du Customizer traduisibles avec WPML
├── screenshot.png             Aperçu 1200×900
├── header.php / footer.php    Squelette (promo, header, tiroirs, footer)
├── front-page.php             Accueil : sections dans l'ordre choisi
├── index.php, archive.php, search.php, single.php, page.php, 404.php
├── comments.php, sidebar.php, searchform.php
├── page-templates/            Pleine largeur, Liste d'envies
├── assets/
│   ├── css/  main.css · woocommerce.css · editor.css (+ .min.css)
│   ├── js/   theme.js · shop.js · product.js · customizer-*.js (+ .min.js)
│   ├── fonts/ Inter & Fraunces (WOFF2 variables, licence OFL)
│   └── images/ placeholder.svg
├── inc/
│   ├── helpers.php            Options, textes traduisibles, utilitaires
│   ├── i18n.php               Multilingue (WPML/Polylang/TranslatePress + mode intégré)
│   ├── icons.php              Jeu d'icônes SVG inline
│   ├── setup.php              Supports du thème, menus, tailles d'images, zones de widgets
│   ├── enqueue.php            Chargement conditionnel & différé des assets
│   ├── template-tags.php      Logo, menus, sélecteur de langue, fil d'Ariane, footer…
│   ├── class-premium-shop-walker-nav.php  Menus accessibles + méga-menu catégories
│   ├── newsletter.php         Newsletter double opt-in (RGPD) + export CSV
│   ├── security.php · accessibility.php · seo.php
│   ├── customizer/            config.php (déclaratif) · customizer.php · dynamic-css.php · contrôle « sortable »
│   ├── admin/onboarding.php   Assistant de configuration
│   └── woocommerce/           setup · product-card · shop-filters · single-product
│                              cart-checkout · account · ajax · admin-fields
├── template-parts/
│   ├── header/  promo-bar · site-header · mobile-menu · search-overlay · cart-drawer · header-checkout
│   ├── footer/  site-footer · footer-checkout
│   ├── homepage/ hero · categories · products · campaign · benefits · testimonials · brands · newsletter · content
│   ├── products/ quick-view · quick-view-modal
│   ├── components/ search-form · newsletter-form · hero-art
│   └── content/  content · content-search · content-none
├── woocommerce/               Seulement 2 surcharges : archive-product.php, content-product.php
└── languages/                 premium-shop.pot + de_DE / fr_FR / es_ES (.po, .mo, .l10n.php)
```

**Principes** : WooCommerce est intégré par **hooks** (actions/filtres) plutôt que par surcharge de
templates (seulement 2 templates surchargés, en conservant tous les hooks d'origine) → les mises à
jour WooCommerce restent sans risque. Chaque option du Customizer est déclarée **une seule fois**
dans `inc/customizer/config.php` (contrôle, valeur par défaut et nettoyage générés automatiquement).

---

## 4. Ce qui est personnalisable sans code

**Apparence → Personnaliser** :
- *Identité du site* : logo, favicon (icône du site), nom.
- *Menus* : menu principal, footer Boutique, footer Service client, footer Légal.
- **Premium Shop — Options du thème** :
  - **Couleurs** (14 réglages, aperçu en direct) ;
  - **Typographie & boutons** : polices, taille, graisse, forme des boutons (carré / doux / pilule), majuscules, arrondi des cartes, animations ;
  - **En-tête & bandeau** : hauteur du logo, disposition (logo à gauche / centré), en-tête fixe, icônes, méga-menu, checkout épuré, messages du bandeau (un par ligne → rotation), lien ;
  - **Accueil — sections & ordre** : glisser-déposer, afficher/masquer, nombre de produits/catégories, titres ;
  - **Hero**, **Bannière de campagne**, **Avantages**, **Témoignages**, **Marques**, **Newsletter** ;
  - **Boutique & cartes produits** : colonnes, produits par page, filtres, AJAX, vue par défaut, 2e image, aperçu rapide, liste d'envies, badges ;
  - **Page produit** : « Acheter maintenant », barre mobile, encadré de réassurance, délai de livraison, retours, garantie, onglet « Livraison & retours » ;
  - **Panier & livraison** : seuil de livraison gratuite (barre de progression), panier latéral, recommandations ;
  - **Entreprise & contact**, **Réseaux sociaux**, **Pied de page** (copyright, moyens de paiement), **Langues**.

Les champs texte vides affichent un texte par défaut **traduit automatiquement** dans la langue du visiteur.

---

## 5. Multilingue

### Langue par défaut : allemand
Au premier passage, le visiteur voit le site en **allemand**. Le sélecteur **DE | FR | ES | EN** est dans l'en-tête (et dans le menu mobile).

### Trois modes, détectés automatiquement
| Situation | Comportement |
|---|---|
| **WPML**, **Polylang** ou **TranslatePress** actif | L'extension gère langues, URL (`/fr/`, `/es/`…) et traduction des produits ; le sélecteur du thème l'utilise. **Recommandé en production** (SEO multilingue, hreflang). |
| Aucune extension | **Mode intégré léger** : `?lang=fr` + cookie ; l'interface (thème + WordPress + WooCommerce) change de langue. Les contenus (produits) restent dans leur langue de saisie. |
| Désactivé | Customizer → Langues → Mode : Désactivé. |

### Traduire vos propres textes (hero, bannières…)
- Avec WPML/Polylang : *Traduction de chaînes* → groupe « Premium Shop ».
- Sans extension : syntaxe dans le champ : `[:de]Jetzt entdecken[:fr]Découvrir[:es]Descubrir[:en]Discover`.

### Ajouter une langue (italien, portugais, néerlandais…)
1. Customizer → Premium Shop → **Langues** → « Langues proposées » : `de,fr,es,en,it`.
2. Réglages → Général : installer l'italien (ou bouton de l'assistant).
3. Traduire le thème : extension **Loco Translate**, ou créer `languages/it_IT.po/.mo` à partir de `premium-shop.pot`.
Aucune modification d'architecture n'est nécessaire (registre extensible via le filtre `premium_shop_language_registry`).

### Pour les développeurs
Toutes les chaînes utilisent `__()`, `_e()`, `_x()`, `_n()`, `esc_html__()`, `esc_attr__()`… avec le domaine `premium-shop`.

---

## 6. Fonctionnalités WooCommerce

- Produits simples, variables, virtuels, téléchargeables, groupés, externes ; promotions ; stocks ; attributs ; variations ; coupons ; avis ; commandes ; e-mails WooCommerce (non modifiés).
- **Compatible HPOS** : uniquement les API CRUD de WooCommerce (aucun accès direct aux tables de commandes).
- Panier et commande **classiques (shortcodes) et en blocs** pris en charge.
- **Carte produit** : 2e image au survol, badges −X % / Nouveau / Épuisé, aperçu rapide, liste d'envies, catégorie, étoiles, prix barré, stock (« Plus que 2 »), ajout au panier AJAX.
- **Boutique** : filtres instantanés (catégories, prix avec double curseur, disponibilité, promotions, attributs avec pastilles couleur, marques, note, recherche), puces de filtres actifs, tri, pagination, grille/liste, tiroir de filtres mobile ; URLs propres et partageables ; fonctionne aussi sans JavaScript.
- **Page produit** : galerie avec zoom, visionneuse, miniatures ; **vidéo** (champ « Vidéo du produit » : YouTube, Vimeo ou MP4, chargée à la demande) ; référence ; « Acheter maintenant » → directement au paiement ; réassurance ; onglet **Versand & Rückgabe** ; barre d'achat fixe sur mobile.
- **Pastilles de couleur** : Produits → Attributs → (termes) → « Couleur de la pastille ».
- **Panier** : barre « Plus que X € pour la livraison gratuite », « Das könnte Ihnen auch gefallen » (ventes croisées, sinon meilleures ventes).
- **Commande** : étapes Panier → Commande → Confirmation, en-tête épuré « Paiement sécurisé », réassurance.
- **Compte client** : salutation, navigation avec icônes, tuiles du tableau de bord.
- **Recherche instantanée** : produits (image, prix, catégorie), catégories, navigation clavier, dans la langue active.

---

## 7. Performance, SEO, sécurité, accessibilité

- **Performance** : aucune bibliothèque lourde ; JS vanilla (~16 Ko min.), chargé en `defer` et **conditionnellement** (shop.js seulement sur la boutique, product.js seulement sur les fiches) ; CSS/JS minifiés ; polices WOFF2 préchargées ; images `lazy`, image du hero en `fetchpriority=high` ; requêtes AJAX via le routeur rapide `wc-ajax`.
- **SEO** : HTML sémantique, un seul H1 par page, hiérarchie H2/H3, textes ALT de secours, fil d'Ariane (Yoast/Rank Math si activés), données structurées WooCommerce intactes, `noindex` des pages filtrées (si aucune extension SEO ne gère), compatibilité Yoast SEO et Rank Math (le thème ne les remplace pas).
- **Sécurité** : échappement systématique (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`), nettoyage de toutes les entrées, **nonces** et vérification des **droits** sur toutes les actions (assistant, newsletter, export, champs produit), honeypot + limitation de débit pour la newsletter, jetons hachés pour le double opt-in, protection CSV.
- **Accessibilité** : lien d'évitement, navigation clavier complète (menus, tiroirs avec piège de focus, Échap), focus visible, ARIA (`aria-expanded`, `aria-current`, `aria-live`…), contrastes WCAG AA (vérifiés avec axe-core), `prefers-reduced-motion` respecté.

---

## 8. Tests réalisés

Sur WordPress 6.5 + WooCommerce 9.1 avec données de démonstration :
- aucune erreur/avertissement PHP sur toutes les pages publiques et d'administration ;
- aucune erreur JavaScript ;
- **aucun débordement horizontal** aux largeurs 320, 375, 390, 414, 768, 1024, 1280, 1440, 1920 px sur 10 pages ;
- scénarios : filtres AJAX + historique du navigateur, tri, recherche instantanée, aperçu rapide → panier, ajout AJAX → panier latéral, liste d'envies, « Acheter maintenant » → paiement, changement de langue DE/FR/ES/EN (cookie), Customizer (tri des sections, couleurs en direct), newsletter double opt-in, vidéo produit ;
- audit axe-core WCAG 2 AA.

---

## 9. Développement & reconstruction

Les outils de build sont dans `premium-shop-dev/` (non inclus dans le zip du thème) :

```bash
cd premium-shop-dev
bash build.sh        # traductions (.pot/.po/.mo/.l10n.php) + minification + lint PHP + dist/premium-shop.zip
```

- `make-pot.py` : extrait les chaînes ; `translations.py` : traductions DE/FR/ES ; `build-languages.py` : génère les fichiers de langue.
- En mode `SCRIPT_DEBUG`, WordPress charge les fichiers non minifiés.
- Pour des modifications profondes, créez un **thème enfant** (les templates et fonctions sont filtrables : `premium_shop_*`).

### Filtres utiles
`premium_shop_language_registry`, `premium_shop_languages`, `premium_shop_home_sections`,
`premium_shop_product_badges`, `premium_shop_product_trust_items`, `premium_shop_text_fallbacks`,
`premium_shop_customizer_config`, `premium_shop_dynamic_css`, `premium_shop_live_search_args`,
`premium_shop_free_shipping_threshold`, `premium_shop_fallback_menu_items`.

---

## 10. Points d'attention juridiques (Allemagne)

Le thème fournit la structure ; le contenu juridique reste sous votre responsabilité :
complétez *Impressum*, *AGB*, *Widerrufsbelehrung* et la *Datenschutzerklärung*, et pour les mentions
de prix (TVA, frais de port, « Grundpreis ») utilisez une extension spécialisée comme
**Germanized** ou **German Market**, compatibles avec ce thème (hooks WooCommerce standard).
Les témoignages affichés proviennent uniquement de vrais avis clients (aucun avis fictif).

---

## 11. Module « Bois de chauffage » (Brennholz) — ce qui est automatisé

Le thème est préréglé pour une boutique de bois de chauffage (modèle de style **Brennholz**, activé
par défaut) : palette vert forêt + cuivre braise sur fond crème, textes d'accueil, avantages,
FAQ et sections adaptés, traduits en DE/FR/ES/EN. Vous pouvez revenir au style « Maison » dans
*Personnaliser → Premium Shop → Boutique de bois de chauffage → Modèle de style*.

### Démarrage en 1 clic
**Apparence → Configuration de la boutique → « Créer le catalogue d'exemple bois de chauffage »** crée, en allemand :
- 4 catégories avec images : *Kaminholz*, *Anzündholz*, *Holzbriketts*, *Kaminholz-Boxen* ;
- les attributs **Scheitlänge** (25/33/50 cm) et **Menge** (1, 2, 3, 6 RM) ;
- la classe de livraison **Spedition** ;
- 8 produits : Buche, Eiche, Birke, Esche, Hartholz-Mix (12 variations chacun, prix calculés),
  Anzündholz 10 kg, Holzbriketts 10 kg, Kaminholz-Box 30 l — avec fiche technique et description.

Les produits sont créés **en brouillon** : vérifiez les prix, remplacez les illustrations par vos
photos, puis publiez (cochez « Publier immédiatement » si vous préférez).

### Fiche produit : onglet « Brennholz »
Dans chaque produit : essence, longueur des bûches, humidité résiduelle, séchage, unité de vente
(RM, SRM, FM, kg, litre), origine, certification, **prix par unité** et 2 remises quantité.

| Vous saisissez | Le thème fait automatiquement |
|---|---|
| Prix par RM (ex. 159 €) + « Calculer les prix automatiquement » | Le prix de **chaque variation** = prix/RM × quantité lue dans l'attribut (« 2 RM », « 1,8 SRM »…) − remise |
| Remises : dès 3 RM −5 %, dès 6 RM −10 % | Appliquées aux variations concernées |
| Rien dans la description | **Description + description courte** rédigées dans la langue principale (modifiables) |
| Essence, longueur, humidité, séchage | **Pastilles** sur les cartes et la fiche, **onglet « Holz-Datenblatt »**, pouvoir calorifique estimé |
| Unité + quantité | **Prix de base (Grundpreis)** obligatoire en Allemagne : « Grundpreis: ab 143,10 € / RM » |
| Produit variable | Prix affiché « ab 159,00 € » au lieu d'une longue fourchette |

### Changer tous les prix en une fois
**Produits → Brennholz-Preise** : tableau de tous vos produits bois ; modifiez les prix par RM ou
appliquez « +5 % » à tout le catalogue → toutes les variations sont recalculées (testé : 60 prix en un clic).

### Import Excel / CSV
`dist/brennholz-produkte-vorlage.csv` : modèle prêt pour **Produits → Importer** (WooCommerce).
Remplissez-le dans Excel/LibreOffice (une ligne par produit, une ligne par variation) : les prix et
descriptions sont calculés à l'import. Colonnes spéciales : `Meta: _ps_species` (beech, oak, ash,
birch, hornbeam, mixed, alder, pine, spruce), `Meta: _ps_unit` (rm, srm, fm, kg, liter, piece),
`Meta: _ps_drying` (kiln, air, fresh), `Meta: _ps_price_per_unit`, `Meta: _ps_auto_prices` (yes)…

**Images introuvables :** si une adresse d'image de la colonne Images ne peut pas être téléchargée,
le produit est quand même importé (sans cette image) et la liste des images manquantes s'affiche
en haut de **Produits**. WooCommerce seul rejetterait toute la ligne.

**Reprendre l'export d'une autre boutique WooCommerce :**
`python3 premium-shop-dev/clean-import.py export.csv propre.csv [--no-images] [--sku-prefix WH]`
convertit les colonnes en anglais, supprime les anciens ID, crée des références (WH-0001…), complète
les descriptions courtes vides et lit dans les noms l'essence, la longueur, les stères et les kg.

### Livraison par code postal
1. **WooCommerce → Réglages → Expédition** : créez une zone par secteur de livraison, avec ses
   codes postaux (`72*`, `70000...71999`, `10115`) et un tarif (ex. « Lieferung per Spedition – 49 € »).
2. Le module **« Liefern wir zu Ihnen? »** (accueil, fiche produit, panier) répond automatiquement au
   client : zone desservie, tarifs, délai — et mémorise le code postal pour la commande.
3. À la commande, le client indique si le lieu de déchargement est **accessible à un camion**
   (visible dans la commande et les e-mails).

### Calculateur et FAQ
- **Calculateur de besoin** (puissance du poêle × heures × jours ÷ pouvoir calorifique de l'essence)
  + **convertisseur RM / SRM / FM** : sur l'accueil et en onglet sur les fiches produit.
- **FAQ bois** (RM/SRM/FM, humidité, longueur, livraison, stockage, essences) avec données structurées
  FAQPage pour Google. Personnalisable : une ligne par question, format `Question :: Réponse`.
- Codes courts utilisables dans n'importe quelle page : `[ps_delivery_check]`, `[ps_firewood_calculator]`, `[ps_faq]`.

### Ce que vous devez encore faire vous-même
- Vos **photos** (les illustrations fournies ne sont que des visuels provisoires) ;
- vos **prix**, vos **zones de livraison** et leurs tarifs ;
- vos textes légaux (Impressum, AGB, Widerrufsbelehrung, Datenschutz).


## 12. Audit 1.2.0 (WordPress 7.1 + WooCommerce 11.2)

Testé sur un site neuf WordPress 7.1.3 + WooCommerce 11.2.0 (et en non-régression sur WordPress 6.5 + WooCommerce 9.1) :
produits simple / variable / groupé / externe / virtuel-téléchargeable, promo, stock faible, rupture, précommande,
vente à l'unité, avis, galerie + zoom + lightbox, panier et caisse **classiques et en blocs**, coupon, TVA, 3 modes
de livraison, 3 paiements, page de remerciement, compte client (commandes, téléchargements, adresses, mot de passe
oublié, inscription), widgets WooCommerce, blocs WooCommerce dans une page, mode « bientôt disponible »,
Customizer et aperçu en direct, outils PHPCS (sécurité, i18n, PHP 7.4+) et Theme Check.

Corrections : voir readme.txt → Changelog 1.2.0.


## 13. Version 1.3.0 — multilingue produits, contact, suivi de commande

### Textes des produits en plusieurs langues (sans extension)
- Fiche produit → onglet **Übersetzungen / Traductions** : nom, description courte et description pour
  chaque langue du sélecteur (DE, FR, ES, EN). Champ vide = texte principal du produit.
- Les noms de catégories se traduisent dans Produits → Catégories → modifier (champs « Name — Français »…).
  Les catégories courantes (Bois de chauffage, Bûches compressées, Granulés et pellets…) sont pré-remplies.
- Import CSV : colonnes `Meta: _ps_name_fr`, `Meta: _ps_short_fr`, `Meta: _ps_desc_fr` (idem `_en`, `_es`).
- `dist/warmeholz-produits-multilingue.csv` : les 89 produits, textes principaux en allemand,
  traductions FR (originales), EN et ES. Fabriqué par `premium-shop-dev/catalog-i18n/build-multilingual.py`.
- Avec WPML / Polylang / TranslatePress, ces champs sont ignorés (traduisez avec l'extension).

### Import CSV plus robuste
- Les colonnes d'un export WooCommerce en français, allemand ou espagnol sont reconnues quelle que soit
  la langue de l'administration (WooCommerce seul ne reconnaît que la langue de l'admin et l'anglais).
- Produits « Import placeholder for … » : bouton « Les mettre à la corbeille » en haut de Produits ;
  réimporter le même fichier avec « Mettre à jour les produits existants » les répare (nom, prix, URL).
- Essence, longueur, stères, kg, séchage et humidité sont lus dans les noms des produits.

### Page Contact
- Modèle de page **Contact** : coordonnées (Personnaliser → Contact) + formulaire prêt (nom, e-mail,
  téléphone, n° de commande, objet, message, consentement RGPD). Anti-spam sans service externe.
- Les messages sont envoyés à l'e-mail de contact **et** enregistrés dans l'admin (menu « Kontaktanfragen »),
  supprimés automatiquement après un an.

### Page Suivi de commande (Sendungsverfolgung)
- Le client saisit n° de commande + e-mail → frise d'état (reçue → en préparation → en route → livrée).
- Dans chaque commande (admin), encadré **Livraison & suivi** : date de livraison prévue, transporteur,
  numéro de suivi (lien automatique pour La Poste CH, DHL, DPD, GLS, UPS), note au client.
  Affiché aussi dans Mon compte et dans les e-mails de commande.

### Mobile
- Bouton de langue « DE » dans l'en-tête mobile + sélecteur en haut du menu.

À la mise à jour, le thème ajoute automatiquement le formulaire à la page « kontakt », crée la page
« sendungsverfolgung », l'ajoute au menu Service client et retire les widgets par défaut de WordPress
de la barre latérale de la boutique.

## 14. Version 1.3.1 — import Excel et produits sans prix

- **Produits → Tous les produits** : un encadré jaune liste les produits publiés
  **sans prix**. WooCommerce n'affiche jamais de bouton « In den Warenkorb »
  pour un produit sans prix : cliquez sur chaque nom pour saisir le prix.
- **Produits → Importer** : dès que vous choisissez le fichier, le thème
  détecte le séparateur (`;` pour un fichier enregistré par Excel) et
  l'encodage Windows-1252, et les règle tout seul dans les options avancées.
- Les prix écrits « 189,00 », « 1 250,50 » ou « 1'250.50 » sont lus
  correctement (sans le thème, WooCommerce lit « 189,00 » comme 18 900).

## 15. Version 1.4.0 — un site vivant

- **Ajout au panier sans quitter la page** (boutique, fiche produit, aperçu
  rapide) : la photo du produit s'envole vers l'icône du panier, le bouton
  affiche « Hinzugefügt ✓ » et un message propose « Zum Warenkorb ».
  Pour rouvrir le panier latéral automatiquement : Personnaliser → Premium
  Shop → Boutique → « Open the side cart after adding a product ».
- **En-têtes de pages illustrés** avec les photos de VOS produits (pages,
  panier, caisse, compte, contact, liste d'envies), braises animées,
  pastilles de réassurance. Désactivable : Personnaliser → Général.
- **Suivi de commande** : camion de livraison animé. **Page Merci** :
  coche animée + confettis.
- **Accueil** : sans image choisie, collage flottant de vos produits.
- **Catégories sans image** : photo d'un de leurs produits automatiquement.
- **Pages « Über uns » et « Versand & Zahlung »** : si elles contenaient
  encore le texte d'exemple, elles sont remplies automatiquement
  (histoire, atouts, étapes de livraison, zones et modes d'expédition,
  moyens de paiement actifs). Codes courts réutilisables :
  `[ps_features]`, `[ps_delivery_steps]`, `[ps_shipping_info]`,
  `[ps_payment_methods]`, `[ps_photo_collage]`, `[ps_cta]`.

## 16. Version 1.5.0 — pages légales et pages traduisibles

- **Datenschutzerklärung** (politique de confidentialité) et
  **Nutzungsbedingungen** (CGU) créées automatiquement en DE (texte principal),
  avec FR / EN / ES pour le sélecteur de langue. Elles reprennent vos
  coordonnées (Personnaliser → Premium Shop → Contact) et apparaissent en bas
  de chaque page.
- La page de confidentialité par défaut de WordPress (brouillon) est remplacée ;
  une page de confidentialité que vous avez déjà publiée n'est jamais modifiée.
- **Toutes les pages sont traduisibles** : sous l'éditeur de page, boîte
  « Traductions » (titre + texte par langue).
- À faire vérifier par un juriste avant publication définitive (notamment le
  for juridique et les prestataires réellement utilisés).
