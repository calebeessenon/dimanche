import 'dart:async';

import 'package:material_ui/material_ui.dart';

/// Interfaces de monétisation.
///
/// L'interface du jeu ne dépend QUE de ces abstractions. L'étape 6 fournit
/// les implémentations réelles (Google AdMob, Google Play Billing / StoreKit
/// via `in_app_purchase`) sans modifier les écrans.

/// Publicités.
///
/// Implémentations : [AdMobAdsService] (Android / iOS, `ads/admob_ads_service.dart`)
/// et [SimulatedAdsService] (web, tests). Les écrans n'utilisent que cette
/// interface. Notifie ses écouteurs quand la disponibilité change.
abstract class AdsService extends ChangeNotifier {
  /// Consentement (UMP) puis initialisation du SDK.
  Future<void> init();

  /// Publicité récompensée. Retourne `true` UNIQUEMENT si la régie a confirmé
  /// la récompense (vidéo regardée jusqu'au bout) ; `false` sinon (pas
  /// d'annonce prête, fermée trop tôt, erreur, affichage déjà en cours).
  Future<bool> showRewarded(String placement);

  /// Interstitiel à une transition naturelle (fin de partie). Respecte les
  /// plafonds de fréquence ; ne fait rien si les pubs sont supprimées.
  Future<void> maybeShowInterstitial(String placement);

  /// Une publicité récompensée est-elle prête à être affichée ?
  bool get rewardedAvailable;

  /// Bannière pour un emplacement non intrusif (widget vide si désactivée).
  Widget banner(String placement);

  /// Désactive interstitiels et bannières (achat « Sans pub »).
  /// Les pubs récompensées restent proposées : elles sont volontaires.
  set adsRemoved(bool v);

  /// Faut-il proposer « Options de confidentialité publicitaire » ?
  Future<bool> privacyOptionsRequired();
  Future<void> showPrivacyOptions();
}

/// Produits intégrés (identifiants identiques sur Google Play et App Store).
abstract final class Products {
  static const coins500 = 'coins_500';
  static const coins1200 = 'coins_1200';
  static const coins3000 = 'coins_3000';
  static const coins8000 = 'coins_8000';
  static const starterPack = 'starter_pack';
  static const noAds = 'no_ads';

  static const coinAmounts = {coins500: 500, coins1200: 1200, coins3000: 3000, coins8000: 8000};
  static const all = [starterPack, coins500, coins1200, coins3000, coins8000, noAds];

  /// Prix indicatifs (affichés si la boutique n'est pas joignable).
  static const fallbackPrices = {
    starterPack: '1,99 €',
    coins500: '0,99 €',
    coins1200: '1,99 €',
    coins3000: '4,99 €',
    coins8000: '9,99 €',
    noAds: '3,99 €',
  };
}

class ProductInfo {
  final String id;
  final String price;
  const ProductInfo(this.id, this.price);
}

/// Achats intégrés.
abstract class PurchaseService {
  Future<void> init();
  bool get available;
  List<ProductInfo> get products;

  /// Lance l'achat ; la livraison passe par [onDelivered] une fois l'achat
  /// vérifié (côté serveur à l'étape 6).
  Future<void> buy(String productId);
  Future<void> restore();

  set onDelivered(void Function(String productId) cb);
}

// -----------------------------------------------------------------------------
// Implémentations provisoires (étapes 1-4) : aucune vraie publicité, aucun
// paiement. En mode debug, la pub récompensée est simulée pour tester le flux.
// -----------------------------------------------------------------------------

class SimulatedAdsService extends AdsService {
  /// Fourni par l'interface pour afficher une fausse publicité de test.
  Future<void> Function()? presenter;
  final bool enabled;
  bool _removed = false;
  int _interstitialCounter = 0;

  SimulatedAdsService({this.enabled = true});

  @override
  Future<void> init() async {}

  @override
  bool get rewardedAvailable => enabled;

  @override
  Future<bool> showRewarded(String placement) async {
    if (!enabled) return false;
    await presenter?.call();
    return true;
  }

  @override
  Future<void> maybeShowInterstitial(String placement) async {
    if (_removed || !enabled) return;
    if (++_interstitialCounter % 3 != 0) return;
    await presenter?.call();
  }

  @override
  Widget banner(String placement) => const SizedBox.shrink();

  @override
  set adsRemoved(bool v) => _removed = v;

  @override
  Future<bool> privacyOptionsRequired() async => false;

  @override
  Future<void> showPrivacyOptions() async {}
}

class UnavailablePurchaseService implements PurchaseService {
  @override
  Future<void> init() async {}
  @override
  bool get available => false;
  @override
  List<ProductInfo> get products => [for (final id in Products.all) ProductInfo(id, Products.fallbackPrices[id]!)];
  @override
  Future<void> buy(String productId) async {}
  @override
  Future<void> restore() async {}
  @override
  set onDelivered(void Function(String productId) cb) {}
}
