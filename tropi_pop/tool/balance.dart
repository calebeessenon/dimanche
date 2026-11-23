// Rapport d'équilibrage : taux de victoire du bot par niveau.
// Usage : dart run tool/balance.dart [premier] [dernier] [parties]
import 'dart:io';

import 'package:tropi_pop/game/levels/level_catalog.dart';

import 'bot.dart';

void main(List<String> args) {
  final first = args.isNotEmpty ? int.parse(args[0]) : 1;
  final last = args.length > 1 ? int.parse(args[1]) : LevelCatalog.count;
  final games = args.length > 2 ? int.parse(args[2]) : 8;
  stdout.writeln('niv  objectifs                              coups coul victoire  étoiles moy');
  for (var n = first; n <= last; n++) {
    final l = LevelCatalog.level(n);
    var wins = 0, stars = 0;
    for (var g = 0; g < games; g++) {
      final res = play(l, 900000 + n * 100 + g);
      if (res.won) {
        wins++;
        stars += res.score >= l.starScores[2] ? 3 : res.score >= l.starScores[1] ? 2 : 1;
      }
    }
    final obj = l.objectives.map((o) => '${o.type.name}${o.color != null ? ':${o.color!.name}' : ''}=${o.target}').join(',');
    stdout.writeln('${n.toString().padLeft(3)}  ${obj.padRight(38)} ${l.moves.toString().padLeft(4)} ${l.colorCount.toString().padLeft(4)}'
        ' ${(100 * wins / games).toStringAsFixed(0).padLeft(7)}%  ${(wins == 0 ? 0 : stars / wins).toStringAsFixed(1).padLeft(6)}');
  }
}
