/// Données de progression du joueur (sérialisables en JSON).
///
/// Ce même format est utilisé pour la sauvegarde locale et, à l'étape 5,
/// pour la synchronisation cloud (Firebase) lors d'un changement de téléphone.
class PlayerData {
  static const maxLives = 5;
  static const lifeRegen = Duration(minutes: 30);

  int coins;
  int livesStored;
  int lastLifeAtMs; // horodatage de référence pour la régénération
  int unlockedLevel;
  Map<int, int> stars;
  Map<int, int> bestScores;
  Map<String, int> boosters; // hammer, shuffle, moves
  bool sound;
  bool music;
  bool notifications;
  String language;
  bool noAds;
  int lastDailyClaim; // aaaammjj
  int dailyStreak;
  int gamesPlayed;
  int updatedAtMs;

  PlayerData({
    this.coins = 500,
    this.livesStored = maxLives,
    this.lastLifeAtMs = 0,
    this.unlockedLevel = 1,
    Map<int, int>? stars,
    Map<int, int>? bestScores,
    Map<String, int>? boosters,
    this.sound = true,
    this.music = true,
    this.notifications = true,
    this.language = 'fr',
    this.noAds = false,
    this.lastDailyClaim = 0,
    this.dailyStreak = 0,
    this.gamesPlayed = 0,
    this.updatedAtMs = 0,
  })  : stars = stars ?? {},
        bestScores = bestScores ?? {},
        boosters = boosters ?? {'hammer': 3, 'shuffle': 3, 'moves': 1};

  int get totalStars => stars.values.fold(0, (a, b) => a + b);
  int get levelsCompleted => stars.length;

  /// Vies actuelles en tenant compte de la régénération.
  int livesAt(DateTime now) {
    if (livesStored >= maxLives) return livesStored;
    final elapsed = now.millisecondsSinceEpoch - lastLifeAtMs;
    final gained = elapsed ~/ lifeRegen.inMilliseconds;
    return (livesStored + gained).clamp(0, maxLives);
  }

  /// Temps avant la prochaine vie (null si plein).
  Duration? nextLifeIn(DateTime now) {
    if (livesAt(now) >= maxLives) return null;
    final elapsed = now.millisecondsSinceEpoch - lastLifeAtMs;
    final rem = lifeRegen.inMilliseconds - elapsed % lifeRegen.inMilliseconds;
    return Duration(milliseconds: rem);
  }

  /// Fige la régénération dans `livesStored` (à appeler avant toute modification).
  void settleLives(DateTime now) {
    final current = livesAt(now);
    if (current >= maxLives) {
      livesStored = current;
      lastLifeAtMs = now.millisecondsSinceEpoch;
      return;
    }
    final elapsed = now.millisecondsSinceEpoch - lastLifeAtMs;
    final gained = elapsed ~/ lifeRegen.inMilliseconds;
    livesStored = current;
    lastLifeAtMs += gained * lifeRegen.inMilliseconds;
  }

  Map<String, dynamic> toJson() => {
        'coins': coins,
        'livesStored': livesStored,
        'lastLifeAtMs': lastLifeAtMs,
        'unlockedLevel': unlockedLevel,
        'stars': stars.map((k, v) => MapEntry('$k', v)),
        'bestScores': bestScores.map((k, v) => MapEntry('$k', v)),
        'boosters': boosters,
        'sound': sound,
        'music': music,
        'notifications': notifications,
        'language': language,
        'noAds': noAds,
        'lastDailyClaim': lastDailyClaim,
        'dailyStreak': dailyStreak,
        'gamesPlayed': gamesPlayed,
        'updatedAtMs': updatedAtMs,
      };

  factory PlayerData.fromJson(Map<String, dynamic> j) {
    Map<int, int> intMap(Object? o) =>
        (o as Map? ?? {}).map((k, v) => MapEntry(int.parse('$k'), (v as num).toInt()));
    return PlayerData(
      coins: j['coins'] as int? ?? 500,
      livesStored: j['livesStored'] as int? ?? maxLives,
      lastLifeAtMs: j['lastLifeAtMs'] as int? ?? 0,
      unlockedLevel: j['unlockedLevel'] as int? ?? 1,
      stars: intMap(j['stars']),
      bestScores: intMap(j['bestScores']),
      boosters: (j['boosters'] as Map?)?.map((k, v) => MapEntry('$k', (v as num).toInt())),
      sound: j['sound'] as bool? ?? true,
      music: j['music'] as bool? ?? true,
      notifications: j['notifications'] as bool? ?? true,
      language: j['language'] as String? ?? 'fr',
      noAds: j['noAds'] as bool? ?? false,
      lastDailyClaim: j['lastDailyClaim'] as int? ?? 0,
      dailyStreak: j['dailyStreak'] as int? ?? 0,
      gamesPlayed: j['gamesPlayed'] as int? ?? 0,
      updatedAtMs: j['updatedAtMs'] as int? ?? 0,
    );
  }
}
