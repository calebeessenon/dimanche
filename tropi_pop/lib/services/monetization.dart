import 'dart:async';

/// Interfaces de monétisation.
///
/// L'interface du jeu ne dépend QUE de ces abstractions. L'étape 6 fournit
/// les implémentations réelles (Google AdMob, Google Play Billing / StoreKit
/// via `in_app_purchase`) sans modifier les écrans.

/// Publicités.
abstract class AdsService {
  /// Prépare les SDK (consentement RGPD/UMP compris à l'étape 6).
  Future<void> init();

  /// Publicité récompensée. Retourne `true` si la récompense est acquise.
  Future<bool> showRewarded(String placement);

  /// Interstitiel (respecte un plafond de fréquence).
  Future<void> maybeShowInterstitial(String placement);

  bool get rewardedAvailable;

  /// Désactive interstitiels et bannières (achat "Sans pub").
  set adsRemoved(bool v);
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

class SimulatedAdsService implements AdsService {
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
    // Plafond : au plus une fois toutes les 3 fins de niveau.
    if (++_interstitialCounter % 3 != 0) return;
    await presenter?.call();
  }

  @override
  set adsRemoved(bool v) => _removed = v;
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
