import 'dart:math';

import 'package:material_ui/material_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../game/engine/models.dart';
import '../../game/render/tile_art.dart';

/// Logo « Tropi Pop » : lettrage dégradé contouré + fruits.
class TropiLogo extends StatefulWidget {
  final double size;
  const TropiLogo({super.key, this.size = 64});

  @override
  State<TropiLogo> createState() => _TropiLogoState();
}

class _TropiLogoState extends State<TropiLogo> with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(vsync: this, duration: const Duration(seconds: 3))..repeat();

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  Widget _word(String text, double size, List<Color> fill) => Stack(children: [
        Text(text,
            style: TextStyle(
              fontFamily: AppTheme.font,
              fontSize: size,
              fontVariations: const [FontVariation('wght', 700)],
              height: 1,
              foreground: Paint()
                ..style = PaintingStyle.stroke
                ..strokeWidth = size * 0.16
                ..strokeJoin = StrokeJoin.round
                ..color = AppColors.night,
            )),
        Text(text,
            style: TextStyle(
              fontFamily: AppTheme.font,
              fontSize: size,
              fontVariations: const [FontVariation('wght', 700)],
              height: 1,
              foreground: Paint()
                ..shader = LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: fill,
                ).createShader(Rect.fromLTWH(0, 0, size * 3, size)),
            )),
      ]);

  @override
  Widget build(BuildContext context) {
    final s = widget.size;
    return AnimatedBuilder(
      animation: _c,
      builder: (context, _) {
        final t = _c.value * 2 * pi;
        return Column(mainAxisSize: MainAxisSize.min, children: [
          Row(mainAxisSize: MainAxisSize.min, children: [
            Transform.rotate(angle: sin(t) * 0.15, child: _FruitIcon(TileColor.yellow, s * 0.7)),
            const SizedBox(width: 4),
            _word('Tropi', s, const [Color(0xFFFFF3A0), AppColors.mango, Color(0xFFFF7A00)]),
          ]),
          Transform.translate(
            offset: Offset(s * 0.4, -s * 0.12),
            child: Transform.scale(
              scale: 1 + sin(t * 2) * 0.04,
              child: Row(mainAxisSize: MainAxisSize.min, children: [
                _word('Pop!', s * 1.15, const [Color(0xFFFFB3D0), AppColors.pink, Color(0xFFC2185B)]),
                Transform.rotate(angle: cos(t) * 0.2, child: _FruitIcon(TileColor.red, s * 0.6)),
              ]),
            ),
          ),
        ]);
      },
    );
  }
}

class _FruitIcon extends StatelessWidget {
  final TileColor color;
  final double size;
  const _FruitIcon(this.color, this.size);

  @override
  Widget build(BuildContext context) => CustomPaint(size: Size.square(size), painter: _FruitPainter(color));
}

class _FruitPainter extends CustomPainter {
  final TileColor color;
  _FruitPainter(this.color);
  @override
  void paint(Canvas canvas, Size size) =>
      TileArt.paint(canvas, Tile(0, color), size.center(Offset.zero), size.width);
  @override
  bool shouldRepaint(covariant _FruitPainter old) => old.color != color;
}

/// Icône d'un fruit pour l'interface (objectifs, boutique…).
class FruitIcon extends StatelessWidget {
  final TileColor color;
  final double size;
  const FruitIcon(this.color, {super.key, this.size = 28});
  @override
  Widget build(BuildContext context) => _FruitIcon(color, size);
}
