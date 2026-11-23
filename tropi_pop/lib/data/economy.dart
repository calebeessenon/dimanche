/// Paramètres de l'économie virtuelle (centralisés pour l'équilibrage et,
/// plus tard, le pilotage via Firebase Remote Config).
///
/// Principe : le jeu est entièrement jouable sans payer. Les pièces se gagnent
/// en jouant (victoires, étoiles, coffres, récompenses quotidiennes, pubs
/// récompensées) ; l'achat ne fait qu'accélérer.
abstract final class Economy {
  static const startCoins = 500;
  static const milestoneCoins = 100;

  /// Pièces gagnées en fin de niveau.
  static int coinsForWin(int stars, int previousStars, bool firstClear) {
    var c = firstClear ? 20 : 5;
    if (stars > previousStars) c += (stars - previousStars) * 10;
    return c;
  }

  static const extraMovesCost = 120; // +5 coups en fin de partie
  static const extraMoves = 5;
  static const adRewardCoins = 25;

  /// Plafonds quotidiens des récompenses publicitaires (préserve l'économie).
  static const adDailyCaps = {'coins': 5, 'booster': 2, 'life': 3};

  static const coinOffers = [
    CoinOffer('hammer', CoinOfferKind.booster, 150, 1),
    CoinOffer('shuffle', CoinOfferKind.booster, 100, 1),
    CoinOffer('moves', CoinOfferKind.booster, 120, 1),
    CoinOffer('hammer', CoinOfferKind.booster, 600, 5),
    CoinOffer('lives', CoinOfferKind.lives, 200, 5),
  ];

  /// Cycle de 7 jours.
  static const dailyRewards = [
    DailyReward(50),
    DailyReward(75),
    DailyReward(50, {'shuffle': 1}),
    DailyReward(100),
    DailyReward(75, {'hammer': 1}),
    DailyReward(150),
    DailyReward(300, {'hammer': 1, 'moves': 1}),
  ];
}

enum CoinOfferKind { booster, lives }

class CoinOffer {
  final String itemId;
  final CoinOfferKind kind;
  final int price;
  final int quantity;
  const CoinOffer(this.itemId, this.kind, this.price, this.quantity);
}

class DailyReward {
  final int coins;
  final Map<String, int> boosters;
  const DailyReward(this.coins, [this.boosters = const {}]);
}
