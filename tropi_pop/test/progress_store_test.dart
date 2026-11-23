import 'package:flutter_test/flutter_test.dart';
import 'package:tropi_pop/data/economy.dart';
import 'package:tropi_pop/data/player_data.dart';
import 'package:tropi_pop/data/progress_store.dart';

void main() {
  late DateTime now;
  late MemoryStore mem;
  ProgressStore open() => ProgressStore(mem, clock: () => now);

  setUp(() {
    now = DateTime(2026, 11, 23, 10);
    mem = MemoryStore();
  });

  test('état initial', () {
    final p = open();
    expect(p.lives, PlayerData.maxLives);
    expect(p.coins, Economy.startCoins);
    expect(p.data.unlockedLevel, 1);
  });

  test('vies : consommation et régénération (1 toutes les 30 min)', () async {
    final p = open();
    for (var i = 0; i < 5; i++) {
      expect(await p.consumeLife(), isTrue);
    }
    expect(p.lives, 0);
    expect(await p.consumeLife(), isFalse);
    now = now.add(const Duration(minutes: 31));
    expect(p.lives, 1);
    now = now.add(const Duration(hours: 5));
    expect(p.lives, 5);
    expect(p.nextLifeIn, isNull);
  });

  test('victoire : déblocage, étoiles, pièces, vie rendue, coffre tous les 10 niveaux', () async {
    final p = open();
    await p.consumeLife();
    final r = await p.completeLevel(1, 5000, 2);
    expect(p.data.unlockedLevel, 2);
    expect(p.starsFor(1), 2);
    expect(r.firstClear, isTrue);
    expect(p.coins, Economy.startCoins + r.coins);
    expect(p.lives, 5);
    // Rejouer avec moins d'étoiles ne fait pas régresser.
    await p.completeLevel(1, 100, 1);
    expect(p.starsFor(1), 2);
    // Étape (niveau 10).
    final m = await p.completeLevel(10, 9000, 3);
    expect(m.milestone, isTrue);
    expect(p.data.unlockedLevel, 11);
  });

  test('persistance + détection de falsification', () async {
    final p = open();
    await p.addCoins(1000);
    expect(open().coins, Economy.startCoins + 1000);
    // Modification manuelle de la sauvegarde.
    mem.data['player_v1'] = mem.data['player_v1']!.replaceFirst('"coins":1500', '"coins":999999');
    final t = open();
    expect(t.tampered, isTrue);
    expect(t.coins, lessThanOrEqualTo(500));
  });

  test('boutique : achat avec pièces', () async {
    final p = open();
    final hammer = p.booster('hammer');
    expect(await p.buyWithCoins(Economy.coinOffers.first), isTrue);
    expect(p.booster('hammer'), hammer + 1);
    expect(await p.spendCoins(999999), isFalse);
  });

  test('récompense quotidienne : une par jour, série consécutive, remise à zéro', () async {
    final p = open();
    expect(p.canClaimDaily, isTrue);
    expect(p.dailyIndex, 0);
    await p.claimDaily();
    expect(p.canClaimDaily, isFalse);
    expect(await p.claimDaily(), isNull);
    now = now.add(const Duration(days: 1));
    expect(p.dailyIndex, 1);
    await p.claimDaily();
    now = now.add(const Duration(days: 3)); // série cassée
    expect(p.dailyIndex, 0);
  });
}
