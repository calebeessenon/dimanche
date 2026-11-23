import 'dart:async';
import 'dart:math';
import 'dart:ui';

import 'package:material_ui/material_ui.dart' show ChangeNotifier, Colors;

import '../engine/game_engine.dart';
import '../engine/models.dart';
import 'tile_art.dart';

/// Représentation visuelle d'un élément (coordonnées en cases).
class TileSprite {
  final Tile tile;
  double x, y;
  double tx, ty;
  double speed = 0;
  double scale;
  double squash = 0; // rebond à l'atterrissage
  TileSprite(this.tile, this.x, this.y, {this.scale = 1})
      : tx = x,
        ty = y;

  bool get atTarget => x == tx && y == ty && scale >= 1;
}

class DyingSprite {
  final Tile tile;
  final double x, y;
  double t = 0;
  DyingSprite(this.tile, this.x, this.y);
}

class Particle {
  double x, y, vx, vy, life, size;
  final double maxLife;
  final Color color;
  Particle(this.x, this.y, this.vx, this.vy, this.life, this.size, this.color) : maxLife = life;
}

class EffectAnim {
  final BoardEffect effect;
  double t = 0;
  EffectAnim(this.effect);
  static const duration = 0.45;
}

class FloatingText {
  final String text;
  final double x, y;
  final Color color;
  final double size;
  double t = 0;
  FloatingText(this.text, this.x, this.y, this.color, {this.size = 0.5});
  static const duration = 0.9;
}

/// Gère tout l'état d'animation du plateau. Mis à jour par le Ticker du
/// widget, il notifie le peintre à chaque image.
class BoardAnimator extends ChangeNotifier {
  final Map<int, TileSprite> sprites = {};
  final List<DyingSprite> dying = [];
  final List<Particle> particles = [];
  final List<EffectAnim> effects = [];
  final List<FloatingText> texts = [];
  final Set<Pos> iceFlash = {};
  final _rng = Random();
  double time = 0;
  double shake = 0;

  final List<Completer<void>> _waiters = [];

  static const _gravity = 55.0; // cases / s²
  static const _maxSpeed = 22.0;
  static const _slideSpeed = 7.0; // permutation

  bool get settled => sprites.values.every((s) => s.atTarget) && dying.isEmpty;

  Future<void> waitSettled() {
    if (settled) return Future.value();
    final c = Completer<void>();
    _waiters.add(c);
    return c.future;
  }

  /// Synchronise les sprites avec le plateau du moteur.
  void sync(GameEngine engine, {Map<int, int> dropFrom = const {}, bool slide = false}) {
    final seen = <int>{};
    for (final p in engine.board.positions) {
      final t = engine.board.at(p).tile;
      if (t == null) continue;
      seen.add(t.id);
      var s = sprites[t.id];
      if (s == null) {
        final d = dropFrom[t.id];
        s = d != null
            ? TileSprite(t, p.x.toDouble(), -d.toDouble())
            : TileSprite(t, p.x.toDouble(), p.y.toDouble(), scale: 0.01);
        sprites[t.id] = s;
      }
      if (slide && (s.tx != p.x || s.ty != p.y)) s.speed = _slideSpeed;
      s.tx = p.x.toDouble();
      s.ty = p.y.toDouble();
    }
    sprites.removeWhere((id, _) => !seen.contains(id));
  }

  /// Positionne directement un sprite (permutation refusée).
  void moveSprite(int id, Pos to) {
    final s = sprites[id];
    if (s == null) return;
    s
      ..tx = to.x.toDouble()
      ..ty = to.y.toDouble()
      ..speed = _slideSpeed;
  }

  void showStep(ClearStep step) {
    for (final r in step.removed) {
      final s = sprites.remove(r.tile.id);
      dying.add(DyingSprite(r.tile, s?.x ?? r.pos.x.toDouble(), s?.y ?? r.pos.y.toDouble()));
      _burst(r.pos.x + 0.5, r.pos.y + 0.5, TileArt.colorOf(r.tile.color), 7);
    }
    for (final p in step.iceHit) {
      _burst(p.x + 0.5, p.y + 0.5, const Color(0xFFCDF3FF), 6);
    }
    for (final p in step.crateHit) {
      _burst(p.x + 0.5, p.y + 0.5, const Color(0xFFB07A43), 8);
    }
    for (final e in step.effects) {
      effects.add(EffectAnim(e));
      if (e.kind == EffectKind.bomb || e.kind == EffectKind.bigBomb || e.kind == EffectKind.megaCross) shake = 0.35;
    }
    if (step.removed.isNotEmpty) {
      var cx = 0.0, cy = 0.0;
      for (final r in step.removed) {
        cx += r.pos.x;
        cy += r.pos.y;
      }
      cx = cx / step.removed.length + 0.5;
      cy = cy / step.removed.length + 0.5;
      texts.add(FloatingText('+${step.points}', cx, cy, Colors.white, size: 0.45));
    }
    notifyListeners();
  }

  void showText(String text, double x, double y, Color color, {double size = 0.9}) {
    texts.add(FloatingText(text, x, y, color, size: size));
  }

  void _burst(double x, double y, Color color, int n) {
    for (var i = 0; i < n; i++) {
      final a = _rng.nextDouble() * 2 * pi;
      final v = 2 + _rng.nextDouble() * 4;
      particles.add(Particle(x, y, cos(a) * v, sin(a) * v - 2, 0.4 + _rng.nextDouble() * 0.35,
          0.08 + _rng.nextDouble() * 0.1, color));
    }
  }

  /// Avance l'animation de [dt] secondes.
  void tick(double dt) {
    dt = min(dt, 1 / 20);
    time += dt;
    for (final s in sprites.values) {
      if (s.scale < 1) s.scale = min(1, s.scale + dt * 6);
      if (s.squash > 0) s.squash = max(0, s.squash - dt * 6);
      final dx = s.tx - s.x, dy = s.ty - s.y;
      if (dx == 0 && dy == 0) {
        s.speed = 0;
        continue;
      }
      final dist = sqrt(dx * dx + dy * dy);
      final falling = dx == 0 && dy > 0;
      s.speed = falling ? min(_maxSpeed, s.speed + _gravity * dt) : max(s.speed, _slideSpeed);
      final step = s.speed * dt;
      if (step >= dist) {
        if (falling && s.speed > 8) s.squash = 1;
        s.x = s.tx;
        s.y = s.ty;
        s.speed = 0;
      } else {
        s.x += dx / dist * step;
        s.y += dy / dist * step;
      }
    }
    for (final d in dying) {
      d.t += dt;
    }
    dying.removeWhere((d) => d.t >= 0.22);
    for (final p in particles) {
      p.vy += 9 * dt;
      p.x += p.vx * dt;
      p.y += p.vy * dt;
      p.life -= dt;
    }
    particles.removeWhere((p) => p.life <= 0);
    for (final e in effects) {
      e.t += dt;
    }
    effects.removeWhere((e) => e.t >= EffectAnim.duration);
    for (final t in texts) {
      t.t += dt;
    }
    texts.removeWhere((t) => t.t >= FloatingText.duration);
    if (shake > 0) shake = max(0, shake - dt);

    if (_waiters.isNotEmpty && settled) {
      final w = List.of(_waiters);
      _waiters.clear();
      for (final c in w) {
        c.complete();
      }
    }
    notifyListeners();
  }

  /// Termine immédiatement toutes les animations en cours.
  void finishAll() {
    for (final s in sprites.values) {
      s
        ..x = s.tx
        ..y = s.ty
        ..scale = 1;
    }
    dying.clear();
    tick(0);
  }
}
