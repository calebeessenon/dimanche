// Joueur automatique utilisé pour l'équilibrage des niveaux.
import 'dart:math';

import 'package:tropi_pop/game/engine/game_engine.dart';
import 'package:tropi_pop/game/levels/level_definition.dart';

double _value(GameEngine e) {
  var v = 0.0;
  for (final o in e.level.objectives) {
    final p = min(e.progressOf(o), o.target) / o.target;
    v += o.type == ObjectiveType.score ? p * 1000 : p * 3000;
  }
  return v + e.score * 0.01;
}

class BotResult {
  final bool won;
  final int score;
  final int movesUsed;
  BotResult(this.won, this.score, this.movesUsed);
}

/// Joue une partie avec un joueur glouton (1 coup d'anticipation).
/// [ignoreWin] : continue jusqu'au dernier coup (mesure du score brut).
BotResult play(LevelDefinition l, int seed, {bool ignoreWin = false}) {
  final e = GameEngine(l, seed: seed);
  final r = Random(seed);
  while (true) {
    if (ignoreWin ? e.movesLeft <= 0 : e.status != GameStatus.playing) break;
    final moves = e.validMoves;
    var best = moves[r.nextInt(moves.length)];
    var bestV = -1.0;
    for (final m in moves) {
      final c = e.clone(seed: r.nextInt(1 << 30));
      c.swap(m.$1, m.$2);
      c.resolveAll();
      final v = _value(c) + r.nextDouble();
      if (v > bestV) {
        bestV = v;
        best = m;
      }
    }
    e.swap(best.$1, best.$2);
    e.resolveAll();
  }
  final won = e.status == GameStatus.won;
  final used = e.movesUsed;
  if (won) {
    e.startFinale();
    e.resolveAll();
  }
  return BotResult(won, e.score, used);
}
