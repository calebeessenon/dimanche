import 'package:material_ui/material_ui.dart' show Widget;

/// Abstraction minimale du SDK publicitaire.
///
/// [AdMobAdsService] contient toutes les RÈGLES (consentement, fréquence,
/// anti-double affichage, validation des récompenses) et ne parle au SDK que
/// par cette interface. Cela permet de tester ces règles sans téléphone
/// (`test/ads_service_test.dart`) et, si besoin, de changer de régie.
abstract class AdGateway {
  Future<void> initialize({List<String> testDeviceIds = const []});

  void loadRewarded(
    String unitId, {
    required void Function(LoadedFullScreenAd ad) onLoaded,
    required void Function(String error) onFailed,
  });

  void loadInterstitial(
    String unitId, {
    required void Function(LoadedFullScreenAd ad) onLoaded,
    required void Function(String error) onFailed,
  });

  /// Widget bannière autonome (gère son propre cycle de vie).
  Widget banner(String unitId);
}

/// Une annonce plein écran chargée, affichable UNE seule fois.
abstract class LoadedFullScreenAd {
  /// Affiche l'annonce. Exactement un de [onDismissed] ou [onFailedToShow] sera
  /// appelé. [onUserEarnedReward] n'est appelé que pour une pub récompensée
  /// validée par la régie.
  void show({
    required void Function() onShown,
    required void Function() onDismissed,
    required void Function(String error) onFailedToShow,
    void Function()? onUserEarnedReward,
  });

  /// Libère les ressources natives. Idempotent.
  void dispose();
}
