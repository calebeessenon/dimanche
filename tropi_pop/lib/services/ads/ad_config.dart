import 'package:flutter/foundation.dart' show TargetPlatform, defaultTargetPlatform, kReleaseMode;

/// Identifiants des blocs d'annonces AdMob.
///
/// Par défaut, ce sont les identifiants de TEST officiels de Google
/// (https://developers.google.com/admob/android/test-ads et
///  https://developers.google.com/admob/ios/test-ads) : ils affichent des
/// annonces marquées « Test Ad » et ne génèrent aucun revenu ni aucun risque
/// pour le compte AdMob.
///
/// Passage en production (sans modifier le code) :
/// ```
/// flutter build appbundle --release \
///   --dart-define=ADS_MODE=production \
///   --dart-define=ADMOB_ANDROID_REWARDED=ca-app-pub-XXXX/YYYY \
///   --dart-define=ADMOB_ANDROID_INTERSTITIAL=ca-app-pub-XXXX/YYYY \
///   --dart-define=ADMOB_ANDROID_BANNER=ca-app-pub-XXXX/YYYY \
///   (idem ADMOB_IOS_* pour iOS)
/// ```
/// + l'identifiant d'APPLICATION AdMob (voir docs/MONETIZATION.md) :
/// Android : `android/gradle.properties` → `admobAppId=` ;
/// iOS : `ios/Flutter/AdMob.xcconfig` → `ADMOB_APP_ID=`.
///
/// Sécurité : si le mode production est demandé mais qu'un identifiant
/// manque, on retombe sur les identifiants de test (jamais d'ID vide).
class AdUnitIds {
  final String rewarded;
  final String interstitial;
  final String banner;
  const AdUnitIds({required this.rewarded, required this.interstitial, required this.banner});

  // --- Identifiants de test officiels Google ---------------------------------
  static const testAndroid = AdUnitIds(
    rewarded: 'ca-app-pub-3940256099942544/5224354917',
    interstitial: 'ca-app-pub-3940256099942544/1033173712',
    banner: 'ca-app-pub-3940256099942544/9214589741', // bannière adaptative
  );
  static const testIos = AdUnitIds(
    rewarded: 'ca-app-pub-3940256099942544/1712485313',
    interstitial: 'ca-app-pub-3940256099942544/4411468910',
    banner: 'ca-app-pub-3940256099942544/2435281174', // bannière adaptative
  );

  // --- Production (injectée à la compilation) --------------------------------
  static const _prodAndroid = AdUnitIds(
    rewarded: String.fromEnvironment('ADMOB_ANDROID_REWARDED'),
    interstitial: String.fromEnvironment('ADMOB_ANDROID_INTERSTITIAL'),
    banner: String.fromEnvironment('ADMOB_ANDROID_BANNER'),
  );
  static const _prodIos = AdUnitIds(
    rewarded: String.fromEnvironment('ADMOB_IOS_REWARDED'),
    interstitial: String.fromEnvironment('ADMOB_IOS_INTERSTITIAL'),
    banner: String.fromEnvironment('ADMOB_IOS_BANNER'),
  );

  bool get complete => rewarded.isNotEmpty && interstitial.isNotEmpty && banner.isNotEmpty;
}

class AdConfig {
  AdConfig._();

  /// `production` uniquement si demandé explicitement.
  static const _mode = String.fromEnvironment('ADS_MODE', defaultValue: 'test');

  /// Bannières activées (désactivables via --dart-define=ADS_BANNERS=false).
  static const bannersEnabled = bool.fromEnvironment('ADS_BANNERS', defaultValue: true);

  /// Identifiants d'appareils de test (séparés par des virgules), pour voir des
  /// annonces de test même avec de vrais identifiants pendant la recette.
  static const testDeviceIds = String.fromEnvironment('ADMOB_TEST_DEVICES');

  static AdUnitIds forPlatform([TargetPlatform? platform]) {
    final p = platform ?? defaultTargetPlatform;
    final isIos = p == TargetPlatform.iOS;
    final prod = isIos ? AdUnitIds._prodIos : AdUnitIds._prodAndroid;
    if (_mode == 'production' && prod.complete) return prod;
    return isIos ? AdUnitIds.testIos : AdUnitIds.testAndroid;
  }

  static bool get usingTestIds {
    final ids = forPlatform();
    return ids == AdUnitIds.testAndroid || ids == AdUnitIds.testIos;
  }

  /// Avertissement à afficher dans les journaux si une version release part
  /// avec les identifiants de test.
  static bool get releaseWithTestIds => kReleaseMode && usingTestIds;

  // --- Règles de fréquence (expérience joueur) ---------------------------------

  /// Aucun interstitiel avant que le joueur ait terminé ce nombre de niveaux.
  static const interstitialMinLevelsCompleted = 3;

  /// Un interstitiel au plus toutes les N fins de partie…
  static const interstitialEveryNLevelEnds = 2;

  /// …et jamais deux à moins de cet intervalle.
  static const interstitialMinInterval = Duration(seconds: 120);

  /// Pas d'interstitiel juste après une pub récompensée regardée volontairement.
  static const interstitialAfterRewardedGrace = Duration(seconds: 90);

  /// Nouvelle tentative de chargement après un échec (backoff exponentiel, max).
  static const maxRetryDelay = Duration(minutes: 2);
}
