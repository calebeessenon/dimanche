// Calibre automatiquement les 100 niveaux avec le bot et génère
// lib/game/levels/level_tuning.dart (coups + seuils d'étoiles).
//
// Usage : dart run tool/tune_levels.dart [parties_par_essai]
import 'dart:io';
import 'dart:math';

import 'package:tropi_pop/game/levels/level_catalog.dart';
import 'package:tropi_pop/game/levels/level_definition.dart';

import 'bot.dart';

/// Taux de victoire visé pour le bot (un joueur humain dispose en plus des boosters).
double targetWinRate(int n) {
  var t = n <= 10 ? 0.97 : 0.92 - (n - 10) / 90 * 0.32; // 0.92 → 0.60
  if (n % 10 == 0 && n > 10) t -= 0.10; // niveau "boss"
  if (n % 10 == 1 && n > 10) t += 0.08; // respiration après un boss
  return t.clamp(0.4, 0.97);
}

LevelDefinition withMoves(LevelDefinition l, int moves, {int? scoreTarget, List<int>? stars, int collectPct = 100}) {
  final objs = [
    for (final o in l.objectives)
      if (o.type == ObjectiveType.score && scoreTarget != null)
        Objective(ObjectiveType.score, scoreTarget)
      else if (o.type == ObjectiveType.collect)
        Objective(ObjectiveType.collect, collectTarget(o.target, collectPct), o.color)
      else
        o
  ];
  return LevelDefinition(
    number: l.number,
    layout: l.layout,
    moves: moves,
    colorCount: l.colorCount,
    objectives: objs,
    starScores: stars ?? (scoreTarget != null ? [scoreTarget, scoreTarget, scoreTarget] : [0, 0, 0]),
  );
}

int round100(num v) => (v / 100).round() * 100;
int collectTarget(int base, int pct) => max(5, (base * pct / 100 / 5).round() * 5);

void main(List<String> args) {
  final games = args.isNotEmpty ? int.parse(args[0]) : 16;
  final out = StringBuffer()
    ..writeln('// FICHIER GÉNÉRÉ par tool/tune_levels.dart — ne pas modifier à la main.')
    ..writeln('// [coups, étoile1, étoile2, étoile3, % collecte] par niveau, calibrés par simulation.')
    ..writeln('const List<List<int>> levelTuning = [');
  for (var n = 1; n <= LevelCatalog.count; n++) {
    final base = LevelCatalog.untuned(n);
    final target = targetWinRate(n);
    final isScore = base.objectives.any((o) => o.type == ObjectiveType.score);
    final hasCollect = base.objectives.any((o) => o.type == ObjectiveType.collect);
    final minMoves = n <= 10 ? 12 : 15;
    const maxMoves = 40;
    var moves = base.moves;
    var pct = 100;
    List<int> stars;
    if (isScore) {
      // Coups fixés par le générateur ; le score cible est un quantile des scores du bot.
      final scores = [
        for (var g = 0; g < games; g++) play(withMoves(base, moves, scoreTarget: 1 << 30), n * 1000 + g, ignoreWin: true).score
      ]..sort();
      // Facteur "joueur humain" : les premiers niveaux doivent être très accessibles.
      final human = n <= 5 ? 0.55 : (n <= 20 ? 0.8 : 0.9);
      final s1 = round100(scores[((1 - target) * (games - 1)).floor()] * human);
      stars = [s1, round100(s1 * 1.3), round100(s1 * 1.6)];
    } else {
      double rate(int m) {
        var w = 0;
        for (var g = 0; g < games; g++) {
          if (play(withMoves(base, m, stars: [0, 0, 0], collectPct: pct), n * 1000 + g).won) w++;
        }
        return w / games;
      }

      for (var attempt = 0; attempt < 8; attempt++) {
        // Recherche dichotomique du nombre minimal de coups atteignant le taux visé.
        var lo = minMoves, hi = maxMoves;
        while (lo < hi) {
          final mid = (lo + hi) ~/ 2;
          if (rate(mid) >= target) {
            hi = mid;
          } else {
            lo = mid + 1;
          }
        }
        moves = lo;
        if (!hasCollect) break;
        if (moves <= minMoves + 2) {
          pct += 20; // trop facile : on augmente la quantité à collecter
        } else if (moves >= maxMoves - 2) {
          pct = max(40, pct - 15); // trop dur : on la réduit
        } else {
          break;
        }
      }
      final finals = <int>[];
      for (var g = 0; g < games; g++) {
        final r = play(withMoves(base, moves, stars: [0, 0, 0], collectPct: pct), n * 2000 + g);
        if (r.won) finals.add(r.score);
      }
      finals.sort();
      if (finals.isEmpty) finals.add(moves * 300);
      final s2 = round100(finals[(finals.length * 0.45).floor()]);
      final s3 = max(s2 + 500, round100(finals[min(finals.length - 1, (finals.length * 0.85).floor())]));
      stars = [round100(max(100, s2 * 0.15)), s2, s3];
    }
    stdout.writeln('niveau $n : coups=$moves étoiles=$stars collecte=$pct% (cible ${(target * 100).round()}%)');
    out.writeln('  [$moves, ${stars.join(', ')}, $pct], // $n');
  }
  out.writeln('];');
  File('lib/game/levels/level_tuning.dart').writeAsStringSync(out.toString());
  stdout.writeln('→ lib/game/levels/level_tuning.dart écrit.');
}
