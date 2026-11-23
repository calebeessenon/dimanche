import 'dart:math';

import '../engine/models.dart';
import 'level_definition.dart';
import 'level_tuning.dart';

/// Catalogue des niveaux.
///
/// Les 100 premiers niveaux sont générés de façon **déterministe** à partir de
/// leur numéro (même niveau sur tous les téléphones) selon une courbe de
/// difficulté calibrée par simulation (voir `tool/balance.dart`).
///
/// Pour ajouter des niveaux faits main, il suffit d'appeler [register] avec
/// une [LevelDefinition] (par ex. chargée depuis un JSON en assets ou depuis
/// Remote Config) : elle remplace le niveau généré du même numéro.
class LevelCatalog {
  LevelCatalog._();

  static const int count = 100;

  static final Map<int, LevelDefinition> _cache = {};

  static LevelDefinition level(int n) => _cache[n] ??= _generate(n);

  /// Version brute (non calibrée) — utilisée par l'outil de calibration.
  static LevelDefinition untuned(int n) => _generate(n, tuned: false);

  static void register(LevelDefinition def) => _cache[def.number] = def;

  /// Nombre de "mondes" (10 niveaux chacun) pour la carte.
  static int worldOf(int n) => (n - 1) ~/ 10;

  // ---------------------------------------------------------------------------
  // Formes de plateau (8 colonnes × 9 lignes max).
  // ---------------------------------------------------------------------------

  static const _shapes = <List<String>>[
    // 0 - plein
    ['........', '........', '........', '........', '........', '........', '........', '........'],
    // 1 - coins coupés
    ['##....##', '#......#', '........', '........', '........', '........', '#......#', '##....##'],
    // 2 - trou central
    ['........', '........', '........', '...##...', '...##...', '........', '........', '........'],
    // 3 - croix
    ['##....##', '##....##', '........', '........', '........', '........', '##....##', '##....##'],
    // 4 - deux îles
    ['...##...', '...##...', '........', '........', '........', '........', '...##...', '...##...'],
    // 5 - sablier
    ['........', '#......#', '##....##', '##....##', '##....##', '##....##', '#......#', '........'],
    // 6 - coeur
    ['.##..##.', '........', '........', '........', '#......#', '##....##', '###..###', '###..###'],
    // 7 - grand 8×9
    ['........', '........', '........', '........', '........', '........', '........', '........', '........'],
    // 8 - arche
    ['........', '........', '........', '........', '..####..', '........', '........', '........', '........'],
    // 9 - losange
    ['###..###', '##....##', '#......#', '........', '........', '#......#', '##....##', '###..###'],
  ];

  // ---------------------------------------------------------------------------
  // Génération
  // ---------------------------------------------------------------------------

  /// Points moyens par coup observés par le bot (5 / 6 couleurs) — voir tool/balance.dart.
  static const _ppm5 = 260.0;
  static const _ppm6 = 190.0;

  static LevelDefinition _generate(int n, {bool tuned = true}) {
    final r = Random(n * 7919 + 17);
    final t = (n - 1) / (count - 1); // 0 → 1

    // Forme : plein au début, puis variété.
    final shape = n <= 4 ? _shapes[0] : _shapes[r.nextInt(_shapes.length)];
    final grid = [for (final row in shape) row.split('')];
    final h = grid.length, w = grid.first.length;

    // Couleurs : 4 pour l'apprentissage, puis 5, puis 6 avec des niveaux "respiration".
    final colors = n <= 3
        ? 4
        : n <= 22
            ? 5
            : (n % 5 == 0 ? 5 : 6);

    // Type de niveau.
    final kind = _kindFor(n);
    final objectives = <Objective>[];

    // Coups de base (recalibrés ensuite par level_tuning.dart).
    var moves = n <= 2 ? 14 : (24 - t * 4).round();

    bool playable(int x, int y) => grid[y][x] != '#';

    void addIce({required bool dbl}) {
      final pattern = r.nextInt(4);
      for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
          if (!playable(x, y)) continue;
          final on = switch (pattern) {
            0 => x >= 2 && x < w - 2 && y >= 2 && y < h - 2, // bloc central
            1 => y >= h - 2, // fond
            2 => (x + y) % 2 == 0 && y >= 2 && y < h - 2, // damier central
            _ => x == 0 || x == w - 1, // bords
          };
          if (on) grid[y][x] = dbl && (x + y) % 3 == 0 ? 'I' : 'i';
        }
      }
    }

    void addCrates({required bool strong}) {
      final pattern = r.nextInt(3);
      for (var y = 0; y < h; y++) {
        for (var x = 0; x < w; x++) {
          if (grid[y][x] != '.') continue;
          final on = switch (pattern) {
            0 => (y == h ~/ 2 || y == h ~/ 2 - 1) && x >= 1 && x < w - 1, // double barre
            1 => (y % 4 == 2 || y % 4 == 3) && (x % 4 == 1 || x % 4 == 2), // blocs 2×2
            _ => y >= h - 3 && (x + y) % 2 == 1, // fond en damier
          };
          if (on) grid[y][x] = strong && (x + y) % 3 == 0 ? 'C' : 'c';
        }
      }
    }

    final ppm = colors >= 6 ? _ppm6 : _ppm5;
    // Fruits d'une couleur à collecter par coup (plus il y a de couleurs, moins on en obtient).
    final collectRate = (switch (colors) { 4 => 2.4, 5 => 1.6, _ => 1.2 }) * (1 + t * 0.3);
    TileColor pickColor() => TileColor.values[r.nextInt(colors)];

    switch (kind) {
      case _Kind.score:
        final target = _round(moves * ppm * (0.75 + t * 0.35));
        objectives.add(Objective(ObjectiveType.score, target));
      case _Kind.collect:
        final c = pickColor();
        objectives.add(Objective(ObjectiveType.collect, _round5(moves * collectRate), c));
      case _Kind.collect2:
        final c1 = pickColor();
        var c2 = pickColor();
        while (c2 == c1) {
          c2 = pickColor();
        }
        objectives
          ..add(Objective(ObjectiveType.collect, _round5(moves * collectRate * 0.6), c1))
          ..add(Objective(ObjectiveType.collect, _round5(moves * collectRate * 0.6), c2));
        moves += 2;
      case _Kind.ice:
        addIce(dbl: n >= 30);
        moves += 2;
      case _Kind.crates:
        addCrates(strong: n >= 40);
        moves += 1;
      case _Kind.iceCollect:
        addIce(dbl: n >= 45);
        objectives.add(Objective(ObjectiveType.collect, _round5(moves * collectRate * 0.6), pickColor()));
        moves += 4;
      case _Kind.boss:
        addIce(dbl: n >= 50);
        addCrates(strong: n >= 60);
        moves += 6;
    }

    final layout = [for (final row in grid) row.join()];
    final ice = layout.join().split('').fold<int>(0, (s, ch) => s + (ch == 'i' ? 1 : ch == 'I' ? 2 : 0));
    final crates = layout.join().split('').fold<int>(0, (s, ch) => s + (ch == 'c' ? 1 : ch == 'C' ? 2 : 0));
    if (ice > 0) objectives.insert(0, Objective(ObjectiveType.clearIce, ice));
    if (crates > 0) objectives.insert(0, Objective(ObjectiveType.clearCrates, crates));

    // Seuils d'étoiles.
    List<int> stars;
    final scoreObj = objectives.where((o) => o.type == ObjectiveType.score).firstOrNull;
    if (scoreObj != null) {
      stars = [scoreObj.target, _round(scoreObj.target * 1.35), _round(scoreObj.target * 1.7)];
    } else {
      stars = [_round(moves * 40), _round(moves * ppm * 0.85), _round(moves * ppm * 1.25)];
    }

    // Calibration issue de la simulation (tool/tune_levels.dart).
    if (tuned && n <= levelTuning.length) {
      final tune = levelTuning[n - 1];
      moves = tune[0];
      stars = [tune[1], tune[2], tune[3]];
      for (var i = 0; i < objectives.length; i++) {
        final o = objectives[i];
        if (o.type == ObjectiveType.score) objectives[i] = Objective(ObjectiveType.score, tune[1]);
        if (o.type == ObjectiveType.collect) objectives[i] = Objective(ObjectiveType.collect, _round5(o.target * tune[4] / 100), o.color);
      }
    }

    return LevelDefinition(
      number: n,
      layout: layout,
      moves: moves,
      colorCount: colors,
      objectives: objectives,
      starScores: stars,
    );
  }

  static _Kind _kindFor(int n) {
    if (n <= 2) return _Kind.score;
    if (n <= 5) return n.isOdd ? _Kind.collect : _Kind.score;
    if (n <= 10) return n == 10 ? _Kind.iceCollect : (n.isEven ? _Kind.ice : _Kind.collect);
    if (n % 10 == 0) return _Kind.boss;
    const cycle = [_Kind.collect, _Kind.crates, _Kind.ice, _Kind.score, _Kind.collect2, _Kind.iceCollect, _Kind.crates, _Kind.collect, _Kind.ice];
    return cycle[n % cycle.length];
  }

  static int _round(num v) => (v / 100).round() * 100;
  static int _round5(num v) => max(5, (v / 5).round() * 5);
}

enum _Kind { score, collect, collect2, ice, crates, iceCollect, boss }
