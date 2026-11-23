import 'package:flutter_test/flutter_test.dart';
import 'package:tropi_pop/game/engine/game_engine.dart';
import 'package:tropi_pop/game/engine/models.dart';
import 'package:tropi_pop/game/levels/level_catalog.dart';
import 'package:tropi_pop/game/levels/level_definition.dart';

const _r = TileColor.red, _o = TileColor.orange, _y = TileColor.yellow, _g = TileColor.green, _b = TileColor.blue;

LevelDefinition _lvl({List<String>? layout, int moves = 20, List<Objective>? obj, List<int> stars = const [100, 200, 300]}) => LevelDefinition(
      number: 0,
      layout: layout ?? List.filled(8, '........'),
      moves: moves,
      colorCount: 5,
      objectives: obj ?? const [Objective(ObjectiveType.score, 100)],
      starScores: stars,
    );

/// Remplit le plateau avec un motif sans aucun alignement, puis applique [rows]
/// (lettres r/o/y/g/b ; '-' = conserver).
void _paint(GameEngine e, List<String> rows) {
  const base = [_r, _o, _y, _g, _b];
  for (final p in e.board.positions) {
    final c = e.board.at(p);
    if (!c.canHoldTile) continue;
    c.tile = Tile(1000 + p.y * 10 + p.x, base[(p.x + 2 * p.y) % 5]);
  }
  for (var y = 0; y < rows.length; y++) {
    for (var x = 0; x < rows[y].length; x++) {
      final ch = rows[y][x];
      if (ch == '-') continue;
      e.board.cell(x, y).tile!.color = {'r': _r, 'o': _o, 'y': _y, 'g': _g, 'b': _b}[ch];
    }
  }
}

void main() {
  test('le plateau initial ne contient aucun alignement et a un coup possible', () {
    for (var seed = 0; seed < 30; seed++) {
      final e = GameEngine(_lvl(), seed: seed);
      expect(e.resolveStep(), isNull, reason: 'seed $seed\n${e.board.dump()}');
      expect(e.findHint(), isNotNull);
    }
  });

  test('une permutation sans alignement est refusée et ne coûte pas de coup', () {
    final e = GameEngine(_lvl(), seed: 1);
    _paint(e, []);
    final before = e.board.dump();
    expect(e.swap(const Pos(0, 0), const Pos(1, 0)), SwapOutcome.invalid);
    expect(e.movesLeft, 20);
    expect(e.board.dump(), before);
  });

  test('permutations non adjacentes refusées', () {
    final e = GameEngine(_lvl(), seed: 1);
    expect(e.swap(const Pos(0, 0), const Pos(2, 0)), SwapOutcome.invalid);
    expect(e.swap(const Pos(0, 0), const Pos(1, 1)), SwapOutcome.invalid);
  });

  test('alignement de 3 : élimination, score, collecte, gravité', () {
    final e = GameEngine(_lvl(), seed: 2);
    // Ligne 0 : r r o r ... → permuter (2,0) et (3,0) donne r r r o.
    _paint(e, ['rror']);
    expect(e.swap(const Pos(2, 0), const Pos(3, 0)), SwapOutcome.match);
    expect(e.movesLeft, 19);
    final step = e.resolveStep()!;
    expect(step.removed.length, 3);
    expect(step.createdSpecials, isEmpty);
    expect(e.collected[_r], 3);
    expect(e.score, 90);
    final spawns = e.applyGravity();
    expect(spawns.length, 3);
    for (final p in e.board.positions) {
      expect(e.board.at(p).tile, isNotNull);
    }
  });

  test('création des bonus : 4 → ligne, L → bombe, 5 → arc-en-ciel', () {
    // 4 en ligne
    var e = GameEngine(_lvl(), seed: 3);
    _paint(e, ['rrbr', '--r-']);
    expect(e.swap(const Pos(2, 0), const Pos(2, 1)), SwapOutcome.match);
    var s = e.resolveStep()!;
    expect(s.createdSpecials, [const Pos(2, 0)]);
    expect(e.board.cell(2, 0).tile!.special, SpecialType.lineH);

    // 5 en ligne
    e = GameEngine(_lvl(), seed: 4);
    _paint(e, ['rrbrr', '--r--']);
    expect(e.swap(const Pos(2, 0), const Pos(2, 1)), SwapOutcome.match);
    s = e.resolveStep()!;
    expect(e.board.cell(2, 0).tile!.special, SpecialType.rainbow);
    expect(e.board.cell(2, 0).tile!.color, isNull);

    // Forme en L (posée directement, résolue comme une cascade).
    e = GameEngine(_lvl(), seed: 6);
    _paint(e, ['rrr-', 'r---', 'r---']);
    s = e.resolveStep()!;
    expect(s.removed.length, 5);
    final specials = [for (final p in e.board.positions) if (e.board.at(p).tile?.special == SpecialType.bomb) p];
    expect(specials, [const Pos(0, 0)]);
  });

  test('un bonus ligne déclenché vide toute la ligne', () {
    final e = GameEngine(_lvl(), seed: 7);
    _paint(e, ['rrbr', '--r-']);
    e.board.cell(0, 0).tile!.special = SpecialType.lineH;
    e.swap(const Pos(2, 0), const Pos(2, 1));
    final s = e.resolveStep()!;
    // 4 rouges + le reste de la ligne 0 (8 cases) ; le nouveau bonus remplace (2,0).
    expect(s.removed.where((r) => r.pos.y == 0).length, 8);
    expect(s.effects.any((f) => f.kind == EffectKind.lineH), isTrue);
  });

  test('arc-en-ciel + fruit : supprime toute la couleur', () {
    final e = GameEngine(_lvl(), seed: 8);
    _paint(e, []);
    e.board.cell(0, 0).tile = Tile(1, null, SpecialType.rainbow);
    final color = e.board.cell(1, 0).tile!.color!;
    final count = e.board.positions.where((p) => e.board.at(p).tile?.color == color).length;
    expect(e.swap(const Pos(0, 0), const Pos(1, 0)), SwapOutcome.combo);
    final s = e.resolveStep()!;
    expect(s.removed.length, count + 1);
    expect(e.board.positions.where((p) => e.board.at(p).tile?.color == color), isEmpty);
  });

  test('deux arc-en-ciel : tout le plateau', () {
    final e = GameEngine(_lvl(), seed: 9);
    _paint(e, []);
    e.board.cell(0, 0).tile = Tile(1, null, SpecialType.rainbow);
    e.board.cell(1, 0).tile = Tile(2, null, SpecialType.rainbow);
    expect(e.swap(const Pos(0, 0), const Pos(1, 0)), SwapOutcome.combo);
    final s = e.resolveStep()!;
    expect(s.removed.length, 64);
  });

  test('ligne + ligne : croix', () {
    final e = GameEngine(_lvl(), seed: 10);
    _paint(e, []);
    e.board.cell(3, 3).tile!.special = SpecialType.lineH;
    e.board.cell(4, 3).tile!.special = SpecialType.lineV;
    expect(e.swap(const Pos(3, 3), const Pos(4, 3)), SwapOutcome.combo);
    final s = e.resolveStep()!;
    expect(s.removed.length, 15); // ligne 3 + colonne 4
  });

  test('bombe + bombe : 5×5', () {
    final e = GameEngine(_lvl(), seed: 11);
    _paint(e, []);
    e.board.cell(3, 3).tile!.special = SpecialType.bomb;
    e.board.cell(4, 3).tile!.special = SpecialType.bomb;
    e.swap(const Pos(3, 3), const Pos(4, 3));
    final s = e.resolveStep()!;
    expect(s.removed.length, 25);
  });

  test('glace et caisses : endommagées par les éliminations', () {
    final e = GameEngine(
      _lvl(layout: ['iiii....', '....c...', '........', '........', '........', '........', '........', '........'],
          obj: const [Objective(ObjectiveType.clearIce, 4)]),
      seed: 12,
    );
    expect(e.initialIce, 4);
    expect(e.initialCrates, 1);
    _paint(e, ['rrbr', '--r-']);
    e.swap(const Pos(2, 0), const Pos(2, 1));
    // (2,1)=b après permutation → r r r r en ligne 0 sur 4 cases de glace.
    final s = e.resolveStep()!;
    expect(s.iceHit.length, 4);
    expect(e.board.totalIce, 0);
    expect(e.isObjectiveDone(e.level.objectives.first), isTrue);
    // La caisse en (4,1) n'est pas adjacente à (0..3,0)… (3,0) est voisin de (3,1) seulement.
    expect(e.board.totalCrates, 1);
    e.applyGravity();
    // Les éléments sous et autour de la caisse sont bien remplis.
    for (final p in e.board.positions) {
      final c = e.board.at(p);
      if (c.canHoldTile) expect(c.tile, isNotNull, reason: '$p\n${e.board.dump()}');
    }
  });

  test('gravité avec trous et caisses : aucune case jouable ne reste vide', () {
    final e = GameEngine(
      _lvl(layout: ['##....##', '#......#', '........', '.cccccc.', '........', '...##...', '........', '........']),
      seed: 13,
    );
    for (final p in e.board.positions) {
      if (e.board.at(p).canHoldTile) e.board.at(p).tile = null;
    }
    e.applyGravity();
    for (final p in e.board.positions) {
      final c = e.board.at(p);
      if (c.canHoldTile) expect(c.tile, isNotNull, reason: '$p\n${e.board.dump()}');
    }
  });

  test('mélange : garantit un coup possible sans alignement', () {
    final e = GameEngine(_lvl(), seed: 14);
    e.shuffle();
    expect(e.findHint(), isNotNull);
    expect(e.resolveStep(), isNull);
  });

  test('marteau : détruit une case sans consommer de coup', () {
    final e = GameEngine(_lvl(), seed: 15);
    expect(e.useHammer(const Pos(4, 4)), isTrue);
    final s = e.resolveStep()!;
    expect(s.removed.length, greaterThanOrEqualTo(1));
    expect(e.movesLeft, 20);
  });

  test('statut : victoire, défaite, étoiles', () {
    final e = GameEngine(_lvl(moves: 1, obj: const [Objective(ObjectiveType.score, 50)], stars: const [50, 5000, 9000]), seed: 16);
    _paint(e, ['rror']);
    e.swap(const Pos(2, 0), const Pos(3, 0));
    e.resolveAll();
    expect(e.score, greaterThanOrEqualTo(90));
    expect(e.status, GameStatus.won);
    expect(e.stars, greaterThanOrEqualTo(1));

    final l = GameEngine(_lvl(moves: 1, obj: const [Objective(ObjectiveType.score, 99999)]), seed: 16);
    _paint(l, ['rror']);
    l.swap(const Pos(2, 0), const Pos(3, 0));
    l.resolveAll();
    expect(l.status, GameStatus.lost);
  });

  test('finale : convertit les coups restants en bonus', () {
    final e = GameEngine(_lvl(moves: 10), seed: 17);
    final before = e.score;
    e.startFinale();
    e.resolveAll();
    expect(e.movesLeft, 0);
    expect(e.score, greaterThan(before + 2500));
  });

  test('catalogue : 100 niveaux valides et sérialisables', () {
    for (var n = 1; n <= LevelCatalog.count; n++) {
      final l = LevelCatalog.level(n);
      expect(l.number, n);
      expect(l.layout.every((r) => r.length == l.width), isTrue);
      expect(l.objectives, isNotEmpty);
      expect(l.starScores[0] <= l.starScores[1] && l.starScores[1] <= l.starScores[2], isTrue, reason: 'niveau $n');
      final round = LevelDefinition.fromJson(l.toJson());
      expect(round.toJson().toString(), l.toJson().toString());
      // Le plateau se construit et propose un coup.
      final e = GameEngine(l, seed: n);
      expect(e.findHint(), isNotNull, reason: 'niveau $n');
    }
  });

  test('stabilité : 2000 coups aléatoires sans exception ni case vide', () {
    for (var seed = 0; seed < 10; seed++) {
      final e = GameEngine(LevelCatalog.level(20 + seed * 7), seed: seed);
      e.addMoves(500);
      for (var i = 0; i < 200; i++) {
        final moves = e.validMoves;
        expect(moves, isNotEmpty);
        final (a, b) = moves[e.rng.nextInt(moves.length)];
        expect(e.swap(a, b), isNot(SwapOutcome.invalid));
        e.resolveAll();
        for (final p in e.board.positions) {
          final c = e.board.at(p);
          if (c.canHoldTile) expect(c.tile, isNotNull);
        }
      }
    }
  });
}
