import 'dart:math';

import '../levels/level_definition.dart';
import 'board.dart';
import 'models.dart';

/// Effet visuel produit par un bonus (consommé par l'interface).
enum EffectKind { lineH, lineV, bomb, bigBomb, rainbow, cross, megaCross }

class BoardEffect {
  final EffectKind kind;
  final Pos at;
  final TileColor? color;
  const BoardEffect(this.kind, this.at, [this.color]);
}

/// Un élément retiré du plateau pendant une étape.
class RemovedTile {
  final Pos pos;
  final Tile tile;
  const RemovedTile(this.pos, this.tile);
}

/// Résultat d'une étape de résolution (élimination).
class ClearStep {
  final List<RemovedTile> removed;
  final List<Pos> createdSpecials;
  final List<BoardEffect> effects;
  final Set<Pos> iceHit;
  final Set<Pos> crateHit;
  final int points;
  final int cascade;

  const ClearStep({
    required this.removed,
    required this.createdSpecials,
    required this.effects,
    required this.iceHit,
    required this.crateHit,
    required this.points,
    required this.cascade,
  });
}

/// Un nouvel élément apparu en haut d'une colonne.
class Spawn {
  final Tile tile;
  final Pos to;

  /// Nombre de cases au-dessus du plateau d'où l'élément commence sa chute.
  final int dropFrom;
  const Spawn(this.tile, this.to, this.dropFrom);
}

enum SwapOutcome { invalid, match, combo }

enum GameStatus { playing, won, lost }

/// Moteur Match-3 complet : permutation, détection, bonus, cascades, gravité,
/// objectifs, mélange automatique.
///
/// Utilisation typique (interface ou bot) :
/// ```dart
/// final r = engine.swap(a, b);
/// if (r != SwapOutcome.invalid) {
///   ClearStep? s;
///   while ((s = engine.resolveStep()) != null) {
///     engine.applyGravity();
///   }
///   engine.shuffleIfNeeded();
/// }
/// ```
class GameEngine {
  final LevelDefinition level;
  final Random rng;
  late final Board board;
  late final List<TileColor> palette;

  int score = 0;
  int movesLeft;
  int movesUsed = 0;
  final Map<TileColor, int> collected = {for (final c in TileColor.values) c: 0};
  late final int initialIce;
  late final int initialCrates;

  int _nextId = 1;
  int _cascade = 0;
  Pos? _swapA;
  Pos? _swapB;

  /// Cellules à éliminer de force lors de la prochaine étape (combos, marteau, finale).
  final Set<Pos> _forced = {};
  final List<BoardEffect> _forcedEffects = [];

  /// Identifiants des bonus déjà "consommés" (ne doivent pas se déclencher à nouveau).
  final Set<int> _consumed = {};

  GameEngine(this.level, {int? seed})
      : rng = Random(seed),
        movesLeft = level.moves {
    palette = TileColor.values.take(level.colorCount.clamp(3, 6)).toList();
    board = _buildBoard();
    initialIce = board.totalIce;
    initialCrates = board.totalCrates;
    _fillInitial();
  }

  /// Copie indépendante (utilisée par le bot de simulation / l'indice avancé).
  GameEngine._copy(GameEngine o, int seed)
      : level = o.level,
        rng = Random(seed),
        movesLeft = o.movesLeft {
    palette = o.palette;
    board = o.board.clone();
    initialIce = o.initialIce;
    initialCrates = o.initialCrates;
    score = o.score;
    movesUsed = o.movesUsed;
    collected.addAll(o.collected);
    _nextId = o._nextId;
  }

  GameEngine clone({int seed = 0}) => GameEngine._copy(this, seed);

  // ---------------------------------------------------------------------------
  // Construction
  // ---------------------------------------------------------------------------

  Board _buildBoard() {
    final w = level.width, h = level.height;
    final cells = <Cell>[];
    for (var y = 0; y < h; y++) {
      final row = level.layout[y];
      for (var x = 0; x < w; x++) {
        final ch = x < row.length ? row[x] : '#';
        cells.add(switch (ch) {
          '#' => Cell(playable: false),
          'i' => Cell(playable: true, ice: 1),
          'I' => Cell(playable: true, ice: 2),
          'c' => Cell(playable: true, crate: 1),
          'C' => Cell(playable: true, crate: 2),
          _ => Cell(playable: true),
        });
      }
    }
    return Board(w, h, cells);
  }

  Tile _newTile([TileColor? color]) => Tile(_nextId++, color ?? palette[rng.nextInt(palette.length)]);

  void _fillInitial() {
    for (var attempt = 0; attempt < 200; attempt++) {
      for (final p in board.positions) {
        final c = board.at(p);
        if (!c.canHoldTile) continue;
        // Choisit une couleur qui ne crée pas d'alignement initial.
        final options = palette.where((col) => !_wouldMatch(p.x, p.y, col)).toList();
        c.tile = _newTile(options.isEmpty ? null : options[rng.nextInt(options.length)]);
      }
      if (findHint() != null) return;
    }
  }

  bool _wouldMatch(int x, int y, TileColor col) {
    bool same(int xx, int yy) => board.matchColor(xx, yy) == col;
    return (same(x - 1, y) && same(x - 2, y)) || (same(x, y - 1) && same(x, y - 2));
  }

  // ---------------------------------------------------------------------------
  // État de la partie
  // ---------------------------------------------------------------------------

  int progressOf(Objective o) => switch (o.type) {
        ObjectiveType.score => score,
        ObjectiveType.collect => collected[o.color]!,
        ObjectiveType.clearIce => initialIce - board.totalIce,
        ObjectiveType.clearCrates => initialCrates - board.totalCrates,
      };

  bool isObjectiveDone(Objective o) => progressOf(o) >= o.target;

  bool get objectivesComplete => level.objectives.every(isObjectiveDone);

  GameStatus get status {
    if (objectivesComplete && score >= level.starScores[0]) return GameStatus.won;
    if (movesLeft <= 0) return GameStatus.lost;
    return GameStatus.playing;
  }

  int get stars {
    if (status != GameStatus.won) return 0;
    if (score >= level.starScores[2]) return 3;
    if (score >= level.starScores[1]) return 2;
    return 1;
  }

  // ---------------------------------------------------------------------------
  // Permutation
  // ---------------------------------------------------------------------------

  bool _swappable(Pos p) => board.inside(p.x, p.y) && board.at(p).canHoldTile && board.at(p).tile != null;

  /// Tente de permuter deux éléments adjacents. En cas d'échec, le plateau est
  /// laissé inchangé et aucun coup n'est consommé.
  SwapOutcome swap(Pos a, Pos b) {
    if (movesLeft <= 0 || !a.isAdjacent(b) || !_swappable(a) || !_swappable(b)) {
      return SwapOutcome.invalid;
    }
    final ca = board.at(a), cb = board.at(b);
    final ta = ca.tile!, tb = cb.tile!;
    ca.tile = tb;
    cb.tile = ta;

    // Combinaisons de bonus : toujours valides.
    if (ta.isRainbow || tb.isRainbow || (ta.isSpecial && tb.isSpecial)) {
      _consumeMove(a, b);
      _setupCombo(b, ta, a, tb);
      return SwapOutcome.combo;
    }

    if (_hasMatchAt(a.x, a.y) || _hasMatchAt(b.x, b.y)) {
      _consumeMove(a, b);
      return SwapOutcome.match;
    }

    // Annulation.
    ca.tile = ta;
    cb.tile = tb;
    return SwapOutcome.invalid;
  }

  void _consumeMove(Pos a, Pos b) {
    movesLeft--;
    movesUsed++;
    _cascade = 0;
    _swapA = a;
    _swapB = b;
  }

  /// `moved` = élément déplacé par le joueur (désormais en `at`),
  /// `other` = élément qui se trouvait à cet endroit (désormais en `otherAt`).
  void _setupCombo(Pos at, Tile moved, Pos otherAt, Tile other) {
    final s1 = moved.special, s2 = other.special;
    _consumed.addAll([moved.id, other.id]);
    _forced.addAll([at, otherAt]);

    bool isLine(SpecialType s) => s == SpecialType.lineH || s == SpecialType.lineV;

    if (s1 == SpecialType.rainbow && s2 == SpecialType.rainbow) {
      // Deux soleil prisme : tout le plateau saute.
      _forced.addAll(board.positions.where((p) => board.at(p).playable));
      _forcedEffects.add(BoardEffect(EffectKind.megaCross, at));
      return;
    }
    if (s1 == SpecialType.rainbow || s2 == SpecialType.rainbow) {
      final partner = s1 == SpecialType.rainbow ? other : moved;
      final color = partner.color ?? _mostCommonColor();
      _forcedEffects.add(BoardEffect(EffectKind.rainbow, s1 == SpecialType.rainbow ? at : otherAt, color));
      for (final p in board.positions) {
        final t = board.at(p).tile;
        if (t == null || t.color != color || board.at(p).crate > 0) continue;
        if (partner.special == SpecialType.bomb) {
          t.special = SpecialType.bomb;
        } else if (isLine(partner.special)) {
          t.special = rng.nextBool() ? SpecialType.lineH : SpecialType.lineV;
        }
        _forced.add(p);
      }
      if (partner.isSpecial) _consumed.remove(partner.id);
      return;
    }
    if (isLine(s1) && isLine(s2)) {
      _forced.addAll(_row(at.y));
      _forced.addAll(_col(at.x));
      _forcedEffects.add(BoardEffect(EffectKind.cross, at));
      return;
    }
    if ((isLine(s1) && s2 == SpecialType.bomb) || (s1 == SpecialType.bomb && isLine(s2))) {
      for (var d = -1; d <= 1; d++) {
        _forced.addAll(_row(at.y + d));
        _forced.addAll(_col(at.x + d));
      }
      _forcedEffects.add(BoardEffect(EffectKind.megaCross, at));
      return;
    }
    // Bombe + bombe : explosion 5×5.
    _forced.addAll(_square(at, 2));
    _forcedEffects.add(BoardEffect(EffectKind.bigBomb, at));
  }

  // ---------------------------------------------------------------------------
  // Boosters (hors coup)
  // ---------------------------------------------------------------------------

  /// Marteau : détruit un élément (ou endommage une caisse) sans consommer de coup.
  bool useHammer(Pos p) {
    if (!board.inside(p.x, p.y) || !board.at(p).playable) return false;
    final c = board.at(p);
    if (c.tile == null && c.crate == 0) return false;
    _cascade = 0;
    _swapA = _swapB = null;
    _forced.add(p);
    _forcedEffects.add(BoardEffect(EffectKind.bomb, p));
    return true;
  }

  void addMoves(int n) => movesLeft += n;

  /// Finale : chaque coup restant transforme un fruit en bonus ligne, puis
  /// tous les bonus du plateau explosent. Retourne le nombre de coups convertis.
  int startFinale() {
    final n = min(movesLeft, 15);
    final candidates = board.positions
        .where((p) => board.at(p).canHoldTile && board.at(p).tile != null && !board.at(p).tile!.isSpecial)
        .toList()
      ..shuffle(rng);
    for (var i = 0; i < n && i < candidates.length; i++) {
      board.at(candidates[i]).tile!.special = rng.nextBool() ? SpecialType.lineH : SpecialType.lineV;
    }
    score += movesLeft * 250;
    movesLeft = 0;
    _cascade = 0;
    _swapA = _swapB = null;
    for (final p in board.positions) {
      final t = board.at(p).tile;
      if (t != null && t.isSpecial && board.at(p).crate == 0) _forced.add(p);
    }
    return n;
  }

  // ---------------------------------------------------------------------------
  // Détection des alignements
  // ---------------------------------------------------------------------------

  bool _hasMatchAt(int x, int y) {
    final col = board.matchColor(x, y);
    if (col == null) return false;
    var h = 1, v = 1;
    for (var i = x - 1; board.matchColor(i, y) == col; i--) {
      h++;
    }
    for (var i = x + 1; board.matchColor(i, y) == col; i++) {
      h++;
    }
    for (var j = y - 1; board.matchColor(x, j) == col; j--) {
      v++;
    }
    for (var j = y + 1; board.matchColor(x, j) == col; j++) {
      v++;
    }
    return h >= 3 || v >= 3;
  }

  /// Retourne les groupes d'alignements (chaque groupe = runs connectés).
  List<_MatchGroup> _findGroups() {
    final runs = <_Run>[];
    for (var y = 0; y < board.height; y++) {
      var x = 0;
      while (x < board.width) {
        final col = board.matchColor(x, y);
        var end = x + 1;
        if (col != null) {
          while (board.matchColor(end, y) == col) {
            end++;
          }
          if (end - x >= 3) runs.add(_Run([for (var i = x; i < end; i++) Pos(i, y)], true));
        }
        x = end;
      }
    }
    for (var x = 0; x < board.width; x++) {
      var y = 0;
      while (y < board.height) {
        final col = board.matchColor(x, y);
        var end = y + 1;
        if (col != null) {
          while (board.matchColor(x, end) == col) {
            end++;
          }
          if (end - y >= 3) runs.add(_Run([for (var j = y; j < end; j++) Pos(x, j)], false));
        }
        y = end;
      }
    }
    // Regroupe les runs qui partagent une case.
    final groups = <_MatchGroup>[];
    for (final r in runs) {
      final touching = groups.where((g) => r.cells.any(g.cells.contains)).toList();
      final merged = _MatchGroup();
      for (final g in touching) {
        merged.runs.addAll(g.runs);
        merged.cells.addAll(g.cells);
        groups.remove(g);
      }
      merged.runs.add(r);
      merged.cells.addAll(r.cells);
      groups.add(merged);
    }
    return groups;
  }

  // ---------------------------------------------------------------------------
  // Résolution
  // ---------------------------------------------------------------------------

  /// Effectue une étape d'élimination. Retourne `null` si rien n'est à éliminer.
  ClearStep? resolveStep() {
    final groups = _findGroups();
    if (groups.isEmpty && _forced.isEmpty) {
      _swapA = _swapB = null;
      return null;
    }
    _cascade++;

    final toClear = <Pos>{..._forced};
    final effects = <BoardEffect>[..._forcedEffects];
    _forced.clear();
    _forcedEffects.clear();
    final newSpecials = <Pos, Tile>{};
    var bonus = 0;

    for (final g in groups) {
      toClear.addAll(g.cells);
      final color = board.matchColor(g.cells.first.x, g.cells.first.y)!;
      final longest = g.runs.map((r) => r.cells.length).reduce(max);
      final hasH = g.runs.any((r) => r.horizontal);
      final hasV = g.runs.any((r) => !r.horizontal);
      SpecialType special;
      if (longest >= 5) {
        special = SpecialType.rainbow;
        bonus += 400;
      } else if (hasH && hasV) {
        special = SpecialType.bomb;
        bonus += 200;
      } else if (longest == 4) {
        special = g.runs.first.horizontal ? SpecialType.lineH : SpecialType.lineV;
        bonus += 120;
      } else {
        continue;
      }
      final at = _specialPosition(g, special);
      newSpecials[at] = Tile(_nextId++, special == SpecialType.rainbow ? null : color, special);
    }

    // Propagation des bonus déclenchés.
    final queue = toClear.toList();
    final triggered = <int>{};
    while (queue.isNotEmpty) {
      final p = queue.removeLast();
      final t = board.at(p).tile;
      if (t == null || !t.isSpecial || _consumed.contains(t.id) || !triggered.add(t.id)) continue;
      final List<Pos> area;
      switch (t.special) {
        case SpecialType.lineH:
          area = _row(p.y);
          effects.add(BoardEffect(EffectKind.lineH, p));
        case SpecialType.lineV:
          area = _col(p.x);
          effects.add(BoardEffect(EffectKind.lineV, p));
        case SpecialType.bomb:
          area = _square(p, 1);
          effects.add(BoardEffect(EffectKind.bomb, p));
        case SpecialType.rainbow:
          final color = _mostCommonColor();
          area = [
            for (final q in board.positions)
              if (board.at(q).tile?.color == color && board.at(q).crate == 0) q
          ];
          effects.add(BoardEffect(EffectKind.rainbow, p, color));
        case SpecialType.none:
          area = const [];
      }
      for (final q in area) {
        if (toClear.add(q)) queue.add(q);
      }
    }
    _consumed.clear();

    // Application.
    final removed = <RemovedTile>[];
    final iceHit = <Pos>{};
    final crateHit = <Pos>{};
    var cleared = 0;
    for (final p in toClear) {
      final c = board.at(p);
      if (!c.playable) continue;
      if (c.crate > 0) {
        if (crateHit.add(p)) c.crate--;
        continue;
      }
      final t = c.tile;
      if (t == null) continue;
      removed.add(RemovedTile(p, t));
      c.tile = null;
      cleared++;
      if (t.color != null) collected[t.color!] = collected[t.color!]! + 1;
      if (c.ice > 0) {
        c.ice--;
        iceHit.add(p);
      }
      for (final n in _neighbors(p)) {
        final nc = board.at(n);
        if (nc.crate > 0 && !toClear.contains(n) && crateHit.add(n)) nc.crate--;
      }
    }
    newSpecials.forEach((p, t) => board.at(p).tile = t);

    final mult = min(_cascade, 6);
    final points = cleared * 30 * mult + bonus + iceHit.length * 50 + crateHit.length * 80;
    score += points;
    _swapA = _swapB = null;

    return ClearStep(
      removed: removed,
      createdSpecials: newSpecials.keys.toList(),
      effects: effects,
      iceHit: iceHit,
      crateHit: crateHit,
      points: points,
      cascade: _cascade,
    );
  }

  Pos _specialPosition(_MatchGroup g, SpecialType special) {
    for (final s in [_swapB, _swapA]) {
      if (s != null && g.cells.contains(s)) return s;
    }
    if (special == SpecialType.bomb) {
      // Intersection d'un run horizontal et d'un run vertical.
      for (final h in g.runs.where((r) => r.horizontal)) {
        for (final v in g.runs.where((r) => !r.horizontal)) {
          for (final c in h.cells) {
            if (v.cells.contains(c)) return c;
          }
        }
      }
    }
    final longest = g.runs.reduce((a, b) => a.cells.length >= b.cells.length ? a : b);
    return longest.cells[longest.cells.length ~/ 2];
  }

  TileColor _mostCommonColor() {
    final counts = <TileColor, int>{};
    for (final p in board.positions) {
      final col = board.matchColor(p.x, p.y);
      if (col != null) counts[col] = (counts[col] ?? 0) + 1;
    }
    if (counts.isEmpty) return palette.first;
    return counts.entries.reduce((a, b) => a.value >= b.value ? a : b).key;
  }

  List<Pos> _row(int y) =>
      y < 0 || y >= board.height ? const [] : [for (var x = 0; x < board.width; x++) Pos(x, y)];
  List<Pos> _col(int x) =>
      x < 0 || x >= board.width ? const [] : [for (var y = 0; y < board.height; y++) Pos(x, y)];
  List<Pos> _square(Pos c, int r) => [
        for (var y = c.y - r; y <= c.y + r; y++)
          for (var x = c.x - r; x <= c.x + r; x++)
            if (board.inside(x, y)) Pos(x, y)
      ];
  Iterable<Pos> _neighbors(Pos p) => [Pos(p.x + 1, p.y), Pos(p.x - 1, p.y), Pos(p.x, p.y + 1), Pos(p.x, p.y - 1)]
      .where((n) => board.inside(n.x, n.y));

  // ---------------------------------------------------------------------------
  // Gravité
  // ---------------------------------------------------------------------------

  /// Fait tomber les éléments et remplit les cases vides. Les éléments
  /// traversent les trous et passent derrière les caisses : aucune case
  /// jouable ne peut rester vide.
  List<Spawn> applyGravity() {
    final spawned = <(Tile, int)>[];
    final spawnCount = List.filled(board.width, 0);
    var moved = true;
    while (moved) {
      moved = false;
      for (var y = board.height - 1; y >= 0; y--) {
        for (var x = 0; x < board.width; x++) {
          final c = board.cell(x, y);
          if (!c.canHoldTile || c.tile != null) continue;
          // Cherche la source au-dessus en sautant les trous et les caisses
          // (les fruits glissent derrière les caisses).
          var sy = y - 1;
          while (sy >= 0 && !board.cell(x, sy).canHoldTile) {
            sy--;
          }
          if (sy < 0) {
            // Haut de colonne : nouvel élément.
            spawnCount[x]++;
            final t = _newTile();
            c.tile = t;
            spawned.add((t, spawnCount[x]));
            moved = true;
            continue;
          }
          final src = board.cell(x, sy);
          if (src.tile != null) {
            c.tile = src.tile;
            src.tile = null;
            moved = true;
          }
        }
      }
    }
    // Position finale de chaque nouvel élément (il a pu tomber après son apparition).
    final where = <int, Pos>{};
    for (final p in board.positions) {
      final t = board.at(p).tile;
      if (t != null) where[t.id] = p;
    }
    return [for (final (t, d) in spawned) Spawn(t, where[t.id]!, d)];
  }

  // ---------------------------------------------------------------------------
  // Coups possibles et mélange
  // ---------------------------------------------------------------------------

  /// Retourne un coup valide (pour l'indice), ou `null` s'il n'y en a aucun.
  (Pos, Pos)? findHint() {
    final moves = _allValidMoves();
    return moves.isEmpty ? null : moves[rng.nextInt(moves.length)];
  }

  List<(Pos, Pos)> _allValidMoves() {
    final out = <(Pos, Pos)>[];
    for (final a in board.positions) {
      if (!_swappable(a)) continue;
      for (final b in [Pos(a.x + 1, a.y), Pos(a.x, a.y + 1)]) {
        if (!_swappable(b)) continue;
        final ta = board.at(a).tile!, tb = board.at(b).tile!;
        if (ta.isRainbow || tb.isRainbow || (ta.isSpecial && tb.isSpecial)) {
          out.add((a, b));
          continue;
        }
        board.at(a).tile = tb;
        board.at(b).tile = ta;
        final ok = _hasMatchAt(a.x, a.y) || _hasMatchAt(b.x, b.y);
        board.at(a).tile = ta;
        board.at(b).tile = tb;
        if (ok) out.add((a, b));
      }
    }
    return out;
  }

  /// Tous les coups valides (utilisé par le bot d'équilibrage).
  List<(Pos, Pos)> get validMoves => _allValidMoves();

  /// Mélange le plateau s'il n'existe plus aucun coup. Retourne `true` si mélangé.
  bool shuffleIfNeeded() {
    if (findHint() != null) return false;
    shuffle();
    return true;
  }

  /// Redistribue les éléments (les bonus sont conservés) jusqu'à obtenir un
  /// plateau sans alignement et avec au moins un coup possible.
  void shuffle() {
    final slots = board.positions.where((p) => board.at(p).canHoldTile && board.at(p).tile != null).toList();
    final tiles = [for (final p in slots) board.at(p).tile!];
    for (var attempt = 0; attempt < 100; attempt++) {
      tiles.shuffle(rng);
      for (var i = 0; i < slots.length; i++) {
        board.at(slots[i]).tile = tiles[i];
      }
      if (_findGroups().isEmpty && findHint() != null) return;
    }
    // En dernier recours : recolorer les fruits normaux.
    for (var attempt = 0; attempt < 100; attempt++) {
      for (final p in slots) {
        final t = board.at(p).tile!;
        if (!t.isSpecial) t.color = palette[rng.nextInt(palette.length)];
      }
      if (_findGroups().isEmpty && findHint() != null) return;
    }
  }

  /// Résout entièrement le plateau (sans animation). Utile pour les tests et le bot.
  List<ClearStep> resolveAll() {
    final steps = <ClearStep>[];
    ClearStep? s;
    while ((s = resolveStep()) != null) {
      steps.add(s!);
      applyGravity();
      if (steps.length > 200) break; // garde-fou
    }
    shuffleIfNeeded();
    return steps;
  }
}

class _Run {
  final List<Pos> cells;
  final bool horizontal;
  _Run(this.cells, this.horizontal);
}

class _MatchGroup {
  final List<_Run> runs = [];
  final Set<Pos> cells = {};
}
