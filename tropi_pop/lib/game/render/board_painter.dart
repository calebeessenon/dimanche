import 'dart:math';

import 'package:material_ui/material_ui.dart';

import '../../core/theme/app_theme.dart';
import '../engine/board.dart';
import '../engine/game_engine.dart';
import '../engine/models.dart';
import 'board_animator.dart';
import 'tile_art.dart';

/// Peint le plateau complet : cases, glace, fruits, caisses, effets.
class BoardPainter extends CustomPainter {
  final Board board;
  final BoardAnimator anim;
  final Pos? selected;
  final (Pos, Pos)? hint;
  final bool hammerMode;

  BoardPainter({required this.board, required this.anim, this.selected, this.hint, this.hammerMode = false})
      : super(repaint: anim);

  @override
  void paint(Canvas canvas, Size size) {
    final cs = min(size.width / board.width, size.height / board.height);
    final ox = (size.width - cs * board.width) / 2;
    final oy = (size.height - cs * board.height) / 2;

    canvas.save();
    if (anim.shake > 0) {
      final m = anim.shake * cs * 0.12;
      canvas.translate(sin(anim.time * 90) * m, cos(anim.time * 70) * m);
    }
    canvas.translate(ox, oy);

    Rect cellRect(int x, int y) => Rect.fromLTWH(x * cs, y * cs, cs, cs);

    // 1. Fond du plateau (forme du niveau).
    final bg = Paint()..color = AppColors.boardBg;
    for (final p in board.positions) {
      if (!board.at(p).playable) continue;
      canvas.drawRRect(RRect.fromRectAndRadius(cellRect(p.x, p.y).inflate(cs * 0.06), Radius.circular(cs * 0.2)), bg);
    }
    for (final p in board.positions) {
      final c = board.at(p);
      if (!c.playable) continue;
      canvas.drawRRect(
        RRect.fromRectAndRadius(cellRect(p.x, p.y).deflate(cs * 0.04), Radius.circular(cs * 0.16)),
        Paint()..color = (p.x + p.y).isEven ? AppColors.cellA : AppColors.cellB,
      );
      // 2. Glace.
      if (c.ice > 0) _paintIce(canvas, cellRect(p.x, p.y), c.ice, cs);
    }

    // Sélection.
    if (selected != null) {
      canvas.drawRRect(
        RRect.fromRectAndRadius(cellRect(selected!.x, selected!.y).deflate(cs * 0.02), Radius.circular(cs * 0.18)),
        Paint()
          ..color = Colors.white.withValues(alpha: 0.35 + 0.15 * sin(anim.time * 8)),
      );
    }

    // 3. Fruits (clip sur la zone du plateau pour masquer les chutes).
    canvas.save();
    canvas.clipRect(Rect.fromLTWH(-cs, 0, cs * (board.width + 2), cs * board.height));
    final hintIds = <int>{};
    if (hint != null) {
      for (final p in [hint!.$1, hint!.$2]) {
        final t = board.at(p).tile;
        if (t != null) hintIds.add(t.id);
      }
    }
    for (final s in anim.sprites.values) {
      var scale = s.scale;
      var center = Offset((s.x + 0.5) * cs, (s.y + 0.5) * cs);
      if (hintIds.contains(s.tile.id)) scale *= 1 + 0.08 * sin(anim.time * 9);
      if (s.squash > 0) {
        canvas.save();
        final sq = s.squash * 0.12;
        canvas.translate(center.dx, center.dy + cs * 0.4);
        canvas.scale(1 + sq, 1 - sq);
        canvas.translate(-center.dx, -(center.dy + cs * 0.4));
        TileArt.paint(canvas, s.tile, center, cs * 0.86, scale: scale, time: anim.time);
        canvas.restore();
      } else {
        TileArt.paint(canvas, s.tile, center, cs * 0.86, scale: scale, time: anim.time);
      }
    }
    for (final d in anim.dying) {
      final k = d.t / 0.22;
      TileArt.paint(canvas, d.tile, Offset((d.x + 0.5) * cs, (d.y + 0.5) * cs), cs * 0.86, scale: 1 + k * 0.4 - k * k * 1.3);
    }
    canvas.restore();

    // 4. Caisses (au-dessus : les fruits glissent derrière).
    for (final p in board.positions) {
      final c = board.at(p);
      if (c.crate > 0) _paintCrate(canvas, cellRect(p.x, p.y).deflate(cs * 0.05), c.crate, cs);
    }

    // 5. Effets de bonus.
    for (final e in anim.effects) {
      _paintEffect(canvas, e, cs);
    }

    // 6. Particules.
    for (final p in anim.particles) {
      final a = (p.life / p.maxLife).clamp(0.0, 1.0);
      canvas.drawCircle(Offset(p.x * cs, p.y * cs), p.size * cs * (0.5 + a * 0.5), Paint()..color = p.color.withValues(alpha: a));
    }

    // 7. Textes flottants.
    for (final t in anim.texts) {
      final k = t.t / FloatingText.duration;
      final tp = TextPainter(
        text: TextSpan(
          text: t.text,
          style: AppTheme.title(cs * t.size, color: t.color.withValues(alpha: (1 - k * k).clamp(0, 1))),
        ),
        textDirection: TextDirection.ltr,
      )..layout();
      tp.paint(canvas, Offset(t.x * cs - tp.width / 2, (t.y - k * 0.8) * cs - tp.height / 2));
    }

    canvas.restore();
  }

  void _paintIce(Canvas canvas, Rect r, int layers, double cs) {
    final rr = RRect.fromRectAndRadius(r.deflate(cs * 0.06), Radius.circular(cs * 0.14));
    canvas.drawRRect(
      rr,
      Paint()
        ..shader = LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: layers >= 2
              ? [const Color(0xDDE6FAFF), const Color(0xCC7FD3F7)]
              : [const Color(0x99E6FAFF), const Color(0x887FD3F7)],
        ).createShader(r),
    );
    final line = Paint()
      ..color = Colors.white.withValues(alpha: 0.8)
      ..strokeWidth = cs * 0.03
      ..style = PaintingStyle.stroke;
    canvas.drawRRect(rr, line);
    if (layers >= 2) {
      final p = Path()
        ..moveTo(r.left + cs * 0.2, r.top + cs * 0.25)
        ..lineTo(r.left + cs * 0.45, r.top + cs * 0.45)
        ..lineTo(r.left + cs * 0.35, r.top + cs * 0.7);
      canvas.drawPath(p, line);
    }
  }

  void _paintCrate(Canvas canvas, Rect r, int hp, double cs) {
    final rr = RRect.fromRectAndRadius(r, Radius.circular(cs * 0.1));
    canvas.drawRRect(rr.shift(Offset(0, cs * 0.04)), Paint()..color = const Color(0x66000000));
    canvas.drawRRect(
      rr,
      Paint()
        ..shader = const LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFFD9A066), Color(0xFFA86B32)],
        ).createShader(r),
    );
    final plank = Paint()
      ..color = const Color(0xFF7A4A1E)
      ..strokeWidth = cs * 0.05
      ..style = PaintingStyle.stroke;
    canvas.drawRRect(rr.deflate(cs * 0.06), plank);
    canvas.drawLine(r.topLeft + Offset(cs * 0.12, cs * 0.12), r.bottomRight - Offset(cs * 0.12, cs * 0.12), plank);
    canvas.drawLine(r.topRight + Offset(-cs * 0.12, cs * 0.12), r.bottomLeft + Offset(cs * 0.12, -cs * 0.12), plank);
    if (hp >= 2) {
      final band = Paint()..color = const Color(0xFF9AA5B1);
      canvas.drawRect(Rect.fromLTWH(r.left, r.center.dy - cs * 0.06, r.width, cs * 0.12), band);
      for (final dx in [0.18, 0.82]) {
        canvas.drawCircle(Offset(r.left + r.width * dx, r.center.dy), cs * 0.035, Paint()..color = const Color(0xFF4F5B66));
      }
    }
  }

  void _paintEffect(Canvas canvas, EffectAnim e, double cs) {
    final k = e.t / EffectAnim.duration;
    final a = (1 - k).clamp(0.0, 1.0);
    final c = Offset((e.effect.at.x + 0.5) * cs, (e.effect.at.y + 0.5) * cs);
    final w = board.width * cs, h = board.height * cs;
    final glow = Paint()
      ..color = Colors.white.withValues(alpha: 0.8 * a)
      ..maskFilter = MaskFilter.blur(BlurStyle.normal, cs * 0.15);
    void hBeam(double y) => canvas.drawRRect(
        RRect.fromRectAndRadius(Rect.fromLTWH(0, y - cs * 0.22 * a, w, cs * 0.44 * a), Radius.circular(cs * 0.2)), glow);
    void vBeam(double x) => canvas.drawRRect(
        RRect.fromRectAndRadius(Rect.fromLTWH(x - cs * 0.22 * a, 0, cs * 0.44 * a, h), Radius.circular(cs * 0.2)), glow);
    switch (e.effect.kind) {
      case EffectKind.lineH:
        hBeam(c.dy);
      case EffectKind.lineV:
        vBeam(c.dx);
      case EffectKind.cross:
        hBeam(c.dy);
        vBeam(c.dx);
      case EffectKind.megaCross:
        for (var d = -1; d <= 1; d++) {
          hBeam(c.dy + d * cs);
          vBeam(c.dx + d * cs);
        }
      case EffectKind.bomb:
      case EffectKind.bigBomb:
        final r = cs * (e.effect.kind == EffectKind.bomb ? 1.6 : 2.6) * Curves.easeOut.transform(k.clamp(0, 1));
        canvas.drawCircle(c, r, Paint()..color = const Color(0xFFFFE08A).withValues(alpha: 0.45 * a));
        canvas.drawCircle(
            c,
            r,
            Paint()
              ..style = PaintingStyle.stroke
              ..strokeWidth = cs * 0.15 * a
              ..color = Colors.white.withValues(alpha: a));
      case EffectKind.rainbow:
        final col = TileArt.colorOf(e.effect.color);
        canvas.drawCircle(c, cs * 3 * k, Paint()..color = col.withValues(alpha: 0.35 * a));
        canvas.drawCircle(
            c,
            cs * 4 * k,
            Paint()
              ..style = PaintingStyle.stroke
              ..strokeWidth = cs * 0.2 * a
              ..shader = SweepGradient(colors: [...TileArt.base.values, TileArt.base.values.first])
                  .createShader(Rect.fromCircle(center: c, radius: cs * 4)));
    }
  }

  @override
  bool shouldRepaint(covariant BoardPainter old) =>
      old.selected != selected || old.hint != hint || old.hammerMode != hammerMode || old.board != board;
}
