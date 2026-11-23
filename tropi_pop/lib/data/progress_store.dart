import 'dart:convert';

import 'package:crypto/crypto.dart';
import 'package:material_ui/material_ui.dart' show ChangeNotifier;
import 'package:shared_preferences/shared_preferences.dart';

import '../services/iap/iap_config.dart';
import 'economy.dart';
import 'player_data.dart';

/// Stockage clé/valeur (abstrait pour les tests).
abstract class KeyValueStore {
  String? read(String key);
  Future<void> write(String key, String value);
}

class PrefsStore implements KeyValueStore {
  final SharedPreferences prefs;
  PrefsStore(this.prefs);
  @override
  String? read(String key) => prefs.getString(key);
  @override
  Future<void> write(String key, String value) => prefs.setString(key, value);
}

class MemoryStore implements KeyValueStore {
  final Map<String, String> data = {};
  @override
  String? read(String key) => data[key];
  @override
  Future<void> write(String key, String value) async => data[key] = value;
}

/// Récompense obtenue en fin de niveau.
class LevelReward {
  final int coins;
  final bool firstClear;
  final bool milestone;
  final bool newBest;
  const LevelReward(this.coins, {this.firstClear = false, this.milestone = false, this.newBest = false});
}

/// Source unique de vérité de la progression du joueur.
///
/// Sécurité : la sauvegarde locale est signée (HMAC-SHA256) pour détecter les
/// modifications manuelles naïves. Ce n'est PAS une protection absolue : la
/// référence fait foi côté serveur (Firestore + règles de sécurité + validation
/// des achats), mise en place aux étapes 5 et 6.
class ProgressStore extends ChangeNotifier {
  static const _key = 'player_v1';
  static const _sigKey = 'player_v1_sig';
  static const _hmacKey = 'tp-local-integrity-v1'; // à surcharger via --dart-define en production

  final KeyValueStore _store;
  final DateTime Function() clock;
  late PlayerData data;
  bool tampered = false;

  ProgressStore(this._store, {DateTime Function()? clock}) : clock = clock ?? DateTime.now {
    _load();
  }

  static Future<ProgressStore> open() async => ProgressStore(PrefsStore(await SharedPreferences.getInstance()));

  String _sign(String json) {
    const secret = String.fromEnvironment('SAVE_HMAC_KEY', defaultValue: _hmacKey);
    return Hmac(sha256, utf8.encode(secret)).convert(utf8.encode(json)).toString();
  }

  void _load() {
    final raw = _store.read(_key);
    if (raw == null) {
      data = PlayerData(lastLifeAtMs: clock().millisecondsSinceEpoch);
      return;
    }
    try {
      data = PlayerData.fromJson(jsonDecode(raw) as Map<String, dynamic>);
      if (_store.read(_sigKey) != _sign(raw)) {
        // Sauvegarde modifiée : on neutralise la monnaie et les achats non vérifiés.
        tampered = true;
        data
          ..coins = data.coins.clamp(0, 500)
          ..noAds = false;
      }
    } catch (_) {
      data = PlayerData(lastLifeAtMs: clock().millisecondsSinceEpoch);
    }
  }

  Future<void> save() async {
    data.updatedAtMs = clock().millisecondsSinceEpoch;
    final json = jsonEncode(data.toJson());
    await _store.write(_key, json);
    await _store.write(_sigKey, _sign(json));
    notifyListeners();
  }

  /// Remplace toutes les données (restauration cloud).
  Future<void> replaceWith(PlayerData d) async {
    data = d;
    await save();
  }

  // --- Vies ---------------------------------------------------------------

  int get lives => data.livesAt(clock());
  Duration? get nextLifeIn => data.nextLifeIn(clock());

  /// Consomme une vie au lancement d'un niveau (rendue en cas de victoire).
  Future<bool> consumeLife() async {
    data.settleLives(clock());
    if (data.livesStored <= 0) return false;
    if (data.livesStored == PlayerData.maxLives) data.lastLifeAtMs = clock().millisecondsSinceEpoch;
    data.livesStored--;
    data.gamesPlayed++;
    await save();
    return true;
  }

  Future<void> addLives(int n) async {
    data.settleLives(clock());
    data.livesStored = (data.livesStored + n).clamp(0, PlayerData.maxLives);
    await save();
  }

  // --- Pièces et boosters ---------------------------------------------------

  int get coins => data.coins;

  Future<bool> spendCoins(int amount) async {
    if (amount < 0 || data.coins < amount) return false;
    data.coins -= amount;
    await save();
    return true;
  }

  Future<void> addCoins(int amount) async {
    data.coins += amount;
    await save();
  }

  int booster(String id) => data.boosters[id] ?? 0;

  Future<bool> useBooster(String id) async {
    if (booster(id) <= 0) return false;
    data.boosters[id] = booster(id) - 1;
    await save();
    return true;
  }

  Future<void> addBooster(String id, int n) async {
    data.boosters[id] = booster(id) + n;
    await save();
  }

  /// Achète un article de la boutique avec des pièces.
  Future<bool> buyWithCoins(CoinOffer offer) async {
    if (data.coins < offer.price) return false;
    data.coins -= offer.price;
    switch (offer.kind) {
      case CoinOfferKind.booster:
        data.boosters[offer.itemId] = booster(offer.itemId) + offer.quantity;
      case CoinOfferKind.lives:
        data.settleLives(clock());
        data.livesStored = PlayerData.maxLives;
    }
    await save();
    return true;
  }

  // --- Niveaux ---------------------------------------------------------------

  int starsFor(int level) => data.stars[level] ?? 0;
  bool isUnlocked(int level) => level <= data.unlockedLevel;

  /// Enregistre une victoire et attribue les récompenses.
  Future<LevelReward> completeLevel(int level, int score, int stars) async {
    final first = !data.stars.containsKey(level);
    final prevStars = starsFor(level);
    final newBest = score > (data.bestScores[level] ?? 0);
    if (stars > prevStars) data.stars[level] = stars;
    if (newBest) data.bestScores[level] = score;
    if (level >= data.unlockedLevel) data.unlockedLevel = level + 1;

    var coins = Economy.coinsForWin(stars, prevStars, first);
    final milestone = first && level % 10 == 0;
    if (milestone) {
      coins += Economy.milestoneCoins;
      data.boosters['hammer'] = booster('hammer') + 1;
      data.boosters['shuffle'] = booster('shuffle') + 1;
    }
    data.coins += coins;
    // La vie consommée au lancement est rendue en cas de victoire.
    data.settleLives(clock());
    data.livesStored = (data.livesStored + 1).clamp(0, PlayerData.maxLives);
    await save();
    return LevelReward(coins, firstClear: first, milestone: milestone, newBest: newBest);
  }

  // --- Récompense quotidienne --------------------------------------------------

  static int _dayKey(DateTime d) => d.year * 10000 + d.month * 100 + d.day;

  bool get canClaimDaily => data.lastDailyClaim != _dayKey(clock());

  /// Jour (0..6) du cycle qui sera réclamé aujourd'hui.
  int get dailyIndex {
    final now = clock();
    final yesterday = _dayKey(now.subtract(const Duration(days: 1)));
    final continues = data.lastDailyClaim == yesterday || data.lastDailyClaim == _dayKey(now);
    final streak = continues ? data.dailyStreak : 0;
    return (canClaimDaily ? streak : streak - 1) % Economy.dailyRewards.length;
  }

  Future<DailyReward?> claimDaily() async {
    if (!canClaimDaily) return null;
    final idx = dailyIndex;
    final r = Economy.dailyRewards[idx];
    final now = clock();
    final yesterday = _dayKey(now.subtract(const Duration(days: 1)));
    data.dailyStreak = data.lastDailyClaim == yesterday ? data.dailyStreak + 1 : 1;
    data.lastDailyClaim = _dayKey(now);
    data.coins += r.coins;
    r.boosters.forEach((k, v) => data.boosters[k] = booster(k) + v);
    await save();
    return r;
  }

  // --- Achats intégrés ------------------------------------------------------------
  // Stockés sous des clés locales séparées : le format synchronisé avec
  // Firebase (PlayerData) n'est pas modifié.

  static const _deliveredKey = 'iap_delivered_v1';

  Set<String> deliveredPurchaseIds() {
    final raw = _store.read(_deliveredKey);
    if (raw == null) return {};
    try {
      return (jsonDecode(raw) as List).cast<String>().toSet();
    } catch (_) {
      return {};
    }
  }

  Future<void> markPurchaseDelivered(String purchaseId) async {
    final ids = deliveredPurchaseIds()..add(purchaseId);
    await _store.write(_deliveredKey, jsonEncode(ids.toList()));
  }

  /// Livre le contenu d'un produit acheté ET vérifié.
  Future<void> deliverPurchase(String productId) async {
    final r = ProductReward.catalog[productId];
    if (r == null) return;
    data.coins += r.coins;
    r.boosters.forEach((k, v) => data.boosters[k] = booster(k) + v);
    if (r.removesAds) data.noAds = true;
    await save();
  }

  // --- Publicités récompensées : plafond quotidien ------------------------------------

  static const _adRewardsKey = 'ad_rewards_v1';

  Map<String, int> _adCounts() {
    final raw = _store.read(_adRewardsKey);
    if (raw == null) return {};
    try {
      final j = jsonDecode(raw) as Map<String, dynamic>;
      if (j['day'] != _dayKey(clock())) return {};
      return (j['counts'] as Map).map((k, v) => MapEntry('$k', (v as num).toInt()));
    } catch (_) {
      return {};
    }
  }

  /// Nombre de récompenses publicitaires encore disponibles aujourd'hui.
  int adRewardsLeft(String kind) => (Economy.adDailyCaps[kind] ?? 0) - (_adCounts()[kind] ?? 0);

  Future<void> countAdReward(String kind) async {
    final c = _adCounts();
    c[kind] = (c[kind] ?? 0) + 1;
    await _store.write(_adRewardsKey, jsonEncode({'day': _dayKey(clock()), 'counts': c}));
    notifyListeners();
  }

  // --- Paramètres -------------------------------------------------------------

  Future<void> updateSettings({bool? sound, bool? music, bool? notifications, String? language}) async {
    if (sound != null) data.sound = sound;
    if (music != null) data.music = music;
    if (notifications != null) data.notifications = notifications;
    if (language != null) data.language = language;
    await save();
  }
}
