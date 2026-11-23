import '../monetization.dart';

/// Configuration des achats intégrés.
///
/// DÉSACTIVÉS par défaut : la boutique affiche « Bientôt disponible ».
/// Activation (quand Google Play Console / App Store Connect sont prêts et que
/// la vérification serveur est déployée) :
/// `--dart-define=IAP_ENABLED=true`
abstract final class IapConfig {
  static const enabled = bool.fromEnvironment('IAP_ENABLED');

  /// Produits « consommables » (rachetables) vs « non consommables ».
  static const consumables = {
    Products.starterPack,
    Products.coins500,
    Products.coins1200,
    Products.coins3000,
    Products.coins8000,
  };
  static const nonConsumables = {Products.noAds};

  static bool isConsumable(String id) => consumables.contains(id);
}

/// Ce que contient chaque produit (livré par ProgressStore.deliverPurchase).
class ProductReward {
  final int coins;
  final Map<String, int> boosters;
  final bool removesAds;
  const ProductReward({this.coins = 0, this.boosters = const {}, this.removesAds = false});

  static const catalog = <String, ProductReward>{
    Products.starterPack: ProductReward(coins: 1000, boosters: {'hammer': 3, 'shuffle': 3, 'moves': 2}),
    Products.coins500: ProductReward(coins: 500),
    Products.coins1200: ProductReward(coins: 1200),
    Products.coins3000: ProductReward(coins: 3000),
    Products.coins8000: ProductReward(coins: 8000),
    Products.noAds: ProductReward(removesAds: true),
  };
}
