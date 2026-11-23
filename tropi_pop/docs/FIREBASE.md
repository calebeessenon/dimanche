# Firebase — projet `pitro-pop`

## Ce qui est intégré

| Élément | Emplacement |
|---|---|
| Config Android | `android/app/google-services.json` + plugin `com.google.gms.google-services` (`android/settings.gradle.kts`, `android/app/build.gradle.kts`) |
| Config iOS | `ios/Runner/GoogleService-Info.plist` (ajouté au projet Xcode, phase « Resources ») |
| Identifiants | Android `applicationId` / `namespace` = `com.pitropop.pitropop` ; iOS `PRODUCT_BUNDLE_IDENTIFIER` = `com.pitropop.pitropop` |
| Options Dart | `lib/firebase_options.dart` (équivalent `flutterfire configure`) |
| Paquets | `firebase_core`, `firebase_auth`, `cloud_firestore` |
| Synchronisation | `lib/services/cloud_sync_service.dart`, fusion `lib/data/progress_merge.dart` |
| Règles de sécurité | `firestore.rules`, `firebase.json`, `.firebaserc` |
| Tests | `test/cloud_sync_test.dart` (Flutter) · `firebase_tests/` (règles, émulateur) |

Android `minSdk` est porté à 23 minimum (exigence Firebase). iOS : 15.0.

## Données enregistrées

```
players/{uid}                 progression complète (privée, lisible par son seul propriétaire)
leaderboard/{uid}             pseudonyme « Joueur XXXXX », étoiles, niveaux, score cumulé
levels/{niveau}/scores/{uid}  meilleur score du joueur sur le niveau (ne peut que monter)
```

* Compte **anonyme** créé automatiquement : aucune donnée personnelle (ni e-mail, ni nom).
* Fusion local/cloud : niveaux, étoiles, meilleurs scores = maximum ; pièces et
  boosters = sauvegarde la plus récente (empêche la duplication) ; réglages = appareil.
* Hors connexion : Firestore met les écritures en file d'attente et les envoie au retour du réseau.
* Envoi automatique 4 s après chaque changement, au retour dans l'application, et
  bouton « Synchroniser » dans Paramètres.
* Sur le web, Firebase n'est pas configuré : le jeu reste en mode hors ligne.

## À faire dans la console Firebase (5 minutes)

1. **Authentication** → *Commencer* → onglet *Mode de connexion* → activer **Anonyme**.
2. **Firestore Database** → *Créer une base de données* → mode **production** →
   région **eur3 (Europe)** (ou la plus proche de vos joueurs).
3. Publier les règles : copier le contenu de `firestore.rules` dans
   *Firestore → Règles → Publier*, ou en ligne de commande :
   ```bash
   npm install -g firebase-tools
   firebase login
   firebase deploy --only firestore:rules
   ```
4. Recommandé : Google Cloud Console → *API et services → Identifiants* →
   restreindre chaque clé API à son application (empreinte SHA-1 Android /
   bundle ID iOS) et aux API Firebase utilisées.

## Changement de téléphone

Le compte anonyme est lié à l'installation. Pour retrouver sa progression sur un
nouveau téléphone, le joueur liera son compte à **Google** (Android) ou **Apple**
(iOS) : `linkWithCredential` conserve l'uid, donc la sauvegarde. Prérequis :
empreinte SHA-1 de la clé de signature (étape 8) déclarée dans Firebase, puis
fournisseurs Google / Apple activés dans Authentication.

## Vérifications

```bash
flutter test                                  # dont 7 tests de synchronisation
cd firebase_tests && npm install && npm test  # 7 tests des règles sur l'émulateur
```
