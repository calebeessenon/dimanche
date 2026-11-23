import 'dart:math';

import 'player_data.dart';

/// Fusion d'une sauvegarde locale et d'une sauvegarde cloud.
///
/// Règles (aucune progression ne doit jamais être perdue) :
/// * niveaux débloqués, étoiles, meilleurs scores, parties jouées : maximum des deux ;
/// * pièces, boosters, vies : ceux de la sauvegarde la plus récente (prendre le
///   maximum permettrait de dupliquer la monnaie entre deux téléphones) ;
/// * récompense quotidienne : la plus avancée ;
/// * achat « sans pub » : conservé s'il existe d'un côté ;
/// * réglages (son, langue…) : ceux de l'appareil.
PlayerData mergeProgress(PlayerData local, PlayerData cloud) {
  final newer = cloud.updatedAtMs > local.updatedAtMs ? cloud : local;
  final stars = <int, int>{...local.stars};
  cloud.stars.forEach((k, v) => stars[k] = max(stars[k] ?? 0, v));
  final best = <int, int>{...local.bestScores};
  cloud.bestScores.forEach((k, v) => best[k] = max(best[k] ?? 0, v));
  final daily = cloud.lastDailyClaim > local.lastDailyClaim ? cloud : local;

  return PlayerData(
    coins: newer.coins,
    livesStored: newer.livesStored,
    lastLifeAtMs: newer.lastLifeAtMs,
    unlockedLevel: max(local.unlockedLevel, cloud.unlockedLevel),
    stars: stars,
    bestScores: best,
    boosters: Map.of(newer.boosters),
    sound: local.sound,
    music: local.music,
    notifications: local.notifications,
    language: local.language,
    noAds: local.noAds || cloud.noAds,
    lastDailyClaim: daily.lastDailyClaim,
    dailyStreak: daily.dailyStreak,
    gamesPlayed: max(local.gamesPlayed, cloud.gamesPlayed),
    updatedAtMs: max(local.updatedAtMs, cloud.updatedAtMs),
  );
}

/// Vrai si la fusion apporte quelque chose de nouveau par rapport à [local].
bool mergeChangesLocal(PlayerData local, PlayerData merged) =>
    merged.unlockedLevel != local.unlockedLevel ||
    merged.totalStars != local.totalStars ||
    merged.coins != local.coins ||
    merged.noAds != local.noAds ||
    merged.lastDailyClaim != local.lastDailyClaim ||
    merged.bestScores.length != local.bestScores.length;
