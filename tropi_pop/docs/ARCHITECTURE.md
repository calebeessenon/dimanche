# Tropi Pop — Architecture technique

Jeu mobile Match-3 original (Android / iOS), Flutter + Dart.

## 1. Choix techniques

| Besoin | Choix | Pourquoi |
|---|---|---|
| Multiplateforme | **Flutter 3.50 / Dart 3.14** | Un seul code pour Android et iOS, rendu 60 i/s natif. |
| Moteur de jeu | **Moteur Dart pur** + `CustomPainter` + `Ticker` | Un Match-3 est une grille : pas besoin de physique ni de scène. Flame ajouterait ~1 Mo et une couche sans gain. Le moteur sans dépendance Flutter est testable unitairement, simulable par un bot et rejouable côté serveur (anti-triche). |
| Graphismes | Dessin **vectoriel** (code) mis en cache (`ui.Picture`) | 100 % original, net sur tous les écrans, APK léger. |
| Audio | `audioplayers` + sons **synthétisés** (`tool/generate_audio.py`) | Aucun droit tiers. |
| Police | Fredoka (SIL OFL, usage commercial libre) | Ronde et lisible. |
| Sauvegarde locale | `shared_preferences` + signature HMAC | Hors connexion, détecte les modifications manuelles. |
| Cloud (étape 5) | Firebase Auth (anonyme → Google / Apple) + Firestore + Cloud Functions | Restauration sur nouveau téléphone, classements, validation serveur. |
| Publicité (étape 6) | Google AdMob (récompensées, interstitiels plafonnés) + UMP (RGPD) | Standard du marché. |
| Achats (étape 6) | `in_app_purchase` (Google Play Billing + StoreKit) + vérification serveur | Un seul code pour les deux stores. |
| État | `ChangeNotifier` + `InheritedWidget` | Aucune dépendance, suffisant pour ce périmètre. |

## 2. Organisation des dossiers

```
tropi_pop/
├── lib/
│   ├── main.dart                  Point d'entrée : services, langue, orientation
│   ├── app.dart                   MaterialApp, cycle de vie, injection des services
│   ├── core/
│   │   ├── l10n/strings.dart      Textes FR / EN
│   │   └── theme/app_theme.dart   Couleurs, typographie
│   ├── game/
│   │   ├── engine/                ★ MOTEUR (Dart pur, sans Flutter)
│   │   │   ├── models.dart          Tile, Cell, Pos, couleurs, bonus
│   │   │   ├── board.dart           Grille
│   │   │   └── game_engine.dart     Règles : permutation, alignements, bonus,
│   │   │                            combos, cascades, gravité, mélange, objectifs
│   │   ├── levels/
│   │   │   ├── level_definition.dart  Format de niveau (JSON)
│   │   │   ├── level_catalog.dart     100 niveaux générés de façon déterministe
│   │   │   └── level_tuning.dart      Calibrage (GÉNÉRÉ par tool/tune_levels.dart)
│   │   ├── controller/game_controller.dart  Orchestration moteur ↔ animation ↔ audio
│   │   ├── render/
│   │   │   ├── tile_art.dart        Dessin des fruits et bonus
│   │   │   ├── board_animator.dart  Sprites, chutes, particules, effets
│   │   │   └── board_painter.dart   Rendu du plateau
│   │   └── widgets/board_view.dart  Widget plateau + gestes
│   ├── data/
│   │   ├── player_data.dart       Progression sérialisable
│   │   ├── progress_store.dart    Vies, pièces, boosters, niveaux, récompense du jour
│   │   └── economy.dart           Paramètres de l'économie virtuelle
│   ├── firebase_options.dart      Configuration Firebase (pitro-pop)
│   ├── services/
│   │   ├── app_services.dart      Accès aux services
│   │   ├── cloud_sync_service.dart Sauvegarde cloud + scores (Firestore)
│   │   ├── audio_service.dart
│   │   ├── monetization.dart      Interfaces Ads / Achats (+ versions simulées)
│   │   ├── ads/                   AdMob : config, consentement UMP, service, bannière
│   │   └── iap/                   Achats intégrés (prêts, désactivés)
│   └── ui/
│       ├── screens/               splash, accueil, carte, jeu, boutique, paramètres…
│       └── widgets/               logo, boutons, barre du haut, objectifs
├── assets/  audio/ (sons générés)  fonts/ (Fredoka + licence OFL)
├── branding/  icon.svg (source de l'icône) + rendu 1024 px
├── store/     visuels pour les stores
├── test/      moteur, sauvegarde, parcours d'interface
├── tool/      bot.dart, balance.dart, tune_levels.dart, generate_audio.py
└── docs/      ce document, feuille de route
```

## 3. Flux d'un coup

```
geste (BoardView) → GameController.trySwap
  → GameEngine.swap            valide ? sinon animation de retour
  → boucle :
      GameEngine.resolveStep   alignements → bonus créés/déclenchés → score
      BoardAnimator.showStep   explosions, particules, textes
      GameEngine.applyGravity  chute + nouveaux fruits
      BoardAnimator.sync       animation de chute
  → shuffleIfNeeded            plus aucun coup → mélange
  → statut : victoire (finale : coups restants → bonus) / défaite / continuer
```

## 4. Règles du moteur

* Grille 8×8 (8×9 et formes à trous possibles), 4 à 6 couleurs selon le niveau.
* Alignement de 3 : élimination. 4 en ligne : **bonus ligne** (horizontal/vertical).
  L ou T : **bombe** 3×3. 5 en ligne : **soleil prisme** (supprime une couleur).
* Combos (permutation de deux bonus) : ligne+ligne = croix ; ligne+bombe = triple
  croix ; bombe+bombe = 5×5 ; prisme+fruit = toute la couleur ; prisme+ligne/bombe =
  la couleur devient des bonus ; prisme+prisme = tout le plateau.
* Obstacles : **glace** (1 ou 2 couches sous les fruits), **caisses** (1 ou 2 PV,
  endommagées par les alignements adjacents et les explosions).
* Score : 30 pts × multiplicateur de cascade, bonus de création, glace, caisses.
* Fin : victoire dès que les objectifs sont atteints → coups restants convertis en
  bonus (finale) ; défaite à 0 coup (proposition de +5 coups).

## 5. Niveaux et équilibrage

Les niveaux sont **déclaratifs** (`LevelDefinition`, sérialisable JSON) :
grille, coups, couleurs, objectifs (score, collecte, glace, caisses), seuils d'étoiles.

1. `LevelCatalog` génère 100 niveaux déterministes (formes, obstacles, objectifs,
   courbe de difficulté, niveau « boss » tous les 10, niveau « respiration » après).
2. `tool/tune_levels.dart` fait jouer un **bot** (16 parties par essai) et cherche
   par dichotomie le nombre de coups qui donne le taux de réussite visé
   (97 % au début → ~60 % au niveau 100), puis fixe les seuils d'étoiles.
3. Le résultat est écrit dans `level_tuning.dart`.

Ajouter des niveaux : `LevelCatalog.register(LevelDefinition.fromJson(...))`
(fichier d'assets ou Remote Config) et augmenter `LevelCatalog.count`.

## 6. Sécurité

* Sauvegarde locale signée HMAC (clé surchargée en production par
  `--dart-define=SAVE_HMAC_KEY=...`) : modification détectée → pièces plafonnées.
* Étape 5 : Firestore avec règles de sécurité (chaque joueur ne lit/écrit que son
  document), Cloud Function de validation des scores de classement (le moteur étant
  déterministe et sans dépendance, il peut être rejoué côté serveur).
* Étape 6 : achats vérifiés côté serveur (Google Play Developer API / App Store
  Server API) avant livraison ; jamais de confiance dans le client.
* Aucune donnée personnelle collectée en local hormis la progression de jeu.

## 7. Commandes

```bash
flutter pub get
flutter test                         # 51 tests : moteur, sauvegarde, cloud, publicité, interface
flutter analyze
flutter run                          # sur un téléphone / émulateur
flutter run -d chrome                # aperçu navigateur
dart run tool/balance.dart 1 100 8   # rapport de difficulté par niveau
dart run tool/tune_levels.dart 16    # recalibrer les 100 niveaux
python3 tool/generate_audio.py       # régénérer les sons
```
