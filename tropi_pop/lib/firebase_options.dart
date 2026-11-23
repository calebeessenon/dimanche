// Configuration Firebase du projet « pitro-pop ».
//
// Équivalent du fichier généré par `flutterfire configure`, rempli à partir de
// android/app/google-services.json et ios/Runner/GoogleService-Info.plist.
// Ces valeurs identifient l'application auprès de Firebase ; ce ne sont pas
// des secrets (la sécurité repose sur les règles Firestore et sur la
// restriction des clés API dans la console Google Cloud).
import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  DefaultFirebaseOptions._();

  /// Options de la plateforme courante, ou `null` si Firebase n'est pas
  /// configuré pour elle (web, bureau) : le jeu fonctionne alors hors ligne.
  static FirebaseOptions? get currentPlatform {
    if (kIsWeb) return null;
    return switch (defaultTargetPlatform) {
      TargetPlatform.android => android,
      TargetPlatform.iOS => ios,
      _ => null,
    };
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyBlCXsThnzAILzKvqwE2epIgEYaESPRZGc',
    appId: '1:998074961132:android:eae9e105a1de833c8669c9',
    messagingSenderId: '998074961132',
    projectId: 'pitro-pop',
    storageBucket: 'pitro-pop.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyBIK1GD8ClUo9Fdq04ITXGzFdTCgVCbBXQ',
    appId: '1:998074961132:ios:39a1102fcb33ef198669c9',
    messagingSenderId: '998074961132',
    projectId: 'pitro-pop',
    storageBucket: 'pitro-pop.firebasestorage.app',
    iosBundleId: 'com.pitropop.pitropop',
  );
}
