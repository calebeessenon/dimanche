import 'dart:math';
import 'dart:ui' as ui;

import 'package:material_ui/material_ui.dart';

import '../engine/models.dart';

/// Dessin vectoriel original des fruits « Tropi Pop ».
///
/// Chaque couleur a aussi une FORME distincte (accessibilité daltoniens) :
/// fraise-cœur, orange ronde, carambole-étoile, citron vert ovale,
/// myrtille couronnée, grappe de raisin.
///
/// Les dessins sont mis en cache sous forme de `ui.Picture` (un par couleur ×
/// bonus) pour un rendu fluide à 60 i/s.
class TileArt {
  TileArt._();

  static const base = <TileColor, Color>{
    TileColor.red: Color(0xFFFF3B5C),
    TileColor.orange: Color(0xFFFF9A1F),
    TileColor.yellow: Color(0xFFFFD93B),
    TileColor.green: Color(0xFF6BD94A),
    TileColor.blue: Color(0xFF3FA2FF),
    TileColor.purple: Color(0xFFB25CFF),
  };

  static Color colorOf(TileColor? c) => c == null ? Colors.white : base[c]!;

  static final Map<String, ui.Picture> _cache = {};

  /// Dessine un élément centré dans un carré de côté [size] à [center].
  static void paint(Canvas canvas, Tile tile, Offset center, double size, {double scale = 1, double time = 0}) {
    final key = '${tile.color?.index}-${tile.special.index}';
    final pic = _cache[key] ??= _record(tile.color, tile.special);
    canvas.save();
    canvas.translate(center.dx, center.dy);
    canvas.scale(size / 100 * scale);
    canvas.drawPicture(pic);
    canvas.restore();

    // Halo animé des bonus (non mis en cache).
    if (tile.special == SpecialType.bomb) {
      final pulse = 0.5 + 0.5 * sin(time * 5);
      canvas.drawCircle(
        center,
        size * 0.47 * scale,
        Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = size * 0.05
          ..color = Colors.white.withValues(alpha: 0.25 + 0.35 * pulse),
      );
    }
  }

  static ui.Picture _record(TileColor? color, SpecialType special) {
    final rec = ui.PictureRecorder();
    final c = Canvas(rec);
    // Repère : carré 100×100 centré sur (0,0).
    if (special == SpecialType.rainbow) {
      _prismSun(c);
    } else {
      _fruit(c, color!);
      if (special == SpecialType.lineH || special == SpecialType.lineV) _stripes(c, special == SpecialType.lineH);
      if (special == SpecialType.bomb) _bombWrap(c);
    }
    return rec.endRecording();
  }

  static Paint _shade(Color col, Rect r) => Paint()
    ..shader = RadialGradient(
      center: const Alignment(-0.35, -0.45),
      radius: 0.95,
      colors: [Color.lerp(col, Colors.white, 0.45)!, col, Color.lerp(col, Colors.black, 0.35)!],
      stops: const [0, 0.55, 1],
    ).createShader(r);

  static final _outline = Paint()
    ..style = PaintingStyle.stroke
    ..strokeWidth = 3
    ..color = const Color(0x55000000);

  static void _shine(Canvas c, Offset at, double w, double h) {
    c.drawOval(
      Rect.fromCenter(center: at, width: w, height: h),
      Paint()..color = Colors.white.withValues(alpha: 0.55),
    );
  }

  static void _shadow(Canvas c, Path p) {
    c.drawPath(p.shift(const Offset(0, 4)), Paint()..color = const Color(0x44000000));
  }

  static void _leaf(Canvas c, Offset at, double s, {double angle = -0.5}) {
    c.save();
    c.translate(at.dx, at.dy);
    c.rotate(angle);
    final p = Path()
      ..moveTo(0, 0)
      ..quadraticBezierTo(s * 0.5, -s * 0.5, s, 0)
      ..quadraticBezierTo(s * 0.5, s * 0.35, 0, 0);
    c.drawPath(p, Paint()..color = const Color(0xFF2FAE4E));
    c.drawPath(p, Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 2
      ..color = const Color(0xFF1C7A33));
    c.restore();
  }

  static void _fruit(Canvas c, TileColor color) {
    final col = base[color]!;
    final r = const Rect.fromLTWH(-42, -42, 84, 84);
    switch (color) {
      case TileColor.red: // fraise en cœur
        final p = Path()
          ..moveTo(0, 40)
          ..cubicTo(-30, 22, -44, 2, -40, -16)
          ..cubicTo(-36, -36, -10, -38, 0, -22)
          ..cubicTo(10, -38, 36, -36, 40, -16)
          ..cubicTo(44, 2, 30, 22, 0, 40)
          ..close();
        _shadow(c, p);
        c.drawPath(p, _shade(col, r));
        c.drawPath(p, _outline);
        final seed = Paint()..color = const Color(0xFFFFF1A8);
        for (final s in const [Offset(-20, -8), Offset(0, -2), Offset(20, -8), Offset(-10, 12), Offset(10, 12), Offset(0, 26)]) {
          c.drawOval(Rect.fromCenter(center: s, width: 4, height: 6), seed);
        }
        _leaf(c, const Offset(-4, -26), 16, angle: -2.6);
        _leaf(c, const Offset(4, -26), 16, angle: -0.5);
        _shine(c, const Offset(-20, -20), 14, 9);
      case TileColor.orange: // orange ronde
        final p = Path()..addOval(Rect.fromCircle(center: Offset.zero, radius: 38));
        _shadow(c, p);
        c.drawPath(p, _shade(col, r));
        c.drawPath(p, _outline);
        final dot = Paint()..color = Colors.black.withValues(alpha: 0.08);
        for (var i = 0; i < 14; i++) {
          final a = i * 2.4, d = 12.0 + (i * 7) % 22;
          c.drawCircle(Offset(cos(a) * d, sin(a) * d), 1.6, dot);
        }
        _leaf(c, const Offset(2, -36), 20, angle: -0.7);
        _shine(c, const Offset(-15, -16), 16, 10);
      case TileColor.yellow: // carambole (étoile)
        final p = Path();
        for (var i = 0; i < 10; i++) {
          final a = -pi / 2 + i * pi / 5;
          final rad = i.isEven ? 42.0 : 21.0;
          final pt = Offset(cos(a) * rad, sin(a) * rad + 3);
          i == 0 ? p.moveTo(pt.dx, pt.dy) : p.lineTo(pt.dx, pt.dy);
        }
        p.close();
        _shadow(c, p);
        c.drawPath(p, _shade(col, r));
        c.drawPath(
            p,
            Paint()
              ..style = PaintingStyle.stroke
              ..strokeWidth = 5
              ..strokeJoin = StrokeJoin.round
              ..color = const Color(0xFFE0A800));
        _shine(c, const Offset(-8, -10), 10, 7);
      case TileColor.green: // citron vert ovale
        final p = Path()..addOval(Rect.fromCenter(center: Offset.zero, width: 70, height: 84));
        _shadow(c, p);
        c.drawPath(p, _shade(col, r));
        c.drawPath(p, _outline);
        final slice = Paint()
          ..style = PaintingStyle.stroke
          ..strokeWidth = 2.5
          ..color = Colors.white.withValues(alpha: 0.5);
        c.drawOval(Rect.fromCenter(center: const Offset(0, 4), width: 40, height: 52), slice);
        for (var i = 0; i < 6; i++) {
          final a = i * pi / 3;
          c.drawLine(const Offset(0, 4), Offset(cos(a) * 20, 4 + sin(a) * 26), slice);
        }
        _shine(c, const Offset(-14, -22), 14, 9);
      case TileColor.blue: // myrtille avec couronne
        final p = Path()..addOval(Rect.fromCircle(center: const Offset(0, 4), radius: 37));
        _shadow(c, p);
        c.drawPath(p, _shade(col, r));
        c.drawPath(p, _outline);
        final crown = Path();
        for (var i = 0; i < 10; i++) {
          final a = -pi / 2 + i * pi / 5;
          final rad = i.isEven ? 11.0 : 5.0;
          final pt = Offset(cos(a) * rad, sin(a) * rad - 26);
          i == 0 ? crown.moveTo(pt.dx, pt.dy) : crown.lineTo(pt.dx, pt.dy);
        }
        crown.close();
        c.drawPath(crown, Paint()..color = const Color(0xFF1F4E9E));
        _shine(c, const Offset(-15, -10), 15, 10);
      case TileColor.purple: // grappe de raisin
        final dark = Color.lerp(col, Colors.black, 0.2)!;
        const grapes = [Offset(-20, -16), Offset(0, -18), Offset(20, -16), Offset(-11, 4), Offset(11, 4), Offset(0, 24)];
        for (final g in grapes) {
          c.drawCircle(g + const Offset(0, 3), 15, Paint()..color = const Color(0x44000000));
        }
        for (final g in grapes) {
          final gr = Rect.fromCircle(center: g, radius: 15);
          c.drawCircle(g, 15, _shade(g.dy > 0 ? dark : col, gr));
          c.drawCircle(g, 15, _outline);
          c.drawCircle(g + const Offset(-5, -6), 3.5, Paint()..color = Colors.white.withValues(alpha: 0.6));
        }
        _leaf(c, const Offset(2, -32), 18, angle: -0.4);
    }
  }

  static void _stripes(Canvas c, bool horizontal) {
    final p = Paint()
      ..color = Colors.white.withValues(alpha: 0.9)
      ..strokeWidth = 6
      ..strokeCap = StrokeCap.round;
    final glow = Paint()
      ..color = const Color(0x99301050)
      ..strokeWidth = 11
      ..strokeCap = StrokeCap.round;
    for (final d in const [-16.0, 0.0, 16.0]) {
      final a = horizontal ? Offset(-30, d) : Offset(d, -30);
      final b = horizontal ? Offset(30, d) : Offset(d, 30);
      c.drawLine(a, b, glow);
      c.drawLine(a, b, p);
    }
    // Flèches aux extrémités.
    final arrow = Paint()..color = Colors.white;
    final arrowEdge = Paint()
      ..color = const Color(0x99301050)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 3
      ..strokeJoin = StrokeJoin.round;
    for (final s in const [-1.0, 1.0]) {
      final tip = horizontal ? Offset(46 * s, 0) : Offset(0, 46 * s);
      final path = Path()..moveTo(tip.dx, tip.dy);
      if (horizontal) {
        path
          ..lineTo(36 * s, -7)
          ..lineTo(36 * s, 7);
      } else {
        path
          ..lineTo(-7, 36 * s)
          ..lineTo(7, 36 * s);
      }
      path.close();
      c.drawPath(path, arrowEdge);
      c.drawPath(path, arrow);
    }
  }

  static void _bombWrap(Canvas c) {
    // Emballage « papillote » : ruban croisé + étincelles.
    final ribbon = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = 7
      ..color = Colors.white.withValues(alpha: 0.85);
    c.drawCircle(Offset.zero, 30, ribbon);
    c.drawLine(const Offset(-30, 0), const Offset(30, 0), ribbon..strokeWidth = 5);
    c.drawLine(const Offset(0, -30), const Offset(0, 30), ribbon);
    final spark = Paint()..color = const Color(0xFFFFF59D);
    for (var i = 0; i < 4; i++) {
      final a = pi / 4 + i * pi / 2;
      c.drawCircle(Offset(cos(a) * 40, sin(a) * 40), 4, spark);
    }
  }

  /// « Soleil prisme » : supprime toute une couleur.
  static void _prismSun(Canvas c) {
    const r = Rect.fromLTWH(-44, -44, 88, 88);
    final cols = [...base.values, base.values.first];
    // Rayons.
    for (var i = 0; i < 12; i++) {
      final a = i * pi / 6;
      final ray = Path()
        ..moveTo(cos(a - 0.14) * 26, sin(a - 0.14) * 26)
        ..lineTo(cos(a) * 46, sin(a) * 46)
        ..lineTo(cos(a + 0.14) * 26, sin(a + 0.14) * 26)
        ..close();
      c.drawPath(ray, Paint()..color = cols[i % 6]);
    }
    // Anneau arc-en-ciel.
    c.drawCircle(Offset.zero, 30, Paint()..shader = SweepGradient(colors: cols).createShader(r));
    // Cœur lumineux.
    c.drawCircle(
      Offset.zero,
      20,
      Paint()
        ..shader = const RadialGradient(colors: [Colors.white, Color(0xFFFFF3C4), Color(0xFFFFD36B)]).createShader(
          Rect.fromCircle(center: Offset.zero, radius: 20),
        ),
    );
    c.drawCircle(Offset.zero, 30, _outline);
    _shine(c, const Offset(-8, -9), 10, 6);
  }
}
