# Compiler et tester PITRO POP (Tropi Pop) sur Windows 11

Identifiant Android : **com.pitropop.pitropop** · version : `1.0.0+1` (pubspec.yaml)

## 1. Logiciels à installer (une seule fois)

| Logiciel | Version | Rôle |
|---|---|---|
| **Git for Windows** | récente | récupérer le projet |
| **Flutter SDK** | **3.50.x stable** (Dart 3.14) | compiler l'application |
| **Android Studio** | récent (inclut le JDK 21 « JBR ») | SDK Android, outils |
| Android SDK Platform | **36** (Android 16) | `compileSdk` / `targetSdk` |
| Android SDK Build-Tools | 36.x | |
| Android SDK Platform-Tools | récent | `adb` (connexion au téléphone) |
| Android SDK Command-line Tools | récent | acceptation des licences |
| Pilote USB | Google USB Driver (Pixel) ou celui du fabricant (Samsung, Xiaomi…) | |

Le reste (Gradle 9.5, plugin Android 9.3.1, Kotlin 2.4.20, plugin Google
Services 4.4.2, bibliothèques Firebase / AdMob / Play Billing) est téléchargé
automatiquement à la première compilation (connexion Internet nécessaire).

Pas besoin de NDK, d'Xcode ni de Visual Studio pour Android.

### Étapes d'installation

1. Flutter : télécharger le zip « stable » sur https://docs.flutter.dev/get-started/install/windows ,
   l'extraire dans `C:\src\flutter` (**pas** dans `C:\Program Files`), puis ajouter
   `C:\src\flutter\bin` à la variable d'environnement `Path`.
2. Android Studio : installer, ouvrir **More Actions → SDK Manager** :
   * onglet *SDK Platforms* : cocher **Android 16 (API 36)** ;
   * onglet *SDK Tools* : cocher **Android SDK Build-Tools**, **Android SDK
     Command-line Tools (latest)**, **Android SDK Platform-Tools**, **Google USB Driver**.
3. Android Studio → *Plugins* : installer **Flutter** (et Dart).
4. Dans **PowerShell** :

```powershell
flutter --version                 # doit afficher 3.50.x
flutter config --android-studio-dir "C:\Program Files\Android\Android Studio"
flutter doctor --android-licenses # répondre y à tout
flutter doctor -v                 # « Android toolchain » doit être ✓
git config --global core.longpaths true   # évite les erreurs de chemins trop longs
```

## 2. Récupérer le projet

```powershell
cd C:\src
git clone https://github.com/calebeessenon/dimanche.git
cd dimanche
git checkout ccr-bab210b9-ph37ry
cd tropi_pop
flutter pub get
flutter test          # 51 tests doivent passer
flutter analyze       # « No issues found! »
```

> Conseil : gardez le projet dans un chemin court, sans espaces ni accents
> (ex. `C:\src\dimanche`). Évitez les dossiers synchronisés OneDrive.

## 3. Préparer le téléphone Android

1. *Paramètres → À propos du téléphone* → toucher 7 fois **Numéro de build**.
2. *Paramètres → Options pour les développeurs* → activer **Débogage USB**.
3. Brancher le câble USB, accepter « Autoriser le débogage USB » sur le téléphone.
4. Vérifier :

```powershell
flutter devices       # le téléphone doit apparaître
adb devices           # (si adb est dans le Path) statut « device »
```

Android 7.0 (API 24) minimum.

## 4. Lancer le jeu sur le téléphone

```powershell
cd C:\src\dimanche\tropi_pop
flutter run                       # mode debug (rechargement à chaud : touche r)
flutter run --release             # performances réelles (pas de débogage)
```

Si plusieurs appareils : `flutter run -d <id affiché par flutter devices>`.

### À vérifier sur le téléphone (recette)

- [ ] Écran de démarrage violet + logo, puis accueil
- [ ] En Europe : le formulaire de consentement Google s'affiche
      (seulement si un message RGPD est publié dans AdMob, sinon rien — normal)
- [ ] Jouer 2-3 niveaux : glisser, cascades, bonus, sons, musique
- [ ] Carte : une **bannière « Test Ad »** apparaît en bas
- [ ] Boutique → « +25 pièces » : une vidéo **« Test Ad »** ; pièces créditées
      seulement si on la regarde jusqu'au bout
- [ ] Après la 2e/4e victoire (à partir de 3 niveaux terminés) : interstitiel « Test Ad »
- [ ] Paramètres → *Sauvegarde cloud* : « Synchronisée » + « Joueur XXXXX »
- [ ] Console Firebase → *Authentication* : un utilisateur anonyme apparaît ;
      *Firestore* : documents `players/…` et `leaderboard/…`
- [ ] Mode avion : le jeu reste jouable ; la sauvegarde repart au retour du réseau

## 5. APK de test

```powershell
flutter build apk --release
# → build\app\outputs\flutter-apk\app-release.apk
adb install -r build\app\outputs\flutter-apk\app-release.apk
```

Tant que la clé de signature (§ 6) n'est pas créée, cet APK est signé avec la
clé de **debug** : parfait pour tester ou partager à des testeurs, **refusé**
par Google Play.

APK plus léger pour un téléphone précis : `flutter build apk --release --split-per-abi`
(→ `app-arm64-v8a-release.apk` pour la plupart des téléphones récents).

## 6. Clé de signature (pour Google Play) — une seule fois

**La clé est irremplaçable : sauvegardez le fichier .jks et les mots de passe
hors de l'ordinateur (clé USB, coffre de mots de passe). Ne la mettez jamais sur GitHub.**

```powershell
# keytool est fourni avec Android Studio :
& "C:\Program Files\Android\Android Studio\jbr\bin\keytool.exe" -genkeypair -v `
  -keystore C:\src\keys\pitropop-upload.jks `
  -storetype JKS -keyalg RSA -keysize 2048 -validity 10000 `
  -alias upload
```

Créer ensuite `tropi_pop\android\key.properties` (déjà ignoré par Git) :

```properties
storePassword=VOTRE_MOT_DE_PASSE
keyPassword=VOTRE_MOT_DE_PASSE
keyAlias=upload
storeFile=C:/src/keys/pitropop-upload.jks
```

(barres obliques `/` dans le chemin.) `build.gradle.kts` détecte ce fichier
automatiquement et signe les versions release avec cette clé.

Empreinte SHA-1 / SHA-256 de la clé (à déclarer dans Firebase → Paramètres du
projet → application Android, utile pour la connexion Google plus tard) :

```powershell
& "C:\Program Files\Android\Android Studio\jbr\bin\keytool.exe" -list -v `
  -keystore C:\src\keys\pitropop-upload.jks -alias upload
```

## 7. AAB pour Google Play

```powershell
flutter build appbundle --release
# → build\app\outputs\bundle\release\app-release.aab
```

À chaque nouvelle version envoyée sur Google Play, augmenter le numéro dans
`pubspec.yaml` : `version: 1.0.1+2` (le nombre après `+` doit toujours augmenter).

Rappel : les identifiants AdMob restent ceux de **TEST** et les achats intégrés
restent **désactivés** tant que les options de production ne sont pas ajoutées
(voir `docs/MONETIZATION.md`). Un AAB de test interne avec annonces de test est
acceptable ; pour la production publique, passer d'abord AdMob en production.

## 8. Problèmes fréquents

| Message | Solution |
|---|---|
| `Android toolchain … cmdline-tools component is missing` | SDK Manager → cocher *Android SDK Command-line Tools* |
| `Android license status unknown` | `flutter doctor --android-licenses` |
| `Unsupported class file major version` / erreur JDK | `flutter config --jdk-dir "C:\Program Files\Android\Android Studio\jbr"` |
| `Filename longer than 260 characters` | `git config --global core.longpaths true` + activer les chemins longs Windows, ou projet dans `C:\src` |
| Téléphone non détecté | autre câble (données), pilote USB, réaccepter l'empreinte RSA, `adb kill-server` puis `adb devices` |
| `INSTALL_FAILED_UPDATE_INCOMPATIBLE` | désinstaller l'ancienne version (signée avec une autre clé) : `adb uninstall com.pitropop.pitropop` |
| `File google-services.json is missing` | le fichier doit être dans `tropi_pop\android\app\` |
| Pas de bannière / vidéo | normal les premières secondes ; vérifier Internet ; les annonces de test peuvent parfois manquer (« no fill ») |
| Sauvegarde cloud « Indisponible » | vérifier Authentication anonyme et base Firestore créées (docs/FIREBASE.md) |
| Première compilation très longue | normal (10-20 min) : téléchargement de Gradle et des bibliothèques |
