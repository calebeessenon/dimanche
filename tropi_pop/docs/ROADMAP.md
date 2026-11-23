# Plan de développement

| Étape | Contenu | État |
|---|---|---|
| 1 | Analyse, architecture, organisation des dossiers | ✅ fait (`docs/ARCHITECTURE.md`) |
| 2 | Identité visuelle (nom, logo, icône, fruits, palette, police, sons, musique) et interface (accueil, carte, jeu, boutique, paramètres, profil, récompense du jour) | ✅ fait |
| 3 | Moteur Match-3 (permutation, alignements, bonus, combos, cascades, gravité, mélange, indice) | ✅ fait, 18 tests |
| 4 | 100 niveaux calibrés par bot, objectifs, obstacles, étoiles, vies, pièces, boosters, coffres d'étape | ✅ fait |
| 5 | Firebase : compte anonyme, sauvegarde cloud, scores et classement (données + règles) | ✅ intégré (`docs/FIREBASE.md`) — activer Auth anonyme + Firestore dans la console |
| 5 bis | Liaison Google/Apple, écran de classement, défis journaliers, événements (Remote Config), notifications | à faire |
| 6 | AdMob (récompensées, interstitiels plafonnés, bannière, consentement UMP) | ✅ intégré avec identifiants de TEST (`docs/MONETIZATION.md`) |
| 6 bis | Achats intégrés : produits Play Console, vérification serveur, activation | 🟡 architecture prête, désactivée |
| 7 | Tests sur appareils réels, profilage, corrections | à faire |
| 8 | Signature, APK de test, AAB Google Play, build iOS | à faire — nécessite le SDK Android (et un Mac pour iOS) |
| 9 | Fiches store, captures, politique de confidentialité, Data safety, classification | à faire |

## Ce qui vous sera demandé

* **Production publicitaire** : compte AdMob (identifiants d'app et de blocs), messages de consentement.
* **Achats** : compte Google Play Console (25 $ une fois), plan Firebase Blaze pour la vérification ;
  compte Apple Developer (99 $/an) pour iOS.
* **Étape 8** : la machine de build doit avoir le SDK Android ; un Mac avec Xcode
  pour iOS.
