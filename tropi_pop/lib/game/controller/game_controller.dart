import 'dart:async';

import 'package:material_ui/material_ui.dart';

import '../../core/l10n/strings.dart';
import '../../services/audio_service.dart';
import '../engine/game_engine.dart';
import '../engine/models.dart';
import '../levels/level_definition.dart';
import '../render/board_animator.dart';

enum Phase { idle, busy, finale, won, lost }

/// Orchestration d'une partie : relie le moteur (règles), l'animateur
/// (visuel) et l'audio. L'écran observe ce contrôleur.
class GameController extends ChangeNotifier {
  final LevelDefinition level;
  final GameEngine engine;
  final BoardAnimator anim = BoardAnimator();
  final AudioService? audio;

  Phase phase = Phase.idle;
  Pos? selected;
  (Pos, Pos)? hint;
  bool hammerMode = false;
  Timer? _hintTimer;

  /// Appelé quand la partie se termine (victoire ou défaite).
  void Function(Phase result)? onGameOver;

  GameController(this.level, {this.audio, int? seed}) : engine = GameEngine(level, seed: seed) {
    anim.sync(engine);
    anim.finishAll();
    _armHint();
  }

  bool get canPlay => phase == Phase.idle;

  void _armHint() {
    _hintTimer?.cancel();
    hint = null;
    _hintTimer = Timer(const Duration(seconds: 6), () {
      if (phase == Phase.idle) {
        hint = engine.findHint();
        notifyListeners();
      }
    });
  }

  // --- Entrées --------------------------------------------------------------

  void tap(Pos p) {
    if (!canPlay) return;
    if (hammerMode) {
      unawaited(_useHammer(p));
      return;
    }
    final s = selected;
    if (s == null || s == p) {
      selected = s == p ? null : p;
      audio?.play('tap');
      notifyListeners();
    } else if (s.isAdjacent(p)) {
      selected = null;
      unawaited(trySwap(s, p));
    } else {
      selected = p;
      notifyListeners();
    }
  }

  Future<void> trySwap(Pos a, Pos b) async {
    if (!canPlay) return;
    selected = null;
    _armHint();
    final ta = _tile(a), tb = _tile(b);
    phase = Phase.busy;
    notifyListeners();

    final outcome = engine.swap(a, b);
    if (outcome == SwapOutcome.invalid) {
      if (ta != null && tb != null && a.isAdjacent(b) && engine.board.at(a).canHoldTile && engine.board.at(b).canHoldTile) {
        anim.moveSprite(ta.id, b);
        anim.moveSprite(tb.id, a);
        await anim.waitSettled();
        audio?.play('invalid');
        anim.moveSprite(ta.id, a);
        anim.moveSprite(tb.id, b);
        await anim.waitSettled();
      }
      phase = Phase.idle;
      notifyListeners();
      return;
    }
    audio?.play('swap');
    anim.sync(engine, slide: true);
    await anim.waitSettled();
    await _resolve();
    await _endTurn();
  }

  Tile? _tile(Pos p) => engine.board.inside(p.x, p.y) ? engine.board.at(p).tile : null;

  Future<void> _resolve() async {
    var maxCascade = 0;
    while (true) {
      final step = engine.resolveStep();
      if (step == null) break;
      maxCascade = step.cascade;
      _playStepSounds(step);
      anim.showStep(step);
      notifyListeners();
      final hasEffect = step.effects.isNotEmpty;
      await Future<void>.delayed(Duration(milliseconds: hasEffect ? 260 : 140));
      final spawns = engine.applyGravity();
      anim.sync(engine, dropFrom: {for (final s in spawns) s.tile.id: s.dropFrom});
      await anim.waitSettled();
    }
    if (maxCascade >= 3 && phase != Phase.finale) {
      final words = ['sweet', 'tasty', 'divine', 'delicious'];
      anim.showText(tr(words[(maxCascade - 3).clamp(0, 3)]), engine.board.width / 2, engine.board.height / 2,
          const Color(0xFFFFE066), size: 1.1);
    }
    if (engine.shuffleIfNeeded()) {
      anim.showText(tr('shuffling'), engine.board.width / 2, engine.board.height / 2, Colors.white);
      anim.sync(engine, slide: true);
      await anim.waitSettled();
    }
  }

  void _playStepSounds(ClearStep step) {
    final kinds = step.effects.map((e) => e.kind).toSet();
    if (kinds.contains(EffectKind.rainbow)) {
      audio?.play('rainbow');
    } else if (kinds.any((k) => k == EffectKind.bomb || k == EffectKind.bigBomb || k == EffectKind.megaCross)) {
      audio?.play('bomb');
    } else if (kinds.isNotEmpty) {
      audio?.play('line');
    } else {
      audio?.pop(step.cascade);
    }
    if (step.createdSpecials.isNotEmpty) audio?.play('special');
  }

  Future<void> _endTurn() async {
    switch (engine.status) {
      case GameStatus.won:
        phase = Phase.finale;
        notifyListeners();
        await Future<void>.delayed(const Duration(milliseconds: 400));
        if (engine.movesLeft > 0 || _boardHasSpecials()) {
          engine.startFinale();
          notifyListeners();
          await Future<void>.delayed(const Duration(milliseconds: 350));
          await _resolve();
        }
        phase = Phase.won;
        audio?.play('win');
        notifyListeners();
        onGameOver?.call(Phase.won);
      case GameStatus.lost:
        phase = Phase.lost;
        audio?.play('lose');
        notifyListeners();
        onGameOver?.call(Phase.lost);
      case GameStatus.playing:
        phase = Phase.idle;
        _armHint();
        notifyListeners();
    }
  }

  bool _boardHasSpecials() => engine.board.positions.any((p) => engine.board.at(p).tile?.isSpecial ?? false);

  // --- Boosters -------------------------------------------------------------

  void toggleHammer() {
    if (!canPlay) return;
    hammerMode = !hammerMode;
    selected = null;
    notifyListeners();
  }

  /// Callback pour débiter le booster ; retourne false si indisponible.
  Future<bool> Function(String id)? consumeBooster;

  Future<void> _useHammer(Pos p) async {
    hammerMode = false;
    final b = engine.board;
    final valid = b.inside(p.x, p.y) && b.at(p).playable && (b.at(p).tile != null || b.at(p).crate > 0);
    if (!valid || (consumeBooster != null && !await consumeBooster!('hammer'))) {
      notifyListeners();
      return;
    }
    engine.useHammer(p);
    phase = Phase.busy;
    audio?.play('bomb');
    notifyListeners();
    await _resolve();
    await _endTurn();
  }

  Future<void> useShuffle() async {
    if (!canPlay) return;
    if (consumeBooster != null && !await consumeBooster!('shuffle')) return;
    phase = Phase.busy;
    notifyListeners();
    engine.shuffle();
    anim.showText(tr('shuffling'), engine.board.width / 2, engine.board.height / 2, Colors.white);
    anim.sync(engine, slide: true);
    await anim.waitSettled();
    phase = Phase.idle;
    _armHint();
    notifyListeners();
  }

  /// Continuer après une défaite (+5 coups).
  void continueWithMoves(int n) {
    if (phase != Phase.lost) return;
    engine.addMoves(n);
    phase = Phase.idle;
    _armHint();
    notifyListeners();
  }

  @override
  void dispose() {
    _hintTimer?.cancel();
    anim.dispose();
    super.dispose();
  }
}
