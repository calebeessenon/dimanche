# Monétisation — publicité AdMob et achats intégrés

## 1. État actuel

| Élément | État |
|---|---|
| Plugin | `google_mobile_ads` 9.2.0 (+ consentement UMP inclus) |
| Identifiants | **TEST officiels Google** partout (application + blocs d'annonces) |
| Récompensées | ✅ vie gratuite, +5 coups en fin de partie, +25 pièces, marteau gratuit |
| Interstitiels | ✅ après une victoire (avant le niveau suivant) et après une défaite abandonnée |
| Bannière | ✅ adaptative, en bas de la **carte** uniquement (jamais en partie) |
| Consentement | ✅ UMP (RGPD / ATT) avant toute requête, bouton « Confidentialité publicitaire » dans Paramètres |
| Achats intégrés | 🟡 architecture prête (`in_app_purchase` 3.3.1), **désactivés** |
| Web | pas de publicité (le plugin n'existe pas sur le web) |

## 2. Fichiers

```
lib/services/monetization.dart          Interfaces AdsService / PurchaseService (+ versions simulées)
lib/services/ads/ad_config.dart         Identifiants (test / production) + règles de fréquence
lib/services/ads/consent_manager.dart   Consentement UMP
lib/services/ads/ad_gateway.dart        Abstraction du SDK (testable)
lib/services/ads/google_ad_gateway.dart Implémentation google_mobile_ads + bannière adaptative
lib/services/ads/admob_ads_service.dart Service AdMob : règles, préchargement, verrou, récompenses
lib/services/iap/iap_config.dart        Activation, produits, contenu des packs
lib/services/iap/purchase_verifier.dart Vérification serveur (refus par défaut)
lib/services/iap/store_purchase_service.dart  Google Play Billing / StoreKit
lib/ui/widgets/rewarded_ad.dart         « Regarder une pub » côté écran (plafonds, messages)
android/app/build.gradle.kts            admobAppId (placeholder du manifeste)
android/gradle.properties               admobAppId de production (commenté)
ios/Flutter/AdMob.xcconfig              ADMOB_APP_ID (Info.plist → GADApplicationIdentifier)
test/ads_service_test.dart              16 tests des règles publicitaires
```

## 3. Garanties techniques

* **Consentement d'abord** : le SDK n'est initialisé et aucune annonce n'est
  demandée tant que `ConsentInformation.canRequestAds()` est faux.
* **Récompense validée uniquement** : `showRewarded` renvoie `true` seulement si
  le SDK a appelé `onUserEarnedReward`, et après la fermeture de l'annonce.
  Fermée trop tôt, non chargée ou en erreur → aucune récompense, message au joueur.
* **Pas de double affichage** : verrou unique pour toutes les annonces plein écran ;
  chaque annonce n'est affichée qu'une fois puis libérée.
* **Pas de fuite mémoire** : `dispose()` sur fermeture / échec ; annonces de plus de
  55 min jetées ; bannière libérée quand l'écran se ferme ; minuteries annulées.
* **Préchargement** + nouvel essai avec délai croissant (5 s → 2 min max).
* **Musique coupée** pendant les annonces plein écran.

## 4. Règles d'expérience joueur

| Règle | Valeur (`AdConfig`) |
|---|---|
| Pas d'interstitiel avant | 3 niveaux terminés |
| Fréquence | 1 fin de partie sur 2 au plus |
| Intervalle minimal | 120 s |
| Après une pub récompensée | 90 s sans interstitiel |
| Plafonds récompensées / jour | pièces 5, booster 2, vie 3 (`Economy.adDailyCaps`) |
| Achat « Sans pub » | supprime interstitiels et bannières, garde les récompensées (volontaires) |

## 5. Passer en production (le moment venu)

1. Console AdMob : créer l'application Android et l'application iOS, puis 3 blocs
   par plateforme (récompensé, interstitiel, bannière adaptative).
2. Identifiants d'**application** :
   * Android : `android/gradle.properties` → `admobAppId=ca-app-pub-…~…`
   * iOS : `ios/Flutter/AdMob.xcconfig` → `ADMOB_APP_ID = ca-app-pub-…~…`
3. Identifiants de **blocs** à la compilation :
   ```bash
   flutter build appbundle --release \
     --dart-define=ADS_MODE=production \
     --dart-define=ADMOB_ANDROID_REWARDED=ca-app-pub-…/… \
     --dart-define=ADMOB_ANDROID_INTERSTITIAL=ca-app-pub-…/… \
     --dart-define=ADMOB_ANDROID_BANNER=ca-app-pub-…/…
   # iOS : ADMOB_IOS_REWARDED / ADMOB_IOS_INTERSTITIAL / ADMOB_IOS_BANNER
   ```
   Si un identifiant manque, l'application retombe automatiquement sur les
   identifiants de test (jamais d'identifiant vide).
4. Pendant la recette avec de vrais identifiants : ajouter votre téléphone comme
   appareil de test (`--dart-define=ADMOB_TEST_DEVICES=<id affiché dans les logs>`)
   pour ne jamais cliquer sur de vraies annonces (risque de suspension du compte).
5. Fichier `app-ads.txt` sur le site web déclaré dans la fiche Play Store.

## 6. Consentement (à configurer dans AdMob)

AdMob → **Confidentialité et messages** :
* créer un message **RGPD** (UE / Royaume-Uni / Suisse) ; sans message publié, le
  formulaire ne s'affiche pas ;
* iOS : créer le message **IDFA / ATT** (texte d'explication déjà présent dans
  `Info.plist` : `NSUserTrackingUsageDescription`) ;
* optionnel : message pour les États américains.

Tester le formulaire hors UE : `--dart-define=UMP_DEBUG_EEA=true --dart-define=ADMOB_TEST_DEVICES=<id>`.

## 7. Achats intégrés (désactivés)

Produits prévus (mêmes identifiants sur Google Play et l'App Store) :

| ID | Type | Contenu | Prix indicatif |
|---|---|---|---|
| `starter_pack` | consommable | 1000 pièces + 3 marteaux + 3 mélanges + 2 « +5 coups » | 1,99 € |
| `coins_500` | consommable | 500 pièces | 0,99 € |
| `coins_1200` | consommable | 1200 pièces | 1,99 € |
| `coins_3000` | consommable | 3000 pièces | 4,99 € |
| `coins_8000` | consommable | 8000 pièces | 9,99 € |
| `no_ads` | non consommable | supprime interstitiels et bannières | 3,99 € |

Flux : achat → vérification **serveur** → livraison (idempotente : un achat déjà
livré ne l'est jamais deux fois) → `completePurchase`.

Pour activer :
1. Créer les produits dans Google Play Console (et App Store Connect).
2. Déployer la vérification serveur : Cloud Function Firebase `verifyPurchase`
   (Google Play Developer API / App Store Server API, compte de service) et
   remplacer `RejectAllVerifier` dans `lib/main.dart`. **Nécessite le plan Blaze**
   de Firebase (paiement à l'usage, gratuit à faible volume).
3. Compiler avec `--dart-define=IAP_ENABLED=true`.

Tant que ces étapes ne sont pas faites : la boutique affiche « Bientôt disponible »,
et même activés par erreur, les achats seraient refusés (aucune livraison sans
vérification).

## 8. Déclarations Google Play (rappel)

* **Contient des annonces : Oui.**
* **Audience cible** : déclarer **13 ans et plus** (ou 16+/18+). Inclure les moins
  de 13 ans soumet l'application au programme « Familles » (régies certifiées,
  traitement « enfant » obligatoire) : à décider avant la publication.
* **Sécurité des données** : identifiant publicitaire, données d'interaction et
  diagnostics collectés par le SDK Google Mobile Ads (partagés à des fins
  publicitaires) ; identifiant Firebase pour la sauvegarde.
